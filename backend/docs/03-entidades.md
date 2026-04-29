# 03 — Modelo de datos: Entidades

Las entidades son las clases PHP que representan las tablas de la base de datos. Doctrine ORM mapea automáticamente cada clase a una tabla y cada propiedad a una columna. Todas las entidades se encuentran en `src/Entity/`.

---

## Diagrama de relaciones (simplificado)

```
User ──────────────────────────────────────────────────────┐
  │                                                         │
  ├── (1:N) Shelf ── (1:N) ShelfBook ── (N:1) Book          │
  │                                                         │
  ├── (1:N) ReadingProgress ── (N:1) Book                   │
  │                                                         │
  ├── (1:N) BookReview ── (N:1) Book                        │
  │                                                         │
  ├── (1:N) Post ── (1:N) PostLike                          │
  │            └── (1:N) PostComment                        │
  │                                                         │
  ├── (N:M via Follow) User                                 │
  │                                                         │
  ├── (1:N) Club (owner) ─── (1:N) ClubMember ◄────────────┘
  │              └── (1:N) ClubJoinRequest
  │              └── (1:N) ClubChat ── (1:N) ClubChatMessage
  │              └── (N:1) Book (currentBook)
  │
  └── (1:N) Notification
```

---

## 1. `User` — Usuario

**Tabla:** `user`

Entidad central del sistema. Representa a cada persona registrada en la plataforma. Implementa `UserInterface` y `PasswordAuthenticatedUserInterface` de Symfony para integrarse con el sistema de seguridad.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único autoincremental |
| `email` | string(180) | Email del usuario — único en la BD |
| `displayName` | string(80) | Nombre visible público — único en la BD |
| `password` | string | Contraseña hasheada (bcrypt/argon2) — nunca en texto plano |
| `roles` | array (JSON) | Lista de roles: siempre incluye `ROLE_USER`; opcionalmente `ROLE_ADMIN` |
| `bio` | string(255)? | Descripción opcional del perfil |
| `avatar` | string(255)? | URL o path del avatar del usuario |
| `isVerified` | bool | Si ha verificado su email (default: `false`) |
| `isPrivate` | bool | Perfil privado: los seguidores deben ser aprobados (default: `false`) |
| `shelvesPublic` | bool | Si sus estanterías son visibles para otros (default: `true`) |
| `clubsPublic` | bool | Si sus clubes son visibles para otros (default: `true`) |
| `isBanned` | bool | Si el usuario está suspendido y no puede iniciar sesión (default: `false`) |

**Relaciones:**
- → `Shelf` (1:N): estanterías propias
- → `Club` (1:N): clubes que ha creado (como propietario)
- → `ClubMember` (1:N): membresías en clubes ajenos
- → `ClubJoinRequest` (1:N): solicitudes de unión enviadas
- → `ClubChat` (1:N): hilos de chat creados
- → `ClubChatMessage` (1:N): mensajes enviados

**Nota de seguridad:** El método `__serialize()` almacena en sesión un hash CRC32c de la contraseña en lugar del hash completo, evitando que el hash bcrypt quede expuesto en los datos de sesión.

---

## 2. `Book` — Libro

**Tabla:** `book`

Almacena los metadatos de un libro. Los libros se obtienen originalmente desde la Google Books API y se guardan localmente para no depender siempre de la API externa.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador interno |
| `externalId` | string? | ID del libro en Google Books (ej: `"OL7353617M"`) |
| `externalSource` | string? | Fuente externa (actualmente siempre `"google_books"`) |
| `title` | string(255) | Título del libro |
| `authors` | array? | Lista de autores en JSON |
| `isbn10` | string? | ISBN-10 |
| `isbn13` | string? | ISBN-13 |
| `coverUrl` | text? | URL de la portada |
| `description` | text? | Sinopsis / descripción |
| `publisher` | string? | Editorial |
| `publishedDate` | string? | Fecha de publicación (como string por variabilidad de formato) |
| `language` | string? | Código de idioma (ej: `"es"`, `"en"`) |
| `pageCount` | int? | Número de páginas |
| `categories` | array? | Categorías/géneros en JSON |
| `createdAt` | DateTimeImmutable | Fecha de creación en nuestra BD |
| `updatedAt` | DateTimeImmutable | Fecha de última actualización |

