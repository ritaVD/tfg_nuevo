# 04 — Controladores API

Todos los controladores de la API se encuentran en `src/Controller/Api/`. Cada uno extiende `AbstractController` de Symfony y devuelve exclusivamente respuestas en formato JSON. Las rutas se definen con el atributo PHP `#[Route]`.

---

## Convenciones generales

- **Autenticación:** Los endpoints protegidos llaman a `$this->denyAccessUnlessGranted('ROLE_USER')` al inicio. Si el usuario no tiene sesión activa, Symfony devuelve automáticamente `401 Unauthorized`.
- **Autorización:** Se comprueba dentro del propio controlador (ej: verificar que el recurso pertenece al usuario antes de modificarlo).
- **Respuestas de error:** Siempre JSON con clave `error` y el código HTTP correspondiente.
- **Respuestas de éxito:** JSON con los datos del recurso. Las creaciones devuelven `201 Created`.
- **Eliminaciones:** Devuelven `204 No Content` (sin cuerpo).

---

## 1. `AuthApiController` — Autenticación

**Prefijo de ruta:** `/api/auth`

Gestiona el registro, la consulta del usuario actual y el cierre de sesión.

### `GET /api/auth/me`
Devuelve los datos básicos del usuario con sesión activa.

**Sin autenticación:** devuelve `401`.

**Respuesta (200):**
```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "displayName": "MiNombre",
  "avatar": "abc123.jpg",
  "roles": ["ROLE_USER"]
}
```

---

### `POST /api/auth/register`
Registra un nuevo usuario.

**Body JSON:**
```json
{ "email": "usuario@ejemplo.com", "password": "miClave123", "displayName": "MiNombre" }
```

**Validaciones:**
- `email` y `password` son obligatorios.
- El email debe tener formato válido.
- La contraseña debe tener al menos 6 caracteres.
- No puede existir otra cuenta con el mismo email.
- Si `displayName` ya está en uso, se le añade un sufijo numérico automáticamente.

**Respuesta (201):**
```json
{ "id": 42, "email": "usuario@ejemplo.com" }
```

---

### `POST /api/auth/logout`
Invalida la sesión actual del usuario.

**Respuesta (200):**
```json
{ "status": "logged_out" }
```

---

## 2. `UserApiController` — Perfil de usuario

**Prefijo de ruta:** `/api`

Gestiona el perfil del usuario autenticado y la consulta de perfiles públicos.

### `GET /api/profile`
Devuelve el perfil completo del usuario autenticado (email, bio, avatar, estadísticas, estanterías, clubes).

### `PUT /api/profile`
Actualiza `displayName` y/o `bio`.

**Validaciones de displayName:** mínimo 3 caracteres, solo letras/números/puntos/guiones, debe ser único.

### `POST /api/profile/avatar`
Sube una imagen de avatar. Recibe un archivo en el campo `avatar` (`multipart/form-data`). Guarda el archivo en `public/uploads/avatars/`.

### `PUT /api/profile/password`
Cambia la contraseña. Requiere enviar la contraseña actual para verificar identidad.

**Body JSON:**
```json
{ "currentPassword": "antigua", "newPassword": "nuevaClave123" }
```

### `PUT /api/profile/privacy`
Configura la privacidad del perfil.

**Body JSON:**
```json
{ "isPrivate": true, "shelvesPublic": false, "clubsPublic": true }
```

### `GET /api/users/search?q=...`
Busca usuarios por `displayName` (mínimo 2 caracteres). Devuelve lista con estado de seguimiento respecto al usuario actual.

### `GET /api/users/{id}`
Devuelve el perfil público de un usuario. Respeta su configuración de privacidad: si `shelvesPublic` es `false`, no incluye las estanterías; si `clubsPublic` es `false`, no incluye los clubes.

### `GET /api/my-requests`
Lista las solicitudes de unión a clubes enviadas por el usuario actual.

### `GET /api/admin-requests`
Lista las solicitudes pendientes en los clubes donde el usuario es administrador.

---

## 3. `PostApiController` — Publicaciones

**Prefijo de ruta:** `/api`

Gestiona el feed social de publicaciones con imágenes.

### `GET /api/posts`
Devuelve el **feed** del usuario: sus propias publicaciones y las de los usuarios a los que sigue (máx. 40 publicaciones ordenadas por fecha descendente).

**Respuesta:** array de posts con `id`, `imagePath`, `description`, `createdAt`, `likes`, `liked` (bool), `commentCount`, y datos del autor.

