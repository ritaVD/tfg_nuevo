# 07 — Migraciones de base de datos

Las migraciones son archivos PHP que describen los cambios en el esquema de la base de datos de forma versionada. Cada migración tiene un método `up()` (aplicar cambio) y `down()` (revertir cambio). Se encuentran en la carpeta `migrations/`.

---

## ¿Qué es Doctrine Migrations?

Doctrine Migrations permite:
- Llevar un **historial** de todos los cambios en la estructura de la BD.
- Aplicar cambios de forma **reproducible** en cualquier entorno (local, staging, producción).
- **Revertir** cambios si algo sale mal.

Symfony ejecuta las migraciones pendientes con:
```bash
php bin/console doctrine:migrations:migrate
```

Y para ver el estado actual:
```bash
php bin/console doctrine:migrations:status
```

---

## Historial de migraciones

### `Version20260215195139` — Esquema inicial
**Fecha:** 15 de febrero de 2026

Crea las tablas fundacionales de toda la aplicación:

| Tabla creada | Descripción |
|-------------|-------------|
| `user` | Usuarios (email, password, roles, is_verified) |
| `book` | Libros con metadatos externos (Google Books) |
| `shelf` | Estanterías personales de usuarios |
| `shelf_book` | Relación libro-estantería con estado y orden |
| `club` | Clubes de lectura |
| `club_member` | Membresías en clubes |
| `club_join_request` | Solicitudes de unión a clubes |
| `club_chat` | Hilos de debate en clubes |
| `club_chat_message` | Mensajes en hilos de debate (con índice compuesto) |
| `messenger_messages` | Cola de mensajes asíncronos de Symfony |

**Claves foráneas relevantes:**
- `club.owner_id → user.id`
- `shelf.user_id → user.id`
- `shelf_book.shelf_id → shelf.id`, `shelf_book.book_id → book.id`
- `club_chat.club_id → club.id`, `club_chat.created_by_id → user.id`
- `club_chat_message.chat_id → club_chat.id`, `club_chat_message.user_id → user.id`

---

### `Version20260217083036` — Tabla de seguimientos
**Fecha:** 17 de febrero de 2026

Añade la tabla `follow` para la funcionalidad social de seguir usuarios:

```sql
CREATE TABLE follow (
    id INT AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    status VARCHAR(10) DEFAULT 'accepted',
    created_at DATETIME NOT NULL,
    UNIQUE (follower_id, following_id),  -- no se puede seguir dos veces
    FOREIGN KEY (follower_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES user(id) ON DELETE CASCADE
)
```

El `ON DELETE CASCADE` garantiza que si un usuario se elimina, todos sus follows desaparecen también.

---

### `Version20260217083418` — Primera versión de notificaciones
**Fecha:** 17 de febrero de 2026

Añade una versión inicial de la tabla `notification`. Esta versión fue posteriormente reemplazada por una estructura más completa.

---

### `Version20260330000000` — Posts, likes y comentarios
**Fecha:** 30 de marzo de 2026

Añade el módulo de publicaciones sociales:

| Tabla creada | Descripción |
|-------------|-------------|
| `post` | Publicaciones con imagen y descripción |
| `post_like` | Likes en publicaciones (único por usuario+post) |
| `post_comment` | Comentarios en publicaciones |

---

### `Version20260330191240` — Ajuste de hilos de chat
**Fecha:** 30 de marzo de 2026

Ajustes a la estructura de `club_chat` y `club_chat_message` (posiblemente columnas o índices añadidos tras la migración inicial).

---

### `Version20260401000000` — Progreso de lectura
**Fecha:** 1 de abril de 2026

Añade la tabla `reading_progress` para el seguimiento del avance de lectura:

```sql
CREATE TABLE reading_progress (
    id INT AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    mode VARCHAR(10),           -- 'pages' o 'percent'
    current_page INT,
    total_pages INT,
    percent FLOAT,
    started_at DATETIME,
    updated_at DATETIME,
    UNIQUE (user_id, book_id)  -- un registro por usuario y libro
)
```

---

### `Version20260401120000` — Reseñas de libros
**Fecha:** 1 de abril de 2026

Añade la tabla `book_review`:

```sql
CREATE TABLE book_review (
    id INT AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT NOT NULL,        -- del 1 al 5
    content LONGTEXT,
    created_at DATETIME,
    UNIQUE (user_id, book_id)  -- una reseña por usuario y libro
)
```

---

### `Version20260402103111` — Campos de perfil de usuario
**Fecha:** 2 de abril de 2026

Añade los campos de personalización del perfil a la tabla `user`:

```sql
ALTER TABLE user
    ADD display_name VARCHAR(80) UNIQUE,
    ADD bio VARCHAR(255),
    ADD avatar VARCHAR(255),
    ADD is_private TINYINT DEFAULT 0,
    ADD shelves_public TINYINT DEFAULT 1,
    ADD clubs_public TINYINT DEFAULT 1
```

Antes de esta migración, los usuarios solo tenían `email`, `password` y `roles`.

---

### `Version20260402105602` — Libro actual del club
**Fecha:** 2 de abril de 2026

Añade los campos de libro en curso a la tabla `club`:

```sql
ALTER TABLE club
    ADD current_book_id INT,              -- FK → book.id
    ADD current_book_since DATETIME,
    ADD current_book_until DATETIME,
    ADD FOREIGN KEY (current_book_id) REFERENCES book(id) ON DELETE SET NULL
```

`ON DELETE SET NULL` garantiza que si el libro es eliminado, el campo `current_book_id` del club se pone a `NULL` en lugar de eliminar el club.

---

### `Version20260403120000` — Ajustes menores
**Fecha:** 3 de abril de 2026

Ajustes de tipos o restricciones en columnas existentes.

---

### `Version20260407120000` — Tabla de notificaciones (versión completa)
**Fecha:** 7 de abril de 2026

Reemplaza o completa la tabla `notification` con la estructura definitiva, incluyendo soporte para todos los tipos de notificación:

```sql
CREATE TABLE notification (
    id INT AUTO_INCREMENT,
    recipient_id INT NOT NULL,    -- quien recibe
    actor_id INT NOT NULL,        -- quien genera la acción
    post_id INT,                  -- post relacionado (likes, comentarios)
    club_id INT,                  -- club relacionado (solicitudes de club)
    type VARCHAR(30) NOT NULL,    -- tipo de notificación
    ref_id INT,                   -- ID auxiliar (Follow.id o ClubJoinRequest.id)
    is_read TINYINT DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX (recipient_id), INDEX (actor_id), INDEX (post_id), INDEX (club_id)
)
```

Con `ON DELETE CASCADE` en todas las claves foráneas para limpiar notificaciones huérfanas automáticamente.

---

## Cómo crear una nueva migración

Cuando se modifica o añade una entidad, Doctrine puede generar automáticamente la migración comparando el esquema actual de la BD con las entidades PHP:

```bash
php bin/console doctrine:migrations:diff
```

Esto genera un nuevo archivo en `migrations/` que se puede revisar y ajustar antes de ejecutar:

```bash
php bin/console doctrine:migrations:migrate
```

---

## Tabla de control interno

Doctrine mantiene automáticamente una tabla `doctrine_migration_versions` en la base de datos que registra qué migraciones ya han sido ejecutadas, evitando que se apliquen dos veces.
