# 06 — Repositorios

Los repositorios son clases que encapsulan las consultas a la base de datos. Todos se encuentran en `src/Repository/` y extienden `ServiceEntityRepository` de Doctrine, lo que les da acceso a métodos básicos como `find()`, `findBy()`, `findOneBy()` y `count()`. Los repositorios personalizados añaden métodos más complejos usando el **QueryBuilder** de Doctrine.

---

## Por qué usar repositorios

Centralizar las consultas en repositorios (en lugar de escribirlas en los controladores) tiene varias ventajas:
- El controlador no necesita saber cómo se construye la consulta SQL.
- Si cambia la lógica de la consulta, solo se modifica en un lugar.
- Es más fácil de testear de forma aislada.

---

## 1. `UserRepository`

**Implementa:** `PasswordUpgraderInterface`

### `search(string $q, int $limit = 20): User[]`
Busca usuarios por `displayName` de forma insensible a mayúsculas/minúsculas.

```sql
SELECT * FROM user
WHERE LOWER(display_name) LIKE LOWER('%q%')
ORDER BY display_name ASC
LIMIT 20
```

Utilizada por `GET /api/users/search?q=...`.

### `upgradePassword(User $user, string $newHashedPassword): void`
Implementación requerida por Symfony. Si el algoritmo de hashing mejora en una nueva versión de PHP, Symfony llama automáticamente a este método al hacer login para re-hashear la contraseña del usuario con el nuevo algoritmo más seguro. Actualiza el hash directamente en BD sin que el usuario lo note.

---

## 2. `PostRepository`

### `findByUser(User $user, int $limit = 30): Post[]`
Devuelve las publicaciones de un usuario ordenadas de más reciente a más antigua.

```sql
SELECT * FROM post WHERE user_id = :user ORDER BY created_at DESC LIMIT 30
```

### `findFeed(User $me, int $limit = 40): Post[]`
La consulta más importante del módulo social. Devuelve el **feed** del usuario: sus propios posts más los posts de los usuarios a quienes sigue con estado `accepted`.