### `GET /api/users/{id}/posts`
Devuelve todas las publicaciones de un usuario específico.

### `POST /api/posts`
Crea una nueva publicación. Recibe `multipart/form-data` con:
- `image`: archivo de imagen (jpg, jpeg, png, gif, webp).
- `description`: texto opcional.

La imagen se guarda en `public/uploads/posts/` con nombre único generado con `uniqid()`.

### `DELETE /api/posts/{id}`
Elimina una publicación. Solo el autor o un `ROLE_ADMIN` pueden eliminarla. También borra el archivo de imagen del disco.

### `POST /api/posts/{id}/like`
Actúa como **toggle**: si el usuario ya dio like lo quita, si no lo dio lo añade. Devuelve el nuevo estado y el total de likes.

### `GET /api/posts/{id}/comments`
Lista todos los comentarios de una publicación.

### `POST /api/posts/{id}/comments`
Añade un comentario. Body JSON: `{ "content": "..." }`.

### `DELETE /api/posts/{id}/comments/{commentId}`
Elimina un comentario. Puede hacerlo el autor del comentario **o** el autor de la publicación.

---

## 4. `ShelfApiController` — Estanterías

**Prefijo de ruta:** `/api/shelves`

Gestiona las colecciones de libros personales del usuario.

### `GET /api/shelves`
Lista las estanterías del usuario (solo `id` y `name`).

### `GET /api/shelves/full`
Lista las estanterías con todos sus libros completos.

### `POST /api/shelves`
Crea una nueva estantería. Body JSON: `{ "name": "..." }`.

### `PATCH /api/shelves/{id}`
Renombra una estantería. Body JSON: `{ "name": "..." }`.

### `DELETE /api/shelves/{id}`
Elimina una estantería y todos sus libros (por `orphanRemoval` en la relación).

### `GET /api/shelves/{id}/books`
Lista los libros de una estantería con su estado y metadatos.

### `POST /api/shelves/{id}/books`
Añade un libro a la estantería. Body JSON: `{ "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }`.

**Lógica de importación automática:** Si el libro no existe en la base de datos local, el backend lo obtiene de la Google Books API y lo guarda antes de añadirlo.

**Estados válidos:** `want_to_read`, `reading`, `read`.

### `PATCH /api/shelves/{id}/books/{bookId}`
Actualiza el estado de lectura de un libro en la estantería.

### `POST /api/shelves/{id}/books/{bookId}/move`
Mueve un libro a otra estantería del mismo usuario. Body JSON: `{ "targetShelfId": 3 }`.

### `DELETE /api/shelves/{id}/books/{bookId}`
Quita un libro de una estantería.

---

## 5. `BookExternalApiController` — Búsqueda de libros

**Prefijo de ruta:** `/api/books`

Actúa como **proxy** hacia la Google Books API para que el frontend no exponga la API key.

### `GET /api/books/search`
Búsqueda avanzada de libros.

**Parámetros de query:**

| Parámetro | Descripción |
|-----------|-------------|
| `q` | Texto libre de búsqueda |
| `title` | Buscar por título |
| `author` | Buscar por autor |
| `isbn` | Buscar por ISBN |
| `subject` | Buscar por categoría/género |
| `publisher` | Buscar por editorial |
| `startIndex` | Paginación (offset) |
| `maxResults` | Número de resultados (max 40) |
| `orderBy` | `relevance` o `newest` |
| `langRestrict` | Filtro por idioma (ej: `es`, `en`) |

---

## 6. `BookReviewApiController` — Reseñas

**Prefijo de ruta:** `/api/books/{externalId}/reviews`

### `GET /api/books/{externalId}/reviews`
Devuelve las reseñas y estadísticas de un libro (media de puntuaciones, distribución por estrellas, reseñas con texto).

### `POST /api/books/{externalId}/reviews`
Crea o actualiza la reseña del usuario para ese libro. Body JSON: `{ "rating": 4, "content": "Muy buen libro..." }`.

Si el usuario ya tenía una reseña, se actualiza (upsert).

---

## 7. `ReadingProgressApiController` — Progreso de lectura

**Prefijo de ruta:** `/api/reading-progress`

### `GET /api/reading-progress`
Lista todos los registros de progreso del usuario.

### `POST /api/reading-progress`
Empieza a rastrear un libro. Body JSON:
```json
{ "externalId": "zyTCAlFPjgYC", "mode": "pages", "totalPages": 350 }
```

