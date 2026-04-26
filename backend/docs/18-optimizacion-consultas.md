# 18 — Optimización de consultas

Este documento explica los problemas de rendimiento más comunes en aplicaciones que usan un ORM y describe cómo se han resuelto en TFGdaw.

---

## 1. El problema N+1

El **problema N+1** aparece cuando se obtiene una lista de N entidades y luego se accede a una relación de cada una de ellas, provocando N consultas adicionales (una por entidad). En total: 1 consulta inicial + N consultas relacionadas = N+1 consultas.

### Ejemplo concreto: listado de clubes

Sin optimización, listar 20 clubes e incluir el número de miembros de cada uno lanzaría 21 consultas:

```
1.  SELECT * FROM club                          -- 1 consulta principal
2.  SELECT COUNT(*) FROM club_member WHERE club_id = 1
3.  SELECT COUNT(*) FROM club_member WHERE club_id = 2
...
21. SELECT COUNT(*) FROM club_member WHERE club_id = 20
```

Con **batch query**, esto se reduce a 2 consultas:

```sql
-- Consulta 1: todos los clubes
SELECT * FROM club;

-- Consulta 2: todos los conteos en una sola pasada
SELECT m.club_id, COUNT(m.id)
FROM club_member m
WHERE m.club_id IN (1, 2, 3, ..., 20)
GROUP BY m.club_id;
```

---

## 2. `getMemberCountsForClubs()` — batch de conteos

```php
// ClubMemberRepository.php
public function getMemberCountsForClubs(array $clubs): array
{
    if (empty($clubs)) {
        return [];
    }

    $rows = $this->createQueryBuilder('m')
        ->select('IDENTITY(m.club) AS clubId, COUNT(m.id) AS cnt')
        ->where('m.club IN (:clubs)')
        ->setParameter('clubs', $clubs)
        ->groupBy('m.club')
        ->getQuery()
        ->getResult();

    $map = [];
    foreach ($rows as $row) {
        $map[(int) $row['clubId']] = (int) $row['cnt'];
    }

    return $map;
}
```

**Puntos clave:**

- `IDENTITY(m.club)` extrae el ID de la relación `ManyToOne` sin hacer JOIN. Es la función DQL para acceder a la FK directamente.
- `IN (:clubs)` acepta un array de objetos `Club`; Doctrine los convierte automáticamente en sus IDs.
- El resultado es un `array<int, int>`: `[clubId => memberCount]`.
- La guarda de `empty($clubs)` evita un error de SQL si el array está vacío (la cláusula `IN ()` vacía no es SQL válido).

**Uso en `ClubApiController`:**

```php
$clubs        = $clubRepo->findAll();
$countMap     = $memberRepo->getMemberCountsForClubs($clubs);
$memberCount  = $countMap[$club->getId()] ?? 0;
```

---

## 3. `getMembershipsMapForUser()` — membresías en lote

Similar al anterior, pero en lugar de conteos devuelve los objetos `ClubMember` completos para poder consultar el rol del usuario en cada club:

```php
public function getMembershipsMapForUser(User $user, array $clubs): array
{
    $memberships = $this->createQueryBuilder('m')
        ->where('m.user = :user')
        ->andWhere('m.club IN (:clubs)')
        ->setParameter('user', $user)
        ->setParameter('clubs', $clubs)
        ->getQuery()
        ->getResult();

    $map = [];
    foreach ($memberships as $m) {
        $map[$m->getClub()->getId()] = $m;
    }

    return $map;
}
```

**Resultado:** `array<int, ClubMember>` — `[clubId => ClubMember]`.

Para un usuario con 5 clubes activos de una lista de 20, la consulta trae solo esas 5 membresías. El controlador luego indexa el mapa por `clubId` para acceso O(1):

```php
$membershipMap = $memberRepo->getMembershipsMapForUser($me, $clubs);
$membership    = $membershipMap[$club->getId()] ?? null;
$userRole      = $membership?->getRole();  // null si no es miembro
```

---

