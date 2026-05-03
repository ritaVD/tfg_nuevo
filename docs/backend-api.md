# API REST — Backend (Symfony)

Base URL: `http://localhost:8000`  
Autenticación: sesión HTTP (cookie `PHPSESSID`). El login lo gestiona Symfony Security en `POST /api/login`.  
Todos los endpoints que requieren autenticación devuelven `401` si la sesión no existe.

---

## Índice

1. [Autenticación](#1-autenticación)
2. [Perfil de usuario](#2-perfil-de-usuario)
3. [Libros (Google Books)](#3-libros-google-books)
4. [Reseñas de libros](#4-reseñas-de-libros)
5. [Estanterías](#5-estanterías)
6. [Progreso de lectura](#6-progreso-de-lectura)
7. [Clubs](#7-clubs)
8. [Chat de clubs](#8-chat-de-clubs)
9. [Social — Posts](#9-social--posts)
10. [Social — Follows](#10-social--follows)
11. [Notificaciones](#11-notificaciones)
12. [Panel de administración](#12-panel-de-administración)

---

## 1. Autenticación

### `GET /api/auth/me`
Devuelve el usuario de la sesión actual.

**Auth:** requerida  
**Response 200:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "displayName": "usuario",
  "avatar": "abc123.jpg",
  "roles": ["ROLE_USER"]
}
```
**Response 401:** `{ "error": "No autenticado" }`

---

### `POST /api/login`
Inicio de sesión gestionado por Symfony Security vía `json_login` (no hay controlador PHP propio). La ruta está definida en `config/routes.yaml` como `api_login` apuntando a `/api/login`.

**Body JSON:**
```json
{ "email": "user@example.com", "password": "123456" }
```
**Response 200:** mismo objeto que `/api/auth/me`  
**Response 401:** credenciales incorrectas

---

### `POST /api/auth/register`
Registro de nuevo usuario.

**Body JSON:**
```json
{
  "email": "user@example.com",
  "password": "123456",
  "displayName": "miusuario"
}
```
- `displayName` es opcional; si se omite se genera a partir del email.
- Si el `displayName` ya existe se añade un sufijo numérico automáticamente.

**Response 201:**
```json
{ "id": 1, "email": "user@example.com" }
```
**Errores:**
| Código | Mensaje |
|--------|---------|
| 400 | `email y password son obligatorios` |
| 400 | `El email no es válido` |
| 400 | `La contraseña debe tener al menos 6 caracteres` |
| 409 | `Ya existe una cuenta con ese email` |

---

### `POST /api/auth/logout`
Invalida la sesión actual.

**Response 200:** `{ "status": "logged_out" }`

---

## 2. Perfil de usuario

### `GET /api/profile`
Perfil completo del usuario autenticado.

**Auth:** requerida  
**Response 200:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "displayName": "usuario",
  "bio": "Texto bio",
  "avatar": "abc123.jpg",
  "shelvesPublic": true,
  "clubsPublic": true,
  "isPrivate": false,
  "followers": 10,
  "following": 5,
  "shelves": [{ "id": 1, "name": "Favoritos" }],
  "clubs": [{ "id": 2, "name": "Club Sci-Fi", "visibility": "public", "role": "member" }]
}
```

---

### `PUT /api/profile`
Editar displayName y/o bio.

**Auth:** requerida  
**Body JSON:**
```json
{ "displayName": "nuevo_nombre", "bio": "Nueva bio" }
```
Reglas de `displayName`: mínimo 3 caracteres, solo `[a-zA-Z0-9_.\-]`, único.

**Response 200:** mismo objeto que `GET /api/profile`  
**Errores:** 400 (vacío/corto/formato), 409 (ya en uso)

---

### `POST /api/profile/avatar`
Subir avatar. Envío como `multipart/form-data`.

**Auth:** requerida  
**Campo:** `avatar` (archivo imagen)  
**Response 200:** `{ "avatar": "abc123.jpg" }`  
El archivo se guarda en `public/uploads/avatars/`.

---

### `PUT /api/profile/password`
Cambiar contraseña.

**Auth:** requerida  
**Body JSON:**
```json
{ "currentPassword": "anterior", "newPassword": "nueva123" }
```
**Response 200:** `{ "status": "password_updated" }`  
**Errores:** 400 (contraseña actual incorrecta, nueva muy corta)

---

### `PUT /api/profile/privacy`
Configurar visibilidad del perfil.

**Auth:** requerida  
**Body JSON** (todos opcionales):
```json
{
  "shelvesPublic": true,
  "clubsPublic": false,
  "isPrivate": true
}
```
`isPrivate: true` requiere que los nuevos seguidores sean aprobados manualmente.

**Response 200:** mismo objeto que `GET /api/profile`

---

### `GET /api/my-requests`
Solicitudes de ingreso a clubs enviadas por el usuario autenticado.

**Auth:** requerida  
**Response 200:**
```json
[
  {
    "id": 3,
    "status": "pending",
    "requestedAt": "2026-04-15T10:00:00+00:00",
    "club": { "id": 5, "name": "Club Fantasía", "visibility": "private" }
  }
]
```

---

### `GET /api/admin-requests`
Solicitudes pendientes en los clubs donde el usuario es admin.

**Auth:** requerida  
**Response 200:** array de solicitudes con datos del usuario solicitante y del club.

---

### `GET /api/users/search?q={texto}`
Buscar usuarios por `displayName`. Mínimo 2 caracteres.

**Auth:** opcional (afecta al campo `followStatus`)  
**Response 200:**
```json
[
  {
    "id": 2,
    "displayName": "otrousuario",
    "avatar": "xyz.jpg",
    "bio": "...",
    "followers": 4,
    "followStatus": "none",
    "isMe": false
  }
]
```
`followStatus`: `none` | `pending` | `accepted`

---

### `GET /api/users/{id}`
Perfil público de un usuario. Respeta la configuración de privacidad.

**Auth:** opcional  
**Response 200:**
```json
{
  "id": 2,
  "displayName": "otrousuario",
  "bio": "...",
  "avatar": "xyz.jpg",
  "followers": 4,
  "following": 2,
  "followStatus": "none",
  "isFollowing": false,
  "shelves": null,
  "clubs": null
}
```
`shelves` y `clubs` son `null` si el usuario tiene esa información privada.  
**Response 404:** usuario no encontrado.

---

## 3. Libros (Google Books)

Los libros se buscan en la API de Google Books y se cachean en la BD local cuando se añaden a estanterías o se usa como libro del mes.

### `GET /api/books/search`
Búsqueda avanzada de libros.

**Query params** (al menos uno obligatorio):
| Param | Descripción |
|-------|-------------|
| `q` | Texto libre |
| `title` | Título |
| `author` | Autor |
| `isbn` | ISBN-10 o ISBN-13 |
| `subject` | Categoría/materia |
| `publisher` | Editorial |
| `page` | Página (default: 1) |
| `limit` | Resultados por página (1-40, default: 20) |
| `orderBy` | `relevance` (default) o `newest` |
| `lang` | Código de idioma (ej: `es`, `en`) |
| `printType` | `books` (default), `magazines`, `all` |
| `filter` | `partial`, `full`, `free-ebooks`, `paid-ebooks`, `ebooks` |

**Ordenación interna:** los resultados se priorizan por popularidad (libros con más valoraciones primero, sin valoración ordenados por nº de páginas).

**Response 200:**
```json
{
  "page": 1,
  "limit": 20,
  "totalItems": 342,
  "results": [
    {
      "externalId": "zyTCAlFPjgYC",
      "title": "Harry Potter y la piedra filosofal",
      "subtitle": null,
      "authors": ["J.K. Rowling"],
      "publisher": "Salamandra",
      "publishedDate": "2000",
      "categories": ["Fiction"],
      "language": "es",
      "description": "...",
      "pageCount": 309,
      "averageRating": 4.5,
      "ratingsCount": 12000,
      "thumbnail": "https://books.google.com/...",
      "previewLink": "https://...",
      "infoLink": "https://...",
      "isbn10": "8478884456",
      "isbn13": "9788478884452"
    }
  ]
}
```
**Response 400:** no se envió ningún filtro.  
**Response 502:** error de Google Books o timeout.

---

### `GET /api/books/{externalId}`
Detalle de un volumen de Google Books.

**Response 200:** mismo objeto que un elemento de `results` de la búsqueda.  
**Response 404:** libro no encontrado en Google Books.  
**Response 502:** error de conexión.

---

## 4. Reseñas de libros

### `GET /api/books/{externalId}/reviews`
Obtiene reseñas y estadísticas de un libro. Si el libro no existe en BD devuelve stats vacías.

**Auth:** opcional (afecta a `myRating`)  
**Response 200:**
```json
{
  "stats": { "average": 4.2, "count": 15 },
  "myRating": { "id": 8, "rating": 5, "content": "Excelente" },
  "reviews": [
    {
      "id": 8,
      "rating": 5,
      "content": "Excelente",
      "createdAt": "2026-04-01T12:00:00+00:00",
      "user": { "id": 1, "displayName": "usuario", "avatar": "abc.jpg" }
    }
  ]
}
```
`myRating` es `null` si el usuario no ha valorado el libro.

---

### `POST /api/books/{externalId}/reviews`
Crear o actualizar la reseña del usuario autenticado (upsert).

**Auth:** requerida  
**Body JSON:**
```json
{ "rating": 4, "content": "Muy bueno" }
```
`rating`: entero del 1 al 5 (obligatorio).  
`content`: texto libre (opcional).

Si el libro no está en BD se importa automáticamente desde Google Books.

**Response 201:**
```json
{
  "review": { "id": 8, "rating": 4, "content": "Muy bueno", "createdAt": "...", "user": {...} },
  "stats": { "average": 4.1, "count": 16 }
}
```
**Response 400:** rating fuera de rango.  
**Response 404:** libro no encontrado en Google Books.

---

### `DELETE /api/books/{externalId}/reviews`
Eliminar la reseña del usuario autenticado.

**Auth:** requerida  
**Response 200:** `{ "stats": { "average": 4.0, "count": 15 } }`  
**Response 404:** libro no encontrado o el usuario no tiene reseña.

---

## 5. Estanterías

### `GET /api/shelves`
Lista las estanterías del usuario (sin los libros).

**Auth:** requerida  
**Response 200:**
```json
[{ "id": 1, "name": "Favoritos" }, { "id": 2, "name": "Por leer" }]
```

---

### `GET /api/shelves/full`
Lista todas las estanterías con sus libros completos.

**Auth:** requerida  
**Response 200:**
```json
[
  {
    "id": 1,
    "name": "Favoritos",
    "books": [
      {
        "id": 10,
        "status": "read",
        "orderIndex": 0,
        "addedAt": "2026-04-01T10:00:00+00:00",
        "book": { "id": 5, "externalId": "zyTCAlFPjgYC", "title": "...", "authors": [...], ... }
      }
    ]
  }
]
```

---

### `POST /api/shelves`
Crear estantería.

**Auth:** requerida  
**Body JSON:** `{ "name": "Mi estantería" }`  
**Response 201:** `{ "id": 3, "name": "Mi estantería" }`  
**Response 400:** name vacío.

---

### `PATCH /api/shelves/{id}`
Renombrar estantería.

**Auth:** requerida (propietario)  
**Body JSON:** `{ "name": "Nuevo nombre" }`  
**Response 200:** `{ "id": 1, "name": "Nuevo nombre" }`  
**Response 404:** estantería no encontrada o no es del usuario.

---

### `DELETE /api/shelves/{id}`
Eliminar estantería (elimina también todos sus libros).

**Auth:** requerida (propietario)  
**Response 204:** sin contenido.  
**Response 404:** no encontrada.

---

### `GET /api/shelves/{id}/books`
Libros de una estantería concreta.

**Auth:** requerida  
**Response 200:** array de `ShelfBook` con datos del libro.

Campos de `ShelfBook`:
- `id`: id de la entrada (no del libro)
- `status`: `want_to_read` | `reading` | `read`
- `orderIndex`: posición en la estantería
- `addedAt`: fecha ISO 8601
- `book`: objeto libro completo

---

### `POST /api/shelves/{id}/books`
Añadir libro a estantería. Si el libro no existe en BD se importa automáticamente desde Google Books.

**Auth:** requerida  
**Body JSON:**
```json
{ "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }
```
`status`: `want_to_read` (default) | `reading` | `read`

**Response 201:** objeto `ShelfBook` con datos del libro.  
**Errores:** 400 (externalId vacío), 404 (libro no encontrado), 409 (ya está en esta estantería).

---

### `PATCH /api/shelves/{id}/books/{bookId}`
Actualizar el estado de lectura de un libro.

**Auth:** requerida  
**Nota:** `bookId` es el id de la entrada `ShelfBook`, no el id del libro.  
**Body JSON:** `{ "status": "reading" }`  
**Response 200:** `{ "id": 10, "status": "reading", "book": {...} }`

---

### `DELETE /api/shelves/{id}/books/{bookId}`
Quitar libro de la estantería.

**Auth:** requerida  
**Response 204:** sin contenido.

---

## 6. Progreso de lectura

### `GET /api/reading-progress`
Lista todos los libros que el usuario está rastreando.

**Auth:** requerida  
**Response 200:**
```json
[
  {
    "id": 1,
    "mode": "pages",
    "currentPage": 120,
    "totalPages": 309,
    "percent": null,
    "computed": 38,
    "startedAt": "2026-03-01T00:00:00+00:00",
    "updatedAt": "2026-04-10T15:30:00+00:00",
    "book": { "id": 5, "externalId": "...", "title": "...", "authors": [...], "coverUrl": "...", "pageCount": 309 }
  }
]
```
`computed`: porcentaje calculado automáticamente (0-100). Si `mode` es `percent` usa el campo `percent`; si es `pages` calcula `currentPage / totalPages * 100`.

---

### `POST /api/reading-progress`
Iniciar seguimiento de un libro.

**Auth:** requerida  
**Body JSON:**
```json
{
  "externalId": "zyTCAlFPjgYC",
  "mode": "pages",
  "totalPages": 309
}
```
`mode`: `pages` | `percent` (default: `percent`)  
`totalPages`: opcional, sobreescribe el nº de páginas del libro.

Si ya existe un seguimiento para ese libro devuelve **200** con el registro existente (sin crear duplicado).

**Response 201:** objeto `ReadingProgress`.  
**Response 404:** libro no encontrado en Google Books.

---

### `PATCH /api/reading-progress/{id}`
Actualizar progreso.

**Auth:** requerida  
**Body JSON** (todos opcionales):
```json
{
  "mode": "pages",
  "currentPage": 150,
  "totalPages": 309,
  "percent": null
}
```
**Response 200:** objeto `ReadingProgress` actualizado.

---

### `DELETE /api/reading-progress/{id}`
Eliminar seguimiento.

**Auth:** requerida  
**Response 204:** sin contenido.

---

## 7. Clubs

### `GET /api/clubs`
Lista todos los clubs (públicos y privados visibles).

**Auth:** opcional (afecta a `userRole` y `hasPendingRequest`)  
**Response 200:**
```json
[
  {
    "id": 1,
    "name": "Club Fantasía",
    "description": "Para amantes de la fantasía",
    "visibility": "public",
    "memberCount": 12,
    "userRole": "member",
    "hasPendingRequest": false,
    "currentBook": {
      "id": 5,
      "externalId": "...",
      "title": "...",
      "authors": [...],
      "coverUrl": "...",
      "publishedDate": "2001",
      "since": "2026-04-01",
      "until": "2026-04-30"
    }
  }
]
```
`userRole`: `admin` | `member` | `null`  
`currentBook`: `null` si no hay libro del mes.

---

### `POST /api/clubs`
Crear club. El creador queda automáticamente como admin.

**Auth:** requerida  
**Body JSON:**
```json
{
  "name": "Mi Club",
  "description": "Descripción opcional",
  "visibility": "public"
}
```
`visibility`: `public` | `private`

**Response 201:** `{ "id": 3, "name": "Mi Club", "visibility": "public" }`  
**Response 400:** name vacío o visibility inválida.

---

### `GET /api/clubs/{id}`
Detalle de un club.

**Auth:** opcional  
**Response 200:** mismo objeto que en la lista, más el campo `owner`:
```json
{
  "owner": { "id": 1, "email": "...", "displayName": "usuario" }
}
```
**Response 404:** club no encontrado.

---

### `PATCH /api/clubs/{id}`
Editar club (solo admin del club).

**Auth:** requerida  
**Body JSON** (todos opcionales): `name`, `description`, `visibility`  
**Response 200:** objeto del club actualizado.  
**Response 403:** solo administradores.

---

### `DELETE /api/clubs/{id}`
Eliminar club (solo admin del club o ROLE_ADMIN global).

**Auth:** requerida  
**Response 204:** sin contenido.

---

### `POST /api/clubs/{id}/join`
Unirse a un club.

- Club **público**: se añade directamente como miembro.
- Club **privado**: se crea una solicitud pendiente y se notifica al owner.

**Auth:** requerida  
**Response 200:**
- Ya miembro: `{ "status": "already_member", "role": "admin" }`
- Ya solicitado: `{ "status": "already_requested", "requestStatus": "pending" }`
- Unido: `{ "status": "joined", "role": "member" }`
- Solicitado: `{ "status": "requested" }`

---

### `DELETE /api/clubs/{id}/leave`
Abandonar un club.

**Auth:** requerida  
**Nota:** el admin no puede salir si hay otros miembros; debe transferir el rol primero.  
**Response 204:** sin contenido.  
**Response 400:** admin con miembros restantes.

---

### `GET /api/clubs/{id}/members`
Lista de miembros del club.

**Auth:** requerida si el club es privado  
**Response 200:**
```json
[
  {
    "id": 1,
    "role": "admin",
    "joinedAt": "2026-01-01T00:00:00+00:00",
    "user": { "id": 1, "displayName": "usuario", "avatar": "abc.jpg" }
  }
]
```

---

### `DELETE /api/clubs/{id}/members/{memberId}`
Expulsar miembro (solo admin del club).

**Auth:** requerida  
**Nota:** `memberId` es el id del `ClubMember`, no del usuario.  
**Response 204:** sin contenido.  
**Response 400:** no puedes expulsarte a ti mismo.

---

### `GET /api/clubs/{id}/requests`
Solicitudes de ingreso pendientes (solo admin del club).

**Auth:** requerida  
**Response 200:**
```json
[
  {
    "id": 3,
    "status": "pending",
    "requestedAt": "2026-04-10T09:00:00+00:00",
    "user": { "id": 5, "displayName": "solicitante", "avatar": null }
  }
]
```

---

### `POST /api/clubs/{id}/requests/{requestId}/approve`
Aceptar solicitud de ingreso (solo admin). Notifica al solicitante.

**Auth:** requerida  
**Response 200:** `{ "status": "approved" }`

---

### `POST /api/clubs/{id}/requests/{requestId}/reject`
Rechazar solicitud de ingreso (solo admin). Notifica al solicitante.

**Auth:** requerida  
**Response 200:** `{ "status": "rejected" }`

---

### `PUT /api/clubs/{id}/current-book`
Establecer el libro del mes (solo admin del club o ROLE_ADMIN).

**Auth:** requerida  
**Body JSON:**
```json
{
  "externalId": "zyTCAlFPjgYC",
  "dateFrom": "2026-04-01",
  "dateUntil": "2026-04-30"
}
```
`dateFrom`: opcional (default: hoy).  
`dateUntil`: opcional, debe ser posterior a `dateFrom`.

Si el libro no está en BD se importa desde Google Books.

**Response 200:** objeto `currentBook`.

---

### `DELETE /api/clubs/{id}/current-book`
Quitar el libro del mes (solo admin del club o ROLE_ADMIN).

**Auth:** requerida  
**Response 204:** sin contenido.

---

## 8. Chat de clubs

### `GET /api/clubs/{clubId}/chats`
Lista los hilos de chat del club.

**Auth:** requerida si el club es privado  
**Response 200:**
```json
[
  {
    "id": 1,
    "title": "Discusión general",
    "isOpen": true,
    "messageCount": 42,
    "createdAt": "2026-04-01T00:00:00+00:00",
    "closedAt": null,
    "createdBy": { "id": 1, "displayName": "admin", "avatar": null }
  }
]
```

---

### `POST /api/clubs/{clubId}/chats`
Crear hilo de chat (solo admin del club o ROLE_ADMIN).

**Auth:** requerida  
**Body JSON:** `{ "title": "Nuevo hilo" }`  
**Response 201:** objeto del hilo creado.

---

### `GET /api/clubs/{clubId}/chats/{chatId}`
Detalle de un hilo.

**Auth:** requerida si el club es privado  
**Response 200:** objeto del hilo.

---

### `PATCH /api/clubs/{clubId}/chats/{chatId}`
Editar hilo (creador del hilo o admin del club).

**Auth:** requerida  
**Body JSON** (opcionales):
```json
{ "title": "Nuevo título", "isOpen": false }
```
Al cerrar (`isOpen: false`) se registra `closedAt`.

**Response 200:** objeto del hilo actualizado.

---

### `DELETE /api/clubs/{clubId}/chats/{chatId}`
Eliminar hilo (solo admin del club o ROLE_ADMIN).

**Auth:** requerida  
**Response 204:** sin contenido.

---

### `GET /api/clubs/{clubId}/chats/{chatId}/messages`
Mensajes paginados del hilo (más antiguos primero).

**Auth:** requerida si el club es privado  
**Query params:**
| Param | Default | Max |
|-------|---------|-----|
| `page` | 1 | — |
| `limit` | 50 | 100 |

**Response 200:**
```json
{
  "page": 1,
  "limit": 50,
  "total": 120,
  "messages": [
    {
      "id": 1,
      "content": "Hola a todos",
      "createdAt": "2026-04-01T10:00:00+00:00",
      "user": { "id": 2, "displayName": "lector", "avatar": null }
    }
  ]
}
```

---

### `POST /api/clubs/{clubId}/chats/{chatId}/messages`
Enviar mensaje (solo miembros, hilo abierto).

**Auth:** requerida  
**Body JSON:** `{ "content": "Mi mensaje" }`  
**Response 201:** objeto del mensaje.  
**Response 400:** hilo cerrado o contenido vacío.  
**Response 403:** no eres miembro.

---

### `DELETE /api/clubs/{clubId}/chats/{chatId}/messages/{messageId}`
Borrar mensaje (autor del mensaje o admin del club).

**Auth:** requerida  
**Response 204:** sin contenido.

---

## 9. Social — Posts

### `GET /api/posts`
Feed del usuario autenticado: posts propios + posts de personas a las que sigue.

**Auth:** requerida  
**Response 200:**
```json
[
  {
    "id": 1,
    "imagePath": "post_abc123.jpg",
    "description": "Mi lectura de hoy",
    "createdAt": "2026-04-15T10:00:00+00:00",
    "likes": 5,
    "liked": false,
    "commentCount": 2,
    "user": { "id": 1, "displayName": "usuario", "avatar": "abc.jpg" }
  }
]
```
Las imágenes se sirven desde `/uploads/posts/{imagePath}`.

---

### `GET /api/users/{id}/posts`
Posts publicados por un usuario concreto.

**Auth:** opcional  
**Response 200:** mismo formato que el feed.

---

### `POST /api/posts`
Crear post. Envío como `multipart/form-data`.

**Auth:** requerida  
**Campos:**
| Campo | Tipo | Requerido |
|-------|------|-----------|
| `image` | archivo | Sí |
| `description` | texto | No |

Formatos permitidos: `jpg`, `jpeg`, `png`, `gif`, `webp`.

**Response 201:** objeto del post creado.  
**Response 400:** sin imagen o formato no permitido.

---

### `DELETE /api/posts/{id}`
Eliminar post propio (o cualquiera si ROLE_ADMIN). Elimina también el archivo de imagen.

**Auth:** requerida  
**Response 204:** sin contenido.

---

### `POST /api/posts/{id}/like`
Toggle like/unlike en un post.

**Auth:** requerida  
**Response 200:** `{ "liked": true, "likes": 6 }`

---

### `GET /api/posts/{id}/comments`
Lista de comentarios de un post.

**Auth:** opcional  
**Response 200:**
```json
[
  {
    "id": 1,
    "content": "Qué buena foto",
    "createdAt": "2026-04-15T11:00:00+00:00",
    "user": { "id": 2, "displayName": "lector", "avatar": null }
  }
]
```

---

### `POST /api/posts/{id}/comments`
Añadir comentario.

**Auth:** requerida  
**Body JSON:** `{ "content": "Mi comentario" }`  
**Response 201:** objeto del comentario.

---

### `DELETE /api/posts/{id}/comments/{commentId}`
Eliminar comentario. Permitido al autor del comentario o al dueño del post.

**Auth:** requerida  
**Response 204:** sin contenido.

---

## 10. Social — Follows

### `POST /api/users/{id}/follow`
Seguir a un usuario.

- Usuario **público**: follow aceptado automáticamente.
- Usuario **privado**: solicitud pendiente. Notifica al destinatario.

**Auth:** requerida  
**Response 200:**
```json
{ "status": "accepted", "isFollowing": true, "followers": 11 }
```
o `{ "status": "pending", "isFollowing": false, "followers": 10 }`

**Response 400:** intentando seguirte a ti mismo.  
**Response 409:** ya sigues o ya enviaste solicitud.

---

### `DELETE /api/users/{id}/follow`
Dejar de seguir.

**Auth:** requerida  
**Response 200:** `{ "status": null, "isFollowing": false, "followers": 10 }`

---

### `GET /api/users/{id}/followers`
Lista de seguidores de un usuario.

**Response 200:** array de `{ id, displayName, avatar, email }`

---

### `GET /api/users/{id}/following`
Lista de usuarios a los que sigue.

**Response 200:** mismo formato que `/followers`.

---

### `DELETE /api/users/{id}/followers`
Eliminar un seguidor (el usuario `{id}` deja de seguirte).

**Auth:** requerida  
**Response 200:** `{ "followers": 9 }`

---

### `GET /api/follow-requests`
Solicitudes de seguimiento entrantes (pendientes de aprobación).

**Auth:** requerida  
**Response 200:**
```json
[
  {
    "id": 5,
    "createdAt": "2026-04-10T08:00:00+00:00",
    "user": { "id": 3, "displayName": "solicitante", "avatar": null, "email": "..." }
  }
]
```

---

### `POST /api/follow-requests/{id}/accept`
Aceptar solicitud de seguimiento. Notifica al solicitante.

**Auth:** requerida  
**Response 200:** `{ "status": "accepted" }`

---

### `DELETE /api/follow-requests/{id}`
Rechazar solicitud de seguimiento.

**Auth:** requerida  
**Response 200:** `{ "status": "declined" }`

---

## 11. Notificaciones

### `GET /api/notifications`
Últimas 30 notificaciones del usuario, con contador de no leídas.

**Auth:** requerida  
**Response 200:**
```json
{
  "unread": 3,
  "items": [
    {
      "id": 10,
      "type": "follow",
      "isRead": false,
      "createdAt": "2026-04-18T09:00:00+00:00",
      "refId": null,
      "actor": { "id": 2, "displayName": "lector", "avatar": null },
      "post": null,
      "club": null
    }
  ]
}
```

**Tipos de notificación (`type`):**
| Valor | Significado |
|-------|-------------|
| `follow` | Alguien te ha seguido |
| `follow_request` | Solicitud de seguimiento entrante (`refId` = id del Follow) |
| `follow_accepted` | Tu solicitud de seguimiento fue aceptada |
| `club_request` | Solicitud de ingreso en tu club (`refId` = id del ClubJoinRequest) |
| `club_approved` | Tu solicitud de ingreso fue aprobada |
| `club_rejected` | Tu solicitud de ingreso fue rechazada |

---

### `GET /api/notifications/history`
Historial completo de notificaciones (hasta 100).

**Auth:** requerida  
**Response 200:** `{ "items": [...] }`

---

### `POST /api/notifications/read-all`
Marcar todas las notificaciones como leídas.

**Auth:** requerida  
**Response 200:** `{ "unread": 0 }`

---

### `POST /api/notifications/follow-requests/{followId}/accept`
Aceptar una solicitud de seguimiento desde el panel de notificaciones (equivalente a `POST /api/follow-requests/{id}/accept`).

**Auth:** requerida  
**Response 200:** `{ "status": "accepted" }`

---

### `DELETE /api/notifications/follow-requests/{followId}`
Rechazar una solicitud de seguimiento desde el panel de notificaciones.

**Auth:** requerida  
**Response 200:** `{ "status": "declined" }`

---

## 12. Panel de administración

Todos los endpoints de esta sección requieren `ROLE_ADMIN`.

### `GET /api/admin/stats`
Estadísticas globales.

**Response 200:**
```json
{ "users": 50, "clubs": 12, "posts": 340 }
```

---

### `GET /api/admin/users`
Lista completa de usuarios.

**Response 200:**
```json
[
  {
    "id": 1,
    "email": "admin@example.com",
    "displayName": "admin",
    "avatar": null,
    "roles": ["ROLE_USER", "ROLE_ADMIN"],
    "isVerified": true,
    "isAdmin": true,
    "isBanned": false
  }
]
```

---

### `PATCH /api/admin/users/{id}/role`
Promover o degradar un usuario como administrador.

**Body JSON:** `{ "isAdmin": true }`  
**Response 200:** `{ "id": 2, "isAdmin": true, "roles": ["ROLE_USER", "ROLE_ADMIN"] }`  
**Response 400:** intentando cambiar tu propio rol.

---

### `PATCH /api/admin/users/{id}/ban`
Banear o desbanear un usuario.

**Auth:** `ROLE_ADMIN`  
**Body JSON:** `{ "isBanned": true }`  
**Response 200:** `{ "id": 2, "isBanned": true }`  
**Response 400:** intentando banear tu propia cuenta.

---

### `DELETE /api/admin/users/{id}`
Eliminar un usuario (cascada en BD: estanterías, posts, membresías, etc.).

**Response 204:** sin contenido.  
**Response 400:** intentando eliminar tu propia cuenta.

---

### `GET /api/admin/clubs`
Lista completa de clubs con el owner y número de miembros.

**Response 200:**
```json
[
  {
    "id": 1,
    "name": "Club Fantasía",
    "description": "...",
    "visibility": "public",
    "memberCount": 12,
    "owner": { "id": 1, "displayName": "admin", "email": "admin@example.com" },
    "createdAt": "2026-01-15T00:00:00+00:00"
  }
]
```

---

### `DELETE /api/admin/clubs/{id}`
Eliminar cualquier club.

**Response 204:** sin contenido.

---

### `GET /api/admin/posts`
Últimos 100 posts.

**Response 200:**
```json
[
  {
    "id": 5,
    "description": "...",
    "imagePath": "post_abc.jpg",
    "createdAt": "2026-04-15T10:00:00+00:00",
    "user": { "id": 2, "displayName": "lector", "email": "lector@example.com" }
  }
]
```

---

### `DELETE /api/admin/posts/{id}`
Eliminar cualquier post (elimina también la imagen del servidor).

**Response 204:** sin contenido.

---

## Notas generales

### Autenticación y sesión
La autenticación se gestiona mediante sesiones PHP. El cliente debe enviar la cookie `PHPSESSID` en cada petición. El login se realiza en `POST /api/auth/login` (gestionado por Symfony Security, no expuesto como controlador propio).

### Archivos estáticos
- Avatares: `GET /uploads/avatars/{filename}`
- Posts: `GET /uploads/posts/{filename}`

### Códigos de respuesta comunes
| Código | Significado |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 204 | Sin contenido (operaciones DELETE exitosas) |
| 400 | Petición incorrecta |
| 401 | No autenticado |
| 403 | Sin permisos |
| 404 | No encontrado |
| 409 | Conflicto (duplicado) |
| 502 | Error al contactar con Google Books |
