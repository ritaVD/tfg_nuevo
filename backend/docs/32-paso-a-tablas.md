# 32 — Diagrama E/R y Paso a Tablas

Este documento recoge el diseño de la base de datos del proyecto: el diagrama Entidad-Relación (E/R) y la traducción de ese modelo a tablas relacionales con sus claves primarias, claves foráneas, tipos de datos y restricciones.

---

## 1. Diagrama Entidad-Relación

El modelo E/R del sistema se compone de 16 entidades principales y sus relaciones. A continuación se muestra el diagrama en notación textual y, a continuación, la descripción de cada relación.

```
┌──────────────┐       ┌──────────────┐
│     USER     │       │     BOOK     │
│──────────────│       │──────────────│
│ PK id        │       │ PK id        │
│ email        │       │ externalId   │
│ displayName  │       │ externalSource│
│ password     │       │ title        │
│ roles        │       │ authors      │
│ bio          │       │ isbn10       │
│ avatar       │       │ isbn13       │
│ isVerified   │       │ coverUrl     │
│ isPrivate    │       │ description  │
│ shelvesPublic│       │ publisher    │
│ clubsPublic  │       │ publishedDate│
└──────┬───────┘       │ language     │
       │               │ pageCount    │
       │               │ categories   │
       │               │ createdAt    │
       │               │ updatedAt    │
       │               └──────┬───────┘
       │                      │
       │  1:N                 │ N:1
       ▼                      │
┌──────────────┐       ┌──────┴───────┐
│    SHELF     │  1:N  │  SHELF_BOOK  │
│──────────────│◄──────│─────────────│
│ PK id        │       │ PK id        │
│ FK user_id   │       │ FK shelf_id  │
│ name         │       │ FK book_id   │
│ orderIndex   │       │ status       │
│ createdAt    │       │ orderIndex   │
│ updatedAt    │       │ addedAt      │
└──────────────┘       │ UNIQUE(shelf_id, book_id) │
                       └──────────────┘

USER ──1:N──► READING_PROGRESS ──N:1──► BOOK
USER ──1:N──► BOOK_REVIEW ──N:1──► BOOK

┌──────────────┐       ┌──────────────┐
│     CLUB     │  N:1  │     USER     │
│──────────────│──────►│ (owner)      │
│ PK id        │       └──────────────┘
│ FK owner_id  │
│ FK currentBook_id (nullable) ──N:1──► BOOK
│ name         │
│ description  │
│ visibility   │
│ createdAt    │
│ updatedAt    │
└──────┬───────┘
       │
       ├──1:N──► CLUB_MEMBER ──N:1──► USER
       ├──1:N──► CLUB_JOIN_REQUEST ──N:1──► USER
       └──1:N──► CLUB_CHAT ──1:N──► CLUB_CHAT_MESSAGE ──N:1──► USER

USER ──1:N──► FOLLOW ──N:1──► USER  (relación reflexiva, follower → following)

USER ──1:N──► POST ──1:N──► POST_LIKE ──N:1──► USER
                    └──1:N──► POST_COMMENT ──N:1──► USER

USER ──1:N──► NOTIFICATION
```

### Cardinalidades resumidas

| Relación | Cardinalidad | Descripción |
|----------|-------------|-------------|
| User → Shelf | 1:N | Un usuario tiene cero o muchas estanterías |
| Shelf → ShelfBook | 1:N | Una estantería contiene cero o muchos libros |
| ShelfBook → Book | N:1 | Muchas entradas de estantería apuntan al mismo libro |
| User → ReadingProgress | 1:N | Un usuario puede tener varios seguimientos de lectura |
| ReadingProgress → Book | N:1 | Un seguimiento corresponde a un único libro |
| User → BookReview | 1:N | Un usuario puede escribir varias reseñas |
| BookReview → Book | N:1 | Una reseña corresponde a un único libro |
| User → Club (owner) | 1:N | Un usuario puede crear varios clubs |
| Club → ClubMember | 1:N | Un club tiene cero o muchos miembros |
| User → ClubMember | 1:N | Un usuario puede ser miembro de varios clubs |
| Club → ClubJoinRequest | 1:N | Un club puede tener varias solicitudes pendientes |
| Club → ClubChat | 1:N | Un club puede tener varios hilos de debate |
| ClubChat → ClubChatMessage | 1:N | Un hilo contiene cero o muchos mensajes |
| User → Follow (follower) | 1:N | Un usuario puede seguir a muchas personas |
| User → Follow (following) | 1:N | Un usuario puede ser seguido por muchas personas |
| User → Post | 1:N | Un usuario puede publicar cero o muchas entradas |
| Post → PostLike | 1:N | Una publicación puede recibir cero o muchos likes |
| Post → PostComment | 1:N | Una publicación puede tener cero o muchos comentarios |
| User → Notification | 1:N | Un usuario puede recibir cero o muchas notificaciones |

---

## 2. Paso a Tablas