### `PATCH /api/reading-progress/{id}`
Actualiza el progreso actual. Body JSON:
```json
{ "currentPage": 125 }
```
o para modo porcentaje:
```json
{ "percent": 35.5 }
```

### `DELETE /api/reading-progress/{id}`
Elimina el registro de progreso de un libro.

---

## 8. `ClubApiController` — Clubes de lectura

**Prefijo de ruta:** `/api/clubs`

Es el controlador más extenso. Gestiona la creación, administración, membresía y libro actual de los clubes.

### `GET /api/clubs`
Lista todos los clubes con nombre, descripción, visibilidad y número de miembros.

### `POST /api/clubs`
Crea un nuevo club. Body JSON:
```json
{ "name": "Club de Fantasía", "description": "...", "visibility": "public" }
```
El creador es añadido automáticamente como miembro con rol `admin`.

### `GET /api/clubs/{id}`
Devuelve los detalles de un club: datos básicos, libro actual y lista de miembros.

### `PATCH /api/clubs/{id}`
Actualiza nombre, descripción o visibilidad. Solo el `admin` del club puede hacerlo.

### `DELETE /api/clubs/{id}`
Elimina el club. Solo el propietario (`owner`) puede hacerlo.

### `POST /api/clubs/{id}/join`
Solicita unirse a un club.
- Si el club es **público**: se añade al usuario directamente como `member`.
- Si el club es **privado**: se crea una `ClubJoinRequest` en estado `pending` y se notifica a los admins.

### `DELETE /api/clubs/{id}/join`
Cancela una solicitud de unión pendiente o abandona el club si ya era miembro.

### `GET /api/clubs/{id}/members`
Lista todos los miembros con su rol y fecha de incorporación.

### `PATCH /api/clubs/{id}/members/{userId}/role`
Cambia el rol de un miembro entre `admin` y `member`. Solo los admins pueden hacerlo.

### `DELETE /api/clubs/{id}/members/{userId}`
Expulsa a un miembro del club. Solo los admins pueden hacerlo.

### `POST /api/clubs/{id}/requests/{reqId}/approve`
Aprueba una solicitud de unión pendiente. El usuario pasa a ser `member` del club.

### `POST /api/clubs/{id}/requests/{reqId}/reject`
Rechaza una solicitud de unión.

### `PATCH /api/clubs/{id}/currentBook`
Establece el libro que el club está leyendo actualmente. Body JSON:
```json
{ "externalId": "zyTCAlFPjgYC", "since": "2026-04-01", "until": "2026-04-30" }
```

---

## 9. `ClubChatApiController` — Hilos de debate

**Prefijo de ruta:** `/api/clubs/{clubId}/chats`

Gestiona los foros de discusión internos de cada club.

### `GET /api/clubs/{clubId}/chats`
Lista todos los hilos de debate del club.

### `POST /api/clubs/{clubId}/chats`
Crea un nuevo hilo. Solo los admins del club pueden crear hilos. Body JSON: `{ "title": "..." }`.

### `PATCH /api/clubs/{clubId}/chats/{chatId}`
Abre o cierra un hilo. Solo admins. Body JSON: `{ "isOpen": false }`.

### `GET /api/clubs/{clubId}/chats/{chatId}/messages`
Lista los mensajes de un hilo ordenados por fecha.

### `POST /api/clubs/{clubId}/chats/{chatId}/messages`
Publica un mensaje en el hilo. Cualquier miembro del club puede hacerlo. Solo en hilos abiertos.

Body JSON: `{ "content": "..." }`.

### `DELETE /api/clubs/{clubId}/chats/{chatId}/messages/{msgId}`
Elimina un mensaje. Solo el autor del mensaje o un admin del club.

---

## 10. `FollowApiController` — Seguimientos

**Prefijo de ruta:** `/api/users/{id}`

### `POST /api/users/{id}/follow`
Sigue a un usuario.
- Si su perfil es **público**: la relación se crea con estado `accepted`.
- Si su perfil es **privado**: se crea con estado `pending` y se envía una notificación de solicitud.

### `DELETE /api/users/{id}/follow`
Deja de seguir a un usuario o cancela una solicitud pendiente.

---

## 11. `NotificationApiController` — Notificaciones

**Prefijo de ruta:** `/api`

### `GET /api/notifications`
Devuelve las 30 notificaciones más recientes del usuario, con el número de no leídas.

### `GET /api/notifications/history`
Historial completo de notificaciones (100 últimas).