**Lifecycle callbacks** (con `#[ORM\HasLifecycleCallbacks]`):
- `#[ORM\PrePersist]` → establece `createdAt` y `updatedAt` automáticamente al crear.
- `#[ORM\PreUpdate]` → actualiza `updatedAt` automáticamente al modificar.

---

## 3. `Shelf` — Estantería

**Tabla:** `shelf`

Una colección de libros perteneciente a un usuario. Un usuario puede tener múltiples estanterías con nombres personalizados (ej: "Leídos", "Por leer", "Favoritos").

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Propietario de la estantería |
| `name` | string(255) | Nombre de la estantería |
| `orderIndex` | int | Posición en la lista (para ordenar estanterías) |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `updatedAt` | DateTimeImmutable | Fecha de última modificación |

**Relaciones:**
- → `ShelfBook` (1:N): libros contenidos en esta estantería

---

## 4. `ShelfBook` — Libro en estantería

**Tabla:** `shelf_book`

Tabla de unión entre `Shelf` y `Book` con metadatos adicionales. Permite que el mismo libro aparezca en varias estanterías (de distintos usuarios) con estado diferente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `shelf` | FK → Shelf | Estantería que contiene el libro |
| `book` | FK → Book | Libro añadido |
| `orderIndex` | int | Posición del libro dentro de la estantería |
| `status` | string(20)? | Estado de lectura: `"reading"`, `"read"`, `"want_to_read"`, etc. |
| `addedAt` | DateTimeImmutable | Cuándo se añadió el libro a la estantería |

**Restricción única:** `(shelf_id, book_id)` — un libro no puede estar dos veces en la misma estantería.

---

## 5. `ReadingProgress` — Progreso de lectura

**Tabla:** `reading_progress`

Permite al usuario llevar un seguimiento de su avance en la lectura de un libro, bien por páginas o por porcentaje.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Usuario que lleva el seguimiento |
| `book` | FK → Book | Libro del que se lleva seguimiento |
| `mode` | string | `"pages"` o `"percent"` |
| `currentPage` | int? | Página actual (si mode = "pages") |
| `totalPages` | int? | Total de páginas del libro |
| `percent` | float? | Porcentaje completado (si mode = "percent") |
| `startedAt` | DateTimeImmutable | Cuándo empezó a registrar el progreso |
| `updatedAt` | DateTimeImmutable | Última actualización del progreso |

**Restricción única:** `(user_id, book_id)` — solo un registro de progreso por usuario y libro.

---

## 6. `BookReview` — Reseña de libro

**Tabla:** `book_review`

Una reseña y puntuación que un usuario escribe sobre un libro.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Autor de la reseña |
| `book` | FK → Book | Libro reseñado |
| `rating` | int | Puntuación del 1 al 5 |
| `content` | text? | Texto de la reseña (opcional) |
| `createdAt` | DateTimeImmutable | Fecha de la reseña |

**Restricción única:** `(user_id, book_id)` — una sola reseña por usuario y libro.

---

## 7. `Post` — Publicación

**Tabla:** `post`

Una publicación en el feed social de la plataforma. Contiene una imagen y una descripción opcional, similar a una red social de fotos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Autor de la publicación |
| `imagePath` | string | Ruta de la imagen subida (en `public/uploads/posts/`) |
| `description` | text? | Descripción o comentario de la publicación |
| `createdAt` | DateTimeImmutable | Fecha de creación |

**Relaciones:**
- → `PostLike` (1:N): likes recibidos
- → `PostComment` (1:N): comentarios recibidos

---

## 8. `PostLike` — Like en publicación

**Tabla:** `post_like`

Registra que un usuario ha dado like a una publicación.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `post` | FK → Post | Publicación likeada |
| `user` | FK → User | Usuario que dio el like |
| `createdAt` | DateTimeImmutable | Cuándo se dio el like |

