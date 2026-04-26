# 23 — Repositorios: detalle de consultas personalizadas

Este documento cubre los métodos personalizados de los repositorios que no fueron explicados en profundidad en documentos anteriores: `NotificationRepository`, `FollowRepository`, `UserRepository` y el comportamiento base de `ServiceEntityRepository`.

---

## 1. Base común: `ServiceEntityRepository`

Todos los repositorios de la aplicación extienden `ServiceEntityRepository<T>`, que a su vez extiende `EntityRepository`. Esto proporciona de forma gratuita:

| Método | Descripción |
|--------|-------------|
| `find($id)` | Busca por clave primaria. Devuelve `?T`. |
| `findAll()` | Devuelve todos los registros. |
| `findBy(criteria, orderBy, limit, offset)` | Búsqueda por criterios simples. |
| `findOneBy(criteria)` | Igual que `findBy` pero devuelve solo el primero. |
| `count(criteria)` | `SELECT COUNT(*) WHERE criteria` sin cargar objetos. |
| `createQueryBuilder(alias)` | Inicia un `QueryBuilder` para consultas DQL complejas. |

Los repositorios personalizados solo añaden métodos cuando la lógica no se puede expresar con los métodos base.

---

## 2. `NotificationRepository`

### 2.1 `findForUser()` — ventana de 72 horas

