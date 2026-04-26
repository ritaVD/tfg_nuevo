# 14 — Módulo Social: Posts y Follows

El módulo social de TFGdaw permite a los usuarios publicar contenido (imágenes con descripción), interactuar con él (likes y comentarios) y seguirse entre sí para construir un feed personalizado.

---

## 1. Publicaciones (Posts)

### 1.1 Qué es un post

Un post es una publicación formada por:
- Una **imagen** obligatoria (jpg, jpeg, png, gif, webp).
- Una **descripción** opcional (texto libre).
- La fecha de creación y el autor.

No hay edición de posts: si quieres cambiar algo, debes borrar y volver a publicar.

### 1.2 Crear una publicación

La petición es `multipart/form-data` (no JSON) porque incluye un archivo:

```
POST /api/posts
Content-Type: multipart/form-data

image: [archivo de imagen]
description: "Terminando El Nombre del Viento, qué final..."
```

**Validaciones:**
- El campo `image` es obligatorio.
- Solo se aceptan extensiones: `jpg`, `jpeg`, `png`, `gif`, `webp`.
- La extensión se detecta por el **contenido real** del archivo, no por su nombre.

**Proceso interno:**
1. Se genera un nombre único: `post_6716a3b4e5f12.jpg` (con `uniqid()` y microsegundos).
2. Se guarda en `public/uploads/posts/`.
3. Se crea la entidad `Post` con el nombre del archivo y la descripción.

### 1.3 Feed del usuario

```
GET /api/posts
```

Devuelve hasta **40 publicaciones** ordenadas de más reciente a más antigua, incluyendo:
- Los posts del propio usuario.
- Los posts de usuarios a los que sigue con estado `accepted`.

La consulta usa un `LEFT JOIN` con la tabla `follow`:

```sql
SELECT p.* FROM post p
LEFT JOIN follow f ON f.follower_id = :me
                  AND f.following_id = p.user_id
                  AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

### 1.4 Posts de un usuario concreto

```
GET /api/users/{id}/posts
```

Devuelve todos los posts de un usuario específico, ordenados de más reciente a más antiguo. Esta ruta es pública (no requiere autenticación), lo que permite ver el perfil de cualquier usuario.

### 1.5 Eliminar un post

```
DELETE /api/posts/{id}
```

Solo puede eliminarlo:
- El **autor del post**.
- Un usuario con **`ROLE_ADMIN`** (administrador global).

Al eliminar un post:
1. Se borra el archivo de imagen del disco (`unlink()`).
2. Se elimina la entidad `Post` de la BD.
3. Por `CASCADE`, se eliminan también todos sus likes y comentarios.

### 1.6 Respuesta serializada de un post

```json
{
  "id": 55,
  "imagePath": "post_6716a3b4e5f12.jpg",
  "description": "Terminando El Nombre del Viento...",
  "createdAt": "2026-04-19T10:00:00+00:00",
  "likes": 8,
  "liked": true,
  "commentCount": 3,
  "user": {
    "id": 2,
    "displayName": "MariaG",
    "avatar": "avatar_2.jpg"
  }
}
```

---

## 2. Sistema de Likes

### 2.1 Toggle de like

```
POST /api/posts/{id}/like
```

Un único endpoint actúa como **toggle**: si el usuario ya dio like, lo quita; si no lo dio, lo añade. No hay endpoint separado para quitar el like.

```
Primera llamada:  liked=false → like añadido  → { liked: true,  likes: 9 }
Segunda llamada:  liked=true  → like eliminado → { liked: false, likes: 8 }
```

La restricción única `(post_id, user_id)` en la tabla garantiza que no pueda existir un like duplicado aunque haya condiciones de carrera.

### 2.2 Notificación de like

Al añadir un like, si el autor del post no es el mismo usuario que hace el like, se crea automáticamente una notificación:

```php
if ($post->getUser()->getId() !== $me->getId()) {
    new Notification($post->getUser(), $me, Notification::TYPE_LIKE, $post);
}
```

---

## 3. Sistema de Comentarios

### 3.1 Ver comentarios

```
GET /api/posts/{id}/comments
```

Ruta pública. Devuelve los comentarios ordenados de más antiguo a más reciente (orden cronológico de conversación).

### 3.2 Añadir un comentario

```
POST /api/posts/{id}/comments
{ "content": "¡Qué buena foto!" }
```

Requiere autenticación. El comentario no puede estar vacío.

Al crear un comentario, si el autor del post no es el mismo que comenta, se genera una notificación `TYPE_COMMENT`.

### 3.3 Eliminar un comentario

```
DELETE /api/posts/{id}/comments/{commentId}
```

Pueden eliminarlo:
- El **autor del comentario**.
- El **autor del post** (puede moderar su propia publicación).

Esto da al creador del post control sobre los comentarios que recibe.

---

## 4. Sistema de Seguimientos (Follow)

### 4.1 Modelo de datos

La tabla `follow` tiene dos participantes y un estado:

```
follower  →  (siguiendo a)  →  following
            status: 'pending' | 'accepted'