## 4. `countByClub()` — COUNT sin cargar la colección

Doctrine carga la colección completa si se hace `$club->getMembers()->count()`. Para una sola consulta puntual esto es aceptable, pero en un bucle sobre N clubes el impacto es grave.

```php
// MAL: carga todos los objetos ClubMember en memoria solo para contar
$count = $club->getMembers()->count();

// BIEN: COUNT directo en BD
public function countByClub(Club $club): int
{
    return (int) $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.club = :club')
        ->setParameter('club', $club)
        ->getQuery()
        ->getSingleScalarResult();
}
```

> **Nota:** `countByClub()` se usa cuando se necesita el conteo para un único club (por ejemplo, en `GET /api/clubs/{id}`). Para múltiples clubes en paralelo se usa `getMemberCountsForClubs()`.

---

## 5. Eager loading con `JOIN + addSelect`

Por defecto Doctrine usa **lazy loading**: las relaciones no se cargan hasta que se accede a ellas. Al iterar sobre una colección y acceder a la relación `user` de cada elemento, se lanza una consulta extra por cada elemento.

La solución es cargar la relación en la misma consulta usando `addSelect`:

```php
// findMembersWithUser — ClubMemberRepository
public function findMembersWithUser(Club $club): array
{
    return $this->createQueryBuilder('m')
        ->join('m.user', 'u')        // JOIN a la tabla user
        ->addSelect('u')             // incluye el objeto User en el resultado hidratado
        ->where('m.club = :club')
        ->setParameter('club', $club)
        ->orderBy('m.joinedAt', 'ASC')
        ->getQuery()
        ->getResult();
}
```

**SQL generado:**
```sql
SELECT m.*, u.*
FROM club_member m
INNER JOIN user u ON m.user_id = u.id
WHERE m.club_id = ?
ORDER BY m.joined_at ASC
```

El resultado es una lista de objetos `ClubMember` cuyos atributos `user` ya están hidratados — sin consultas adicionales al acceder a `$member->getUser()`.

### Otros métodos con eager loading

| Método | Repositorio | Relación precargada |
|--------|-------------|---------------------|
| `findMembersWithUser()` | `ClubMemberRepository` | `ClubMember → User` |
| `findByClubWithCreator()` | `ClubChatRepository` | `ClubChat → User (createdBy)` |
| `findPendingWithUser()` | `ClubJoinRequestRepository` | `ClubJoinRequest → User` |
| `findPaginated()` | `ClubChatMessageRepository` | `ClubChatMessage → User` |

---

## 6. `findPaginated()` — paginación con Doctrine

Los mensajes de un hilo de debate pueden ser miles. Cargarlos todos en memoria sería inviable. El repositorio implementa paginación offset/limit:

```php
// ClubChatMessageRepository.php
public function findPaginated(int $chatId, int $page, int $limit): array
{
    return $this->createQueryBuilder('m')
        ->join('m.user', 'u')
        ->addSelect('u')
        ->where('m.chat = :chatId')
        ->setParameter('chatId', $chatId)
        ->orderBy('m.createdAt', 'ASC')
        ->setFirstResult(($page - 1) * $limit)   // OFFSET
        ->setMaxResults($limit)                   // LIMIT
        ->getQuery()
        ->getResult();
}
```

**Equivalente SQL:**
```sql
SELECT m.*, u.*
FROM club_chat_message m
INNER JOIN user u ON m.user_id = u.id
WHERE m.chat_id = ?
ORDER BY m.created_at ASC
LIMIT ? OFFSET ?
```

**Parámetros en la petición HTTP:**
```
GET /api/clubs/{id}/chats/{chatId}/messages?page=2&limit=50
```

El controlador lee y valida estos parámetros:
```php
$page  = max(1, (int) $request->query->get('page', 1));
$limit = min(100, max(1, (int) $request->query->get('limit', 50)));
```

- `max(1, ...)` impide páginas negativas o cero.
- `min(100, ...)` impide que un cliente pida más de 100 mensajes por petición.