A continuación se detalla la traducción del modelo E/R a tablas relacionales. Para cada tabla se indica: nombre, columnas con tipo de dato SQL, clave primaria, claves foráneas y restricciones de unicidad o índices adicionales.

---

### Tabla `user`

| Columna | Tipo SQL | Nulo | Valor por defecto | Descripción |
|---------|----------|------|-------------------|-------------|
| `id` | INT AUTO_INCREMENT | NO | — | **Clave primaria** |
| `email` | VARCHAR(180) | NO | — | Email de login. **UNIQUE** |
| `display_name` | VARCHAR(80) | NO | — | Nombre visible público. **UNIQUE** |
| `password` | VARCHAR(255) | NO | — | Hash bcrypt/argon2id |
| `roles` | JSON | NO | `'["ROLE_USER"]'` | Array de roles |
| `bio` | VARCHAR(255) | SÍ | NULL | Biografía opcional |
| `avatar` | VARCHAR(255) | SÍ | NULL | Nombre de fichero del avatar |
| `is_verified` | TINYINT(1) | NO | 0 | Email verificado |
| `is_private` | TINYINT(1) | NO | 0 | Perfil privado |
| `shelves_public` | TINYINT(1) | NO | 1 | Estanterías visibles |
| `clubs_public` | TINYINT(1) | NO | 1 | Clubs visibles |

**Restricciones:** `UNIQUE(email)`, `UNIQUE(display_name)`

---

### Tabla `book`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `external_id` | VARCHAR(255) | SÍ | ID en Google Books |
| `external_source` | VARCHAR(50) | SÍ | Siempre `'google_books'` |
| `title` | VARCHAR(255) | NO | Título del libro |
| `authors` | JSON | SÍ | Array de autores |
| `isbn10` | VARCHAR(20) | SÍ | ISBN-10. **UNIQUE** |
| `isbn13` | VARCHAR(20) | SÍ | ISBN-13. **UNIQUE** |
| `cover_url` | TEXT | SÍ | URL de portada |
| `description` | TEXT | SÍ | Sinopsis |
| `publisher` | VARCHAR(255) | SÍ | Editorial |
| `published_date` | VARCHAR(50) | SÍ | Fecha de publicación (string) |
| `language` | VARCHAR(10) | SÍ | Código de idioma |
| `page_count` | INT | SÍ | Número de páginas |
| `categories` | JSON | SÍ | Array de categorías |
| `created_at` | DATETIME | NO | Fecha de importación |
| `updated_at` | DATETIME | NO | Última actualización |

**Restricciones:** `UNIQUE(external_source, external_id)`, `UNIQUE(isbn13)`, `UNIQUE(isbn10)`
**Índices:** `INDEX(title)`

---

### Tabla `shelf`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `name` | VARCHAR(255) | NO | Nombre de la estantería |
| `order_index` | INT | NO | Posición de visualización |
| `created_at` | DATETIME | NO | — |
| `updated_at` | DATETIME | NO | — |

---

### Tabla `shelf_book`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `shelf_id` | INT | NO | **FK → shelf(id)** ON DELETE CASCADE |
| `book_id` | INT | NO | **FK → book(id)** ON DELETE CASCADE |
| `status` | VARCHAR(20) | SÍ | `want_to_read` / `reading` / `read` |
| `order_index` | INT | NO | Orden dentro de la estantería |
| `added_at` | DATETIME | NO | Fecha de adición |

**Restricciones:** `UNIQUE(shelf_id, book_id)`

---

### Tabla `reading_progress`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `book_id` | INT | NO | **FK → book(id)** ON DELETE CASCADE |
| `mode` | VARCHAR(10) | NO | `pages` / `percent` |
| `current_page` | INT | SÍ | Página actual |
| `total_pages` | INT | SÍ | Total de páginas (override) |
| `percent` | DOUBLE | SÍ | Porcentaje 0-100 |
| `started_at` | DATETIME | NO | Fecha de inicio |
| `updated_at` | DATETIME | NO | Última actualización |

**Restricciones:** `UNIQUE(user_id, book_id)`

---

### Tabla `book_review`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `book_id` | INT | NO | **FK → book(id)** ON DELETE CASCADE |
| `rating` | INT | NO | Puntuación 1-5 |
| `content` | TEXT | SÍ | Texto de la reseña |
| `created_at` | DATETIME | NO | — |

**Restricciones:** `UNIQUE(user_id, book_id)`

---

### Tabla `club`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `owner_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `current_book_id` | INT | SÍ | **FK → book(id)** ON DELETE SET NULL |
| `name` | VARCHAR(255) | NO | Nombre del club |
| `description` | TEXT | SÍ | Descripción |
| `visibility` | VARCHAR(10) | NO | `public` / `private` |
| `current_book_since` | DATETIME | SÍ | Inicio del libro del mes |
| `current_book_until` | DATETIME | SÍ | Fin previsto del libro del mes |
| `created_at` | DATETIME | NO | — |
| `updated_at` | DATETIME | NO | — |

---