```

### 4.2 Seguir a alguien

```
POST /api/users/{id}/follow
```

| Situación | Resultado |
|-----------|-----------|
| Target con perfil **público** | `Follow(status: accepted)` + notif `TYPE_FOLLOW` |
| Target con perfil **privado** | `Follow(status: pending)` + notif `TYPE_FOLLOW_REQUEST` |
| Ya le sigues o tienes solicitud pendiente | `409 Conflict` |
| Intentas seguirte a ti mismo | `400 Bad Request` |

**Respuesta:**
```json
{
  "status": "accepted",
  "isFollowing": true,
  "followers": 42
}
```
o para perfil privado:
```json
{
  "status": "pending",
  "isFollowing": false,
  "followers": 42
}
```

### 4.3 Dejar de seguir

```
DELETE /api/users/{id}/follow
```

Elimina el registro `Follow` independientemente de su estado (cancela tanto seguimientos activos como solicitudes pendientes).

### 4.4 Ver lista de seguidores / seguidos

```
GET /api/users/{id}/followers   → quiénes siguen a este usuario
GET /api/users/{id}/following   → a quiénes sigue este usuario
```

Ambas rutas son públicas y devuelven solo los follows con estado `accepted`.

### 4.5 Eliminar un seguidor

```
DELETE /api/users/{id}/followers
```

Permite a un usuario **expulsar** a alguien que le sigue. Útil para cuentas privadas que quieren revocar el acceso de un seguidor ya aceptado.

### 4.6 Ver solicitudes entrantes

```
GET /api/follow-requests
```

Lista las solicitudes de seguimiento pendientes que ha recibido el usuario autenticado (solo relevante para cuentas privadas).

### 4.7 Aceptar o rechazar una solicitud

```
POST /api/follow-requests/{id}/accept
DELETE /api/follow-requests/{id}   (rechazar)
```

También se puede gestionar desde el panel de notificaciones:
```
POST /api/notifications/follow-requests/{followId}/accept
DELETE /api/notifications/follow-requests/{followId}
```

Ambas rutas hacen exactamente lo mismo. La ruta de notificaciones es la que usa el frontend cuando el usuario interactúa con el panel de notificaciones.

---

## 5. Privacidad del perfil y el feed

### Impacto de `isPrivate`

| Acción | Perfil público | Perfil privado |
|--------|---------------|----------------|
| Ver perfil (`GET /api/users/{id}`) | Disponible | Disponible (datos básicos) |
| Seguir | Inmediato (`accepted`) | Solicitud (`pending`) |
| Ver posts de ese usuario | Disponibles | Solo si le sigues |

> **Nota actual:** La visibilidad de los posts de cuentas privadas la controla el frontend usando el estado del `followStatus`. El backend devuelve siempre los posts de `GET /api/users/{id}/posts`; es el cliente quien decide mostrarlos o no según si el perfil es privado y si se le sigue.

### Impacto de `shelvesPublic` y `clubsPublic`

En `GET /api/users/{id}`, el backend comprueba estos campos antes de incluir los datos:

```php
'shelves' => $user->isShelvesPublic()
    ? [...datos de estanterías...]
    : null,

'clubs' => $user->isClubsPublic()
    ? [...datos de clubes...]
    : null,
```

Si el valor es `null`, el frontend sabe que esa sección está oculta.

---

## 6. Relación entre el módulo social y otros módulos

```
Follow ──► Feed (PostRepository::findFeed usa la tabla follow)
Post ────► Notification (like, comment)
Follow ──► Notification (follow, follow_request, follow_accepted)
User ────► Post (autor)
User ────► Follow (follower / following)
```

El módulo social es el pegamento que conecta a los usuarios entre sí y genera el flujo de notificaciones.