La respuesta incluye los metadatos necesarios para que el cliente sepa cuántas páginas existen:
```json
{
  "page": 2,
  "limit": 50,
  "total": 143,
  "messages": [...]
}
```

---

## 7. `countByChat()` — conteo sin cargar mensajes

Al igual que con los clubes, el conteo total de mensajes de un hilo se hace con `COUNT` en lugar de cargar la colección:

```php
public function countByChat(int $chatId): int
{
    return (int) $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.chat = :chatId')
        ->setParameter('chatId', $chatId)
        ->getQuery()
        ->getSingleScalarResult();
}
```

Se usa tanto al listar los hilos de un club (para mostrar cuántos mensajes tiene cada uno) como para calcular el total en la respuesta paginada.

---

## 8. `findFeed()` — LEFT JOIN en el feed

La consulta del feed necesita traer tanto los posts propios como los de usuarios seguidos sin hacer dos consultas separadas:

```php
// PostRepository.php
public function findFeed(User $me, int $limit = 40): array
{
    return $this->createQueryBuilder('p')
        ->leftJoin(
            'App\Entity\Follow', 'f',
            'WITH',
            'f.follower = :me AND f.following = p.user AND f.status = :accepted'
        )
        ->andWhere('p.user = :me OR f.id IS NOT NULL')
        ->setParameter('me', $me)
        ->setParameter('accepted', 'accepted')
        ->orderBy('p.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

**SQL equivalente:**
```sql
SELECT p.*
FROM post p
LEFT JOIN follow f ON f.follower_id = :me
                  AND f.following_id = p.user_id
                  AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

**Por qué LEFT JOIN y no INNER JOIN:**  
Con INNER JOIN, si el usuario no sigue a nadie, no aparecerían sus propios posts (porque no habría ningún registro `follow` que coincidiera). LEFT JOIN preserva todos los posts y la condición `p.user_id = :me` recupera los propios.

---

## 9. `getStats()` — estadísticas en una sola consulta

Las estadísticas de reseñas de un libro (media, total, distribución) podrían calcularse cargando todos los objetos `BookReview` y haciendo los cálculos en PHP. En cambio, se delegan a la BD:

```php
// BookReviewRepository.php
public function getStats(Book $book): array
{
    $row = $this->createQueryBuilder('r')
        ->select('AVG(r.rating) AS avg, COUNT(r.id) AS total')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->getQuery()
        ->getOneOrNullResult();

    $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $rows = $this->createQueryBuilder('r')
        ->select('r.rating, COUNT(r.id) AS cnt')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->groupBy('r.rating')
        ->getQuery()
        ->getResult();

    foreach ($rows as $r) {
        $dist[(int) $r['rating']] = (int) $r['cnt'];
    }

    return [
        'average'      => $row['avg'] ? round((float) $row['avg'], 1) : null,
        'count'        => (int) ($row['total'] ?? 0),
        'distribution' => $dist,
    ];
}
```

Son 2 consultas en lugar de cargar N objetos en PHP: una para `AVG + COUNT` global y otra para la distribución por estrellas.

---

## 10. Resumen de estrategias aplicadas

| Problema | Consultas sin optimizar | Solución aplicada | Consultas optimizadas |
|----------|------------------------|-------------------|-----------------------|
| Conteo de miembros al listar N clubes | N+1 | `getMemberCountsForClubs()` batch | 2 |
| Rol del usuario en N clubes | N+1 | `getMembershipsMapForUser()` batch | 2 |
| Cargar usuarios de N miembros | N | `findMembersWithUser()` eager JOIN | 1 |
| Cargar autores de N mensajes | N | `findPaginated()` eager JOIN | 1 |
| Conteo de mensajes por hilo | 1 carga colección | `countByChat()` COUNT | 1 (ligera) |
| Feed con follows | 2 separadas + merge PHP | `findFeed()` LEFT JOIN | 1 |
| Estadísticas de reseñas | N cargas + PHP | `getStats()` AVG/COUNT en BD | 2 |