### Tabla `club_member`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `club_id` | INT | NO | **FK → club(id)** ON DELETE CASCADE |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `role` | VARCHAR(10) | NO | `admin` / `member` |
| `joined_at` | DATETIME | NO | Fecha de incorporación |

**Restricciones:** `UNIQUE(club_id, user_id)`

---

### Tabla `club_join_request`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `club_id` | INT | NO | **FK → club(id)** ON DELETE CASCADE |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `resolved_by_id` | INT | SÍ | **FK → user(id)** ON DELETE SET NULL |
| `status` | VARCHAR(10) | NO | `pending` / `approved` / `rejected` |
| `requested_at` | DATETIME | NO | — |
| `resolved_at` | DATETIME | SÍ | — |

**Restricciones:** `UNIQUE(club_id, user_id)`

---

### Tabla `club_chat`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `club_id` | INT | NO | **FK → club(id)** ON DELETE CASCADE |
| `created_by_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `title` | VARCHAR(255) | NO | Título del hilo |
| `is_open` | TINYINT(1) | NO | 1 = abierto, 0 = cerrado |
| `created_at` | DATETIME | NO | — |
| `closed_at` | DATETIME | SÍ | — |

---

### Tabla `club_chat_message`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `chat_id` | INT | NO | **FK → club_chat(id)** ON DELETE CASCADE |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `content` | TEXT | NO | Contenido del mensaje |
| `created_at` | DATETIME | NO | — |

**Índices:** `INDEX(chat_id, created_at)` para paginación eficiente de mensajes

---

### Tabla `follow`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `follower_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE — quien sigue |
| `following_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE — quien es seguido |
| `status` | VARCHAR(10) | NO | `pending` / `accepted` |
| `created_at` | DATETIME | NO | — |

**Restricciones:** `UNIQUE(follower_id, following_id)`

---

### Tabla `post`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `image_path` | VARCHAR(255) | NO | Nombre del fichero de imagen |
| `description` | TEXT | SÍ | Texto de la publicación |
| `created_at` | DATETIME | NO | — |

---

### Tabla `post_like`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `post_id` | INT | NO | **FK → post(id)** ON DELETE CASCADE |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `created_at` | DATETIME | NO | — |

**Restricciones:** `UNIQUE(post_id, user_id)` — un usuario no puede dar like dos veces a la misma publicación

---

### Tabla `post_comment`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `post_id` | INT | NO | **FK → post(id)** ON DELETE CASCADE |
| `user_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE |
| `content` | TEXT | NO | Texto del comentario |
| `created_at` | DATETIME | NO | — |

---

### Tabla `notification`

| Columna | Tipo SQL | Nulo | Descripción |
|---------|----------|------|-------------|
| `id` | INT AUTO_INCREMENT | NO | **Clave primaria** |
| `recipient_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE — quien recibe |
| `actor_id` | INT | NO | **FK → user(id)** ON DELETE CASCADE — quien genera la acción |
| `post_id` | INT | SÍ | **FK → post(id)** ON DELETE CASCADE — publicación relacionada |
| `club_id` | INT | SÍ | **FK → club(id)** ON DELETE CASCADE — club relacionado |
| `type` | VARCHAR(30) | NO | Tipo de notificación (ver tabla de tipos) |
| `ref_id` | INT | SÍ | ID auxiliar (Follow.id o ClubJoinRequest.id) |
| `is_read` | TINYINT(1) | NO | 0 = no leída, 1 = leída |
| `created_at` | DATETIME | NO | — |

---

## 3. Resumen de integridad referencial

Todas las claves foráneas del sistema están definidas con las siguientes reglas de borrado en cascada:

| Tabla hija | FK | Regla ON DELETE |
|------------|-----|-----------------|
| `shelf` | `user_id` | CASCADE — al borrar usuario se borran sus estanterías |
| `shelf_book` | `shelf_id` | CASCADE — al borrar estantería se borran sus libros |
| `shelf_book` | `book_id` | CASCADE — si el libro desaparece, se elimina la entrada |
| `reading_progress` | `user_id`, `book_id` | CASCADE |
| `book_review` | `user_id`, `book_id` | CASCADE |
| `club` | `owner_id` | CASCADE |
| `club` | `current_book_id` | SET NULL — el club no se borra si pierde su libro del mes |
| `club_member` | `club_id`, `user_id` | CASCADE |
| `club_join_request` | `club_id`, `user_id` | CASCADE |
| `club_join_request` | `resolved_by_id` | SET NULL |
| `club_chat` | `club_id`, `created_by_id` | CASCADE |
| `club_chat_message` | `chat_id`, `user_id` | CASCADE |
| `follow` | `follower_id`, `following_id` | CASCADE |
| `post` | `user_id` | CASCADE |
| `post_like` | `post_id`, `user_id` | CASCADE |
| `post_comment` | `post_id`, `user_id` | CASCADE |
| `notification` | `recipient_id`, `actor_id` | CASCADE |
| `notification` | `post_id`, `club_id` | CASCADE |