**Restricción única:** `(post_id, user_id)` — un usuario solo puede dar like una vez por publicación.

---

## 9. `PostComment` — Comentario en publicación

**Tabla:** `post_comment`

Un comentario escrito por un usuario en una publicación.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `post` | FK → Post | Publicación comentada |
| `user` | FK → User | Autor del comentario |
| `content` | text | Texto del comentario |
| `createdAt` | DateTimeImmutable | Fecha del comentario |

---

## 10. `Follow` — Seguimiento entre usuarios

**Tabla:** `follow`

Representa la relación de seguimiento entre dos usuarios. El estado puede ser `pending` (esperando aprobación, en cuentas privadas) o `accepted` (seguimiento activo).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `follower` | FK → User | Usuario que sigue (el que envía la solicitud) |
| `following` | FK → User | Usuario seguido (el que recibe la solicitud) |
| `status` | string(10) | `"pending"` o `"accepted"` (default: `"accepted"`) |
| `createdAt` | DateTimeImmutable | Fecha de creación de la relación |

**Restricción única:** `(follower_id, following_id)` — no se puede seguir dos veces a la misma persona.

**Lógica de estados:**
- Si el perfil del usuario seguido es **público** → el estado se crea directamente como `accepted`.
- Si el perfil es **privado** → el estado se crea como `pending` y el usuario debe aprobar la solicitud desde las notificaciones.

**Métodos de negocio:** `accept()`, `isPending()`, `isAccepted()`.

---

## 11. `Club` — Club de lectura

**Tabla:** `club`

Un grupo de usuarios organizados en torno a la lectura de libros. Tiene un propietario, miembros, y puede tener un libro de lectura activo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `owner` | FK → User | Usuario que creó y administra el club |
| `name` | string(255) | Nombre del club |
| `description` | text? | Descripción del club |
| `visibility` | string(10) | `"public"` (cualquiera puede unirse) o `"private"` (requiere solicitud) |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `updatedAt` | DateTimeImmutable | Fecha de última modificación |
| `currentBook` | FK → Book? | Libro que el club está leyendo actualmente |
| `currentBookSince` | DateTimeImmutable? | Desde cuándo se lee el libro actual |
| `currentBookUntil` | DateTimeImmutable? | Fecha objetivo para terminar el libro |

**Relaciones:**
- → `ClubMember` (1:N, orphanRemoval): miembros del club
- → `ClubJoinRequest` (1:N, orphanRemoval): solicitudes de unión
- → `ClubChat` (1:N, orphanRemoval): hilos de discusión
- → `Book` (N:1): libro actual (se pone a NULL si el libro es eliminado)

---

## 12. `ClubMember` — Miembro de club

**Tabla:** `club_member`

Representa la membresía de un usuario en un club, con un rol específico.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `club` | FK → Club | Club al que pertenece |
| `user` | FK → User | Miembro del club |
| `role` | string | `"admin"` (gestiona el club) o `"member"` (miembro normal) |
| `joinedAt` | DateTimeImmutable | Fecha de incorporación al club |

**Restricción única:** `(club_id, user_id)` — un usuario solo puede ser miembro una vez por club.

---

## 13. `ClubJoinRequest` — Solicitud de unión a club

**Tabla:** `club_join_request`

Cuando un club es privado, los usuarios envían una solicitud que debe ser aprobada o rechazada por un administrador.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `club` | FK → Club | Club al que se solicita unirse |
| `user` | FK → User | Usuario que envía la solicitud |
| `resolvedBy` | FK → User? | Administrador que procesó la solicitud |
| `status` | string | `"pending"`, `"approved"` o `"rejected"` |
| `requestedAt` | DateTimeImmutable | Cuándo se envió la solicitud |
| `resolvedAt` | DateTimeImmutable? | Cuándo fue procesada |

**Restricción única:** `(club_id, user_id)` — solo una solicitud activa por usuario y club.

---

## 14. `ClubChat` — Hilo de debate en club

**Tabla:** `club_chat`