```php
public function findForUser(User $user, int $limit = 30): array
{
    $since = new \DateTimeImmutable('-72 hours');

    return $this->createQueryBuilder('n')
        ->where('n.recipient = :user')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('user', $user)
        ->setParameter('since', $since)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

Devuelve las notificaciones de las últimas 72 horas, limitadas a 30. Esta ventana temporal es la consulta principal que usa el badge de notificaciones del frontend.

`new \DateTimeImmutable('-72 hours')` crea un objeto de fecha relativo al momento de ejecución. La sintaxis de modificador de PHP acepta strings como `'-72 hours'`, `'-3 days'`, `'+1 week'`, etc.

### 2.2 `findAllForUser()` — historial completo

```php
public function findAllForUser(User $user, int $limit = 100): array
{
    return $this->createQueryBuilder('n')
        ->where('n.recipient = :user')
        ->setParameter('user', $user)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

Sin límite temporal, hasta 100 notificaciones. Se usa para el historial completo (`GET /api/notifications/history`).

### 2.3 `countUnread()` — conteo de no leídas

```php
public function countUnread(User $user): int
{
    return (int) $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->where('n.recipient = :user AND n.isRead = false')
        ->setParameter('user', $user)
        ->getQuery()
        ->getSingleScalarResult();
}
```

`getSingleScalarResult()` devuelve un único valor escalar (el COUNT) sin crear objetos. Es la forma más eficiente de obtener un número de la BD.

El resultado es `string` en PHP (Doctrine lo recibe del driver como string), por lo que se castea a `int`.

### 2.4 `markAllRead()` — actualización masiva sin cargar objetos

```php
public function markAllRead(User $user): void
{
    $this->createQueryBuilder('n')
        ->update()
        ->set('n.isRead', 'true')
        ->where('n.recipient = :user AND n.isRead = false')
        ->setParameter('user', $user)
        ->getQuery()
        ->execute();
}
```

`->update()` genera un `UPDATE` directo en BD sin cargar los objetos en memoria. Actualiza potencialmente decenas de registros en una sola query:

```sql
UPDATE notification
SET is_read = true
WHERE recipient_id = ? AND is_read = false
```

Comparado con la alternativa naive:
```php
// MAL: carga todos los objetos, hace N flush
foreach ($notifications as $n) {
    $n->setIsRead(true);
}
$em->flush();
```

La versión con `->update()` es O(1) en consultas independientemente del número de notificaciones.

### 2.5 `deleteByRefIdAndType()` — limpieza tras procesar solicitudes

```php
public function deleteByRefIdAndType(User $recipient, string $type, int $refId): void
{
    $this->createQueryBuilder('n')
        ->delete()
        ->where('n.recipient = :recipient AND n.type = :type AND n.refId = :refId')
        ->setParameter('recipient', $recipient)
        ->setParameter('type', $type)
        ->setParameter('refId', $refId)
        ->getQuery()
        ->execute();
}
```

`->delete()` genera un `DELETE` directo. Se usa tras aceptar o rechazar una solicitud de seguimiento:

```php
// Tras aceptar el follow:
$this->repo->deleteByRefIdAndType($me, Notification::TYPE_FOLLOW_REQUEST, $followId);
```

Esto elimina la notificación `follow_request` con ese `refId` específico (el ID del `Follow`). Si el usuario tiene múltiples solicitudes pendientes, solo se elimina la procesada.

---

## 3. `FollowRepository`

### 3.1 `findFollow()` — estado del follow entre dos usuarios

```php
public function findFollow(User $follower, User $following): ?Follow
{
    return $this->findOneBy(['follower' => $follower, 'following' => $following]);
}
```

Devuelve el registro `Follow` independientemente de su estado (`pending` o `accepted`). Se usa en múltiples lugares:

- En `GET /api/users/{id}` para calcular `followStatus`
- En `GET /api/users/search` para calcular `followStatus` por cada resultado
- En `POST /api/users/{id}/follow` para comprobar si ya existe un follow

### 3.2 `countFollowers()` y `countFollowing()`

```php
public function countFollowers(User $user): int
{
    return $this->count(['following' => $user, 'status' => Follow::STATUS_ACCEPTED]);
}

public function countFollowing(User $user): int
{
    return $this->count(['follower' => $user, 'status' => Follow::STATUS_ACCEPTED]);
}
```

Ambos usan el método `count()` heredado de `ServiceEntityRepository`, que internamente genera `SELECT COUNT(*) WHERE criteria`. Solo se cuentan los follows con `status = 'accepted'` — los pendientes no se incluyen en los contadores públicos.

### 3.3 `findFollowers()` y `findFollowing()`

```php
public function findFollowers(User $user): array
{
    return $this->findBy(
        ['following' => $user, 'status' => Follow::STATUS_ACCEPTED],
        ['createdAt' => 'DESC']
    );
}

public function findFollowing(User $user): array
{
    return $this->findBy(
        ['follower' => $user, 'status' => Follow::STATUS_ACCEPTED],
        ['createdAt' => 'DESC']
    );
}
```

Ambas devuelven objetos `Follow` ordenados por fecha de seguimiento, de más reciente a más antiguo. El controlador itera sobre ellos para construir la respuesta con datos del usuario.

### 3.4 `findIncomingRequests()` y `countIncomingRequests()`

```php
public function findIncomingRequests(User $user): array
{
    return $this->findBy(
        ['following' => $user, 'status' => Follow::STATUS_PENDING],
        ['createdAt' => 'DESC']
    );
}

public function countIncomingRequests(User $user): int
{
    return $this->count(['following' => $user, 'status' => Follow::STATUS_PENDING]);
}
```

Solo relevantes para cuentas privadas. Devuelven/cuentan las solicitudes de seguimiento pendientes de aprobación.

---

## 4. `UserRepository`

### 4.1 `search()` — búsqueda case-insensitive por subcadena

```php
public function search(string $q, int $limit = 20): array
{
    return $this->createQueryBuilder('u')
        ->where('LOWER(u.displayName) LIKE LOWER(:q)')
        ->setParameter('q', '%' . $q . '%')
        ->orderBy('u.displayName', 'ASC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

`LOWER()` en ambos lados de la comparación garantiza que buscar `"mar"` encuentre `"MariaG"`, `"MARIO"` o `"tamara"`. El parámetro se envuelve con `%...%` para búsqueda de subcadena.

**SQL generado:**
```sql
SELECT u.*
FROM user u
WHERE LOWER(u.display_name) LIKE LOWER('%mar%')
ORDER BY u.display_name ASC
LIMIT 20
```

### 4.2 `upgradePassword()` — rehashing automático

```php
public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
{
    $user->setPassword($newHashedPassword);
    $this->getEntityManager()->persist($user);
    $this->getEntityManager()->flush();
}
```

`UserRepository` implementa `PasswordUpgraderInterface`. Symfony llama automáticamente a este método cuando detecta que el hash de la contraseña de un usuario fue generado con un algoritmo más antiguo. Actualiza el hash al algoritmo actual sin requerir que el usuario cambie su contraseña.

---

## 5. `BookReviewRepository`

### 5.1 `findOneByUserAndBook()`

```php
public function findOneByUserAndBook(User $user, Book $book): ?BookReview
{
    return $this->findOneBy(['user' => $user, 'book' => $book]);
}
```

Método de conveniencia que encapsula la restricción única `(user, book)`. Se usa tanto en la lectura (para obtener `myRating`) como en el upsert (para decidir si crear o actualizar).

### 5.2 `findByBook()`

```php
public function findByBook(Book $book): array
{
    return $this->createQueryBuilder('r')
        ->join('r.user', 'u')
        ->addSelect('u')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->orderBy('r.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

Eager loading del usuario de cada reseña (JOIN + addSelect). Devuelve las reseñas de más reciente a más antigua. Solo incluye reseñas con `content` no nulo (reseñas con texto) — las puramente numéricas no aparecen en el listado público.

### 5.3 `getStats()` — media, total y distribución

```php
public function getStats(Book $book): array
{
    // Media y total global
    $row = $this->createQueryBuilder('r')
        ->select('AVG(r.rating) AS avg, COUNT(r.id) AS total')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->getQuery()
        ->getOneOrNullResult();

    // Distribución por estrella
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

Dos consultas en lugar de cargar todos los objetos:

1. **Primera consulta:** `AVG + COUNT` para la media y el total.
2. **Segunda consulta:** `GROUP BY rating` para la distribución por estrellas.

`$dist` se inicializa con `[1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]` para garantizar que todas las estrellas aparezcan en la respuesta aunque tengan cero reseñas.

---

## 6. Otros repositorios

### 6.1 `ShelfBookRepository`

Solo usa `findOneBy()` heredado para buscar si un libro ya está en una estantería:

```php
// En ShelfApiController
$existing = $shelfBookRepo->findOneBy(['shelf' => $shelf, 'book' => $book]);
```

### 6.2 `ReadingProgressRepository`

Solo usa `findOneBy()` para la restricción única `(user, book)`:

```php
$existing = $this->repo->findOneBy(['user' => $this->getUser(), 'book' => $book]);
```

### 6.3 `ClubRepository`

`findBy([], ['id' => 'DESC'])` para el panel de admin. Para el listado público:

```php
// En ClubApiController - filtra por visibilidad o membresía
$clubs = $clubRepo->findBy(['visibility' => 'public']);
```

### 6.4 `ClubJoinRequestRepository`

```php
// findPendingWithUser — eager loading del usuario solicitante
public function findPendingWithUser(Club $club): array
{
    return $this->createQueryBuilder('r')
        ->join('r.user', 'u')
        ->addSelect('u')
        ->where('r.club = :club AND r.status = :pending')
        ->setParameter('club', $club)
        ->setParameter('pending', 'pending')
        ->getQuery()
        ->getResult();
}
```

Eager loading para evitar N+1 al listar solicitudes pendientes de un club.

### 6.5 `ClubChatRepository`

```php
public function findByClubWithCreator(Club $club): array
{
    return $this->createQueryBuilder('c')
        ->join('c.createdBy', 'u')
        ->addSelect('u')
        ->where('c.club = :club')
        ->setParameter('club', $club)
        ->orderBy('c.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

Precarga el `createdBy` (User) de cada hilo para evitar una consulta extra por hilo al serializar la respuesta.

---

## 7. Tabla resumen de métodos DQL avanzados

| Patrón | Método Doctrine | Cuándo usarlo |
|--------|----------------|---------------|
| Conteo sin cargar objetos | `count(criteria)` / `COUNT(m.id)` + `getSingleScalarResult()` | Cuando solo necesitas el número |
| Actualización masiva | `createQueryBuilder()->update()->set()->where()` | Para UPDATE de múltiples filas |
| Eliminación masiva | `createQueryBuilder()->delete()->where()` | Para DELETE de múltiples filas sin cargar |
| Eager loading | `->join('r.user', 'u')->addSelect('u')` | Prevenir N+1 al acceder a relaciones |
| Paginación | `->setFirstResult(offset)->setMaxResults(limit)` | Listas largas con páginas |
| Agrupación | `->groupBy('campo')->select('campo, COUNT(id)')` | Estadísticas y distribuciones |
| FK sin JOIN | `IDENTITY(m.club)` en DQL | Obtener el ID de una FK sin hacer JOIN |
| Búsqueda case-insensitive | `LOWER(campo) LIKE LOWER(:q)` | Búsquedas de texto |