### `POST /api/notifications/read-all`
Marca todas las notificaciones como leídas.

### `POST /api/notifications/follow-requests/{followId}/accept`
Acepta una solicitud de seguimiento pendiente. Cambia el estado del `Follow` a `accepted` y envía notificación al solicitante.

### `POST /api/notifications/follow-requests/{followId}/reject`
Rechaza una solicitud de seguimiento eliminando el registro `Follow`.

### `POST /api/notifications/club-requests/{reqId}/approve`
Aprueba una solicitud de unión a club desde las notificaciones (mismo efecto que el endpoint del club).

### `POST /api/notifications/club-requests/{reqId}/reject`
Rechaza una solicitud de unión a club.

---

## 12. `AdminApiController` — Panel de administración

**Prefijo de ruta:** `/api/admin`

**Todos los endpoints requieren `ROLE_ADMIN`.** Si el usuario no tiene este rol, Symfony devuelve `403 Forbidden`.

### `GET /api/admin/stats`
Estadísticas globales de la plataforma.
```json
{ "users": 154, "clubs": 23, "posts": 891 }
```

### `GET /api/admin/users`
Lista todos los usuarios con email, roles y estado de verificación.

### `PATCH /api/admin/users/{id}/role`
Promueve o degrada un usuario a/de admin. No puede aplicarse al propio admin.

Body JSON: `{ "isAdmin": true }`.

### `DELETE /api/admin/users/{id}`
Elimina un usuario. No puede eliminarse a sí mismo.

### `GET /api/admin/clubs`
Lista todos los clubes con su propietario y número de miembros.

### `DELETE /api/admin/clubs/{id}`
Elimina cualquier club de la plataforma.

### `GET /api/admin/posts`
Lista los 100 posts más recientes con datos de su autor.

### `DELETE /api/admin/posts/{id}`
Elimina cualquier post y su imagen del disco.

---

## Resumen de endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/login` | — | Login con email/password |
| GET | `/api/auth/me` | — | Usuario actual |
| POST | `/api/auth/register` | — | Registro |
| POST | `/api/auth/logout` | ✓ | Cerrar sesión |
| GET | `/api/profile` | ✓ | Mi perfil completo |
| PUT | `/api/profile` | ✓ | Editar perfil |
| POST | `/api/profile/avatar` | ✓ | Subir avatar |
| PUT | `/api/profile/password` | ✓ | Cambiar contraseña |
| PUT | `/api/profile/privacy` | ✓ | Configurar privacidad |
| GET | `/api/users/search` | — | Buscar usuarios |
| GET | `/api/users/{id}` | — | Perfil público |
| GET | `/api/posts` | ✓ | Feed |
| POST | `/api/posts` | ✓ | Crear post |
| DELETE | `/api/posts/{id}` | ✓ | Eliminar post |
| POST | `/api/posts/{id}/like` | ✓ | Toggle like |
| GET | `/api/posts/{id}/comments` | — | Ver comentarios |
| POST | `/api/posts/{id}/comments` | ✓ | Añadir comentario |
| DELETE | `/api/posts/{id}/comments/{cid}` | ✓ | Borrar comentario |
| GET | `/api/shelves` | ✓ | Mis estanterías |
| POST | `/api/shelves` | ✓ | Crear estantería |
| POST | `/api/shelves/{id}/books` | ✓ | Añadir libro |
| DELETE | `/api/shelves/{id}/books/{bid}` | ✓ | Quitar libro |
| GET | `/api/books/search` | — | Buscar libros (Google) |
| GET | `/api/books/{eid}/reviews` | — | Ver reseñas |
| POST | `/api/books/{eid}/reviews` | ✓ | Crear/actualizar reseña |
| GET | `/api/reading-progress` | ✓ | Mi progreso |
| POST | `/api/reading-progress` | ✓ | Iniciar seguimiento |
| PATCH | `/api/reading-progress/{id}` | ✓ | Actualizar progreso |
| GET | `/api/clubs` | — | Lista de clubes |
| POST | `/api/clubs` | ✓ | Crear club |
| POST | `/api/clubs/{id}/join` | ✓ | Unirse/solicitar |
| GET | `/api/notifications` | ✓ | Notificaciones |
| POST | `/api/notifications/read-all` | ✓ | Marcar leídas |
| GET | `/api/admin/stats` | ADMIN | Estadísticas |
| GET | `/api/admin/users` | ADMIN | Gestionar usuarios |