Un hilo de discusión dentro de un club. Solo los administradores pueden crear hilos y abrirlos/cerrarlos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `club` | FK → Club | Club al que pertenece el hilo |
| `createdBy` | FK → User | Administrador que creó el hilo |
| `title` | string | Título del hilo (ej: "¿Qué os parece el capítulo 5?") |
| `isOpen` | bool | Si el hilo está abierto a nuevos mensajes (default: `true`) |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `closedAt` | DateTimeImmutable? | Fecha en que fue cerrado |

**Relaciones:**
- → `ClubChatMessage` (1:N): mensajes del hilo

---

## 15. `ClubChatMessage` — Mensaje en hilo de debate

**Tabla:** `club_chat_message`

Un mensaje escrito por un miembro del club dentro de un hilo de debate.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `chat` | FK → ClubChat | Hilo al que pertenece el mensaje |
| `user` | FK → User | Autor del mensaje |
| `content` | text | Contenido del mensaje |
| `createdAt` | DateTimeImmutable | Fecha del mensaje |

**Índice de base de datos:** `(chat_id, created_at)` para acelerar la consulta de mensajes por hilo ordenados cronológicamente.

---

## 16. `Notification` — Notificación

**Tabla:** `notification`

Registra los eventos que generan alertas para los usuarios (likes, comentarios, solicitudes de seguimiento, actividad en clubes).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `recipient` | FK → User | Usuario que recibe la notificación |
| `actor` | FK → User | Usuario que generó la acción |
| `type` | string(30) | Tipo de notificación (ver tabla de tipos) |
| `post` | FK → Post? | Publicación relacionada (en likes/comentarios) |
| `club` | FK → Club? | Club relacionado (en solicitudes de club) |
| `refId` | int? | ID auxiliar: `Follow.id` (follow_request) o `ClubJoinRequest.id` (club_request) |
| `isRead` | bool | Si el usuario ya ha visto la notificación (default: `false`) |
| `createdAt` | DateTimeImmutable | Cuándo se generó |

**Tipos de notificación:**

| Constante | Valor | Cuándo se genera |
|-----------|-------|-----------------|
| `TYPE_FOLLOW` | `"follow"` | Alguien empieza a seguirte (cuenta pública) |
| `TYPE_FOLLOW_REQUEST` | `"follow_request"` | Alguien solicita seguirte (cuenta privada) |
| `TYPE_FOLLOW_ACCEPTED` | `"follow_accepted"` | Aceptaron tu solicitud de seguimiento |
| `TYPE_LIKE` | `"like"` | Alguien le da like a tu publicación |
| `TYPE_COMMENT` | `"comment"` | Alguien comenta tu publicación |
| `TYPE_CLUB_REQUEST` | `"club_request"` | Alguien solicita unirse a tu club (notif. para admin) |
| `TYPE_CLUB_APPROVED` | `"club_approved"` | Tu solicitud de unión a un club fue aprobada |
| `TYPE_CLUB_REJECTED` | `"club_rejected"` | Tu solicitud de unión a un club fue rechazada |

**Método de negocio:** `markRead()` — marca la notificación como leída.

---

## Resumen de restricciones únicas

| Entidad | Columnas únicas | Propósito |
|---------|-----------------|-----------|
| `User` | `email` | No puede haber dos cuentas con el mismo email |
| `User` | `displayName` | No puede haber dos usuarios con el mismo nombre visible |
| `ShelfBook` | `(shelf_id, book_id)` | Un libro no puede estar dos veces en la misma estantería |
| `ReadingProgress` | `(user_id, book_id)` | Un solo registro de progreso por libro y usuario |
| `BookReview` | `(user_id, book_id)` | Una sola reseña por libro y usuario |
| `PostLike` | `(post_id, user_id)` | No se puede dar like dos veces a la misma publicación |
| `Follow` | `(follower_id, following_id)` | No se puede seguir dos veces a la misma persona |
| `ClubMember` | `(club_id, user_id)` | Un usuario solo puede ser miembro una vez por club |
| `ClubJoinRequest` | `(club_id, user_id)` | Solo una solicitud pendiente por usuario y club |