```sql
SELECT p.*
FROM post p
LEFT JOIN follow f ON f.follower_id = :me AND f.following_id = p.user_id AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

La clave está en el `LEFT JOIN` con la tabla `follow`: si `f.id IS NOT NULL`, significa que `p.user_id` es alguien a quien `:me` sigue.

---

## 3. `FollowRepository`

### `findFollow(User $follower, User $following): ?Follow`
Busca si existe alguna relación de seguimiento (en cualquier estado) entre dos usuarios. Usado para mostrar el estado del botón "Seguir" en perfiles.

### `countFollowers(User $user): int`
Cuenta cuántos usuarios siguen a `$user` con estado `accepted`.

### `countFollowing(User $user): int`
Cuenta a cuántos usuarios sigue `$user` con estado `accepted`.

### `findFollowers(User $user): Follow[]`
Lista todos los seguidores aceptados de un usuario, ordenados por fecha descendente.

### `findFollowing(User $user): Follow[]`
Lista todos los usuarios que sigue `$user`, ordenados por fecha descendente.

### `findIncomingRequests(User $user): Follow[]`
Lista las solicitudes de seguimiento pendientes recibidas por un usuario con cuenta privada.

### `countIncomingRequests(User $user): int`
Cuenta las solicitudes de seguimiento pendientes entrantes.

---

## 4. `NotificationRepository`

### `findForUser(User $user, int $limit = 30): Notification[]`
Devuelve las notificaciones de las últimas **72 horas** del usuario, ordenadas de más reciente a más antigua. Usado por `GET /api/notifications`.

```sql
SELECT * FROM notification
WHERE recipient_id = :user AND created_at >= NOW() - INTERVAL 72 HOUR
ORDER BY created_at DESC
LIMIT 30
```

### `findAllForUser(User $user, int $limit = 100): Notification[]`
Historial completo sin límite temporal. Usado por `GET /api/notifications/history`.

### `countUnread(User $user): int`
Cuenta las notificaciones no leídas del usuario. Usado para mostrar el badge rojo en el icono de notificaciones.

```sql
SELECT COUNT(id) FROM notification WHERE recipient_id = :user AND is_read = 0
```

### `markAllRead(User $user): void`
Marca todas las notificaciones no leídas como leídas en una sola operación `UPDATE` (sin cargar cada entidad individualmente), lo que es mucho más eficiente.

```sql
UPDATE notification SET is_read = 1 WHERE recipient_id = :user AND is_read = 0
```

### `deleteByRefIdAndType(User $recipient, string $type, int $refId): void`
Elimina notificaciones específicas por tipo y ID de referencia. Se usa al procesar solicitudes de seguimiento o de club: una vez que el usuario acepta/rechaza, la notificación de solicitud se elimina.

---

## 5. `BookRepository`

Repositorio básico sin métodos personalizados. Usa `findOneBy(['externalId' => ..., 'externalSource' => 'google_books'])` para comprobar si un libro ya está importado.

---

## 6. `ShelfRepository`

Repositorio básico. Usa `findBy(['user' => $user])` para obtener las estanterías de un usuario.

---

## 7. `ShelfBookRepository`

Repositorio básico. Usa `findOneBy(['shelf' => $shelf, 'book' => $book])` para comprobar si un libro ya está en una estantería.

---

## 8. `ClubRepository`

Repositorio básico. Usa `findBy([], ['id' => 'DESC'])` para listar todos los clubes.

---

## 9. `ClubMemberRepository`

Repositorio básico. Usa `findBy(['club' => $club, 'role' => 'admin'])` para obtener los administradores de un club y `findBy(['user' => $user, 'role' => 'admin'])` para los clubs donde el usuario es admin.

---

## 10. `ClubJoinRequestRepository`

Repositorio básico. Consultas frecuentes:
- `findBy(['club' => $club, 'status' => 'pending'])` — solicitudes pendientes de un club.
- `findBy(['user' => $user])` — solicitudes enviadas por el usuario.

---

## 11. `ClubChatRepository` y `ClubChatMessageRepository`

Repositorios básicos. Las consultas de mensajes aprovechan el índice compuesto `(chat_id, created_at)` definido en la entidad para recuperar mensajes de un hilo de forma eficiente.

---

## 12. `PostLikeRepository`

### Métodos destacados:
- `findByPostAndUser(Post $post, User $user): ?PostLike` — comprueba si el usuario ya dio like.
- `countByPost(Post $post): int` — cuenta el total de likes de una publicación.

---

## 13. `PostCommentRepository`

### `findByPost(Post $post): PostComment[]`
Devuelve los comentarios de una publicación ordenados por fecha ascendente (los más antiguos primero).

---

## 14. `ReadingProgressRepository`

Repositorio básico. Usa `findBy(['user' => $user])` para listar el progreso del usuario.

---

## 15. `BookReviewRepository`

Repositorio básico. Usa `findBy(['book' => $book])` para obtener todas las reseñas de un libro y calcular estadísticas de puntuación.

---

## Patrón QueryBuilder

Los repositorios que tienen consultas complejas usan el `QueryBuilder` de Doctrine:

```php
return $this->createQueryBuilder('alias')
    ->where('alias.campo = :valor')
    ->setParameter('valor', $miValor)
    ->orderBy('alias.fecha', 'DESC')
    ->setMaxResults(40)
    ->getQuery()
    ->getResult();
```

- `createQueryBuilder('alias')`: crea el builder con el alias para la entidad principal.
- `->where()` / `->andWhere()`: condiciones de filtrado.
- `->setParameter()`: previene SQL injection al no interpolar valores directamente.
- `->orderBy()`: ordenación de resultados.
- `->setMaxResults()`: límite de resultados (equivalente a `LIMIT`).
- `->getQuery()->getResult()`: ejecuta y devuelve array de entidades.
- `->getQuery()->getSingleScalarResult()`: para consultas `COUNT` que devuelven un número.
- `->getQuery()->execute()`: para `UPDATE` y `DELETE` masivos.
