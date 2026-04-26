# 10 — Sistema de notificaciones

El sistema de notificaciones informa a los usuarios de la actividad relevante que ocurre en su entorno social: nuevos seguidores, likes, comentarios, solicitudes de unión a clubes y respuestas a esas solicitudes.

---

## 1. Tipos de notificación

| Constante | Valor string | Quién la recibe | Cuándo se genera |
|-----------|-------------|-----------------|-----------------|
| `TYPE_FOLLOW` | `"follow"` | Usuario seguido | Alguien empieza a seguirle (cuenta pública) |
| `TYPE_FOLLOW_REQUEST` | `"follow_request"` | Usuario seguido | Alguien solicita seguirle (cuenta privada) |
| `TYPE_FOLLOW_ACCEPTED` | `"follow_accepted"` | El que envió la solicitud | Su solicitud fue aceptada |
| `TYPE_LIKE` | `"like"` | Autor del post | Alguien le da like a su publicación |
| `TYPE_COMMENT` | `"comment"` | Autor del post | Alguien comenta su publicación |
| `TYPE_CLUB_REQUEST` | `"club_request"` | Admin del club | Un usuario solicita unirse a su club privado |
| `TYPE_CLUB_APPROVED` | `"club_approved"` | El que envió la solicitud | Su solicitud de unión fue aprobada |
| `TYPE_CLUB_REJECTED` | `"club_rejected"` | El que envió la solicitud | Su solicitud de unión fue rechazada |

---

## 2. Cuándo se crean las notificaciones

Las notificaciones se crean directamente en los controladores, justo después de la acción que las provoca. No hay un servicio centralizado; cada controlador se encarga de sus notificaciones.

### En `FollowApiController`

```php
// Al seguir a alguien con cuenta pública
new Notification($target, $me, Notification::TYPE_FOLLOW);

// Al seguir a alguien con cuenta privada
new Notification($target, $me, Notification::TYPE_FOLLOW_REQUEST,
    null, null, $follow->getId()  // refId = Follow.id
);

// Al aceptar una solicitud de seguimiento
new Notification($requester, $me, Notification::TYPE_FOLLOW_ACCEPTED);
```

### En `PostApiController`

```php
// Al dar like
new Notification($post->getUser(), $me, Notification::TYPE_LIKE, $post);

// Al comentar
new Notification($post->getUser(), $me, Notification::TYPE_COMMENT, $post);
```

> **Nota:** El like y el comentario no generan notificación si el autor del post soy yo mismo (no tiene sentido notificarse a uno mismo).

### En `ClubApiController`

```php
// Al solicitar unirse a un club privado (se notifica a cada admin)
foreach ($admins as $admin) {
    new Notification($admin, $me, Notification::TYPE_CLUB_REQUEST,
        null, $club, $joinRequest->getId()  // refId = ClubJoinRequest.id
    );
}

// Al aprobar una solicitud
new Notification($user, $me, Notification::TYPE_CLUB_APPROVED, null, $club);

// Al rechazar una solicitud
new Notification($user, $me, Notification::TYPE_CLUB_REJECTED, null, $club);
```

---

## 3. El campo `refId`

El campo `refId` es un ID de referencia auxiliar que se usa en las notificaciones que requieren una acción posterior:

| Tipo | `refId` contiene | Para qué se usa |
|------|-----------------|-----------------|
| `follow_request` | `Follow.id` | Permite aceptar/rechazar directamente desde la notificación |
| `club_request` | `ClubJoinRequest.id` | Permite aprobar/rechazar directamente desde la notificación |

Cuando el React muestra una notificación de solicitud, incluye el `refId` en la URL del botón de acción, por ejemplo:
- `POST /api/notifications/follow-requests/{refId}/accept`
- `POST /api/notifications/club-requests/{refId}/approve`

---

## 4. Ciclo de vida de una notificación de solicitud

```
                    ESTADO INICIAL
                         │
              Usuario A envía solicitud
                         │
                         ▼
            ┌────────────────────────┐
            │  Notification creada   │
            │  type: follow_request  │
            │  isRead: false         │
            │  refId: Follow.id      │
            └────────────────────────┘
                         │
                ┌────────┴────────┐
                │                 │
         ACEPTA (accept)    RECHAZA (decline)
                │                 │
                ▼                 ▼
      Follow.status =        Follow eliminado
        'accepted'           
                │                 │
                │                 │
    Notification(requester,       │
    TYPE_FOLLOW_ACCEPTED)         │
                │                 │
                └────────┬────────┘
                         │
              deleteByRefIdAndType()
              (elimina la notif original)
                         │
                         ▼
                  PROCESO COMPLETO
```

Esto garantiza que:
1. El recipiente no vea solicitudes ya procesadas en su bandeja.
2. El solicitante recibe feedback del resultado.

---

## 5. Endpoints del sistema de notificaciones

### `GET /api/notifications`
Devuelve las notificaciones de las **últimas 72 horas** (máx. 30), más el conteo de no leídas.

```json
{
  "unread": 3,
  "items": [
    {
      "id": 101,
      "type": "like",
      "isRead": false,
      "createdAt": "2026-04-19T10:30:00+00:00",
      "refId": null,
      "actor": {
        "id": 7,
        "displayName": "MariaG",
        "avatar": "avatar_7.jpg"
      },
      "post": {
        "id": 55,
        "imagePath": "post_abc123.jpg"
      },
      "club": null
    }
  ]
}
```

### `GET /api/notifications/history`
Historial completo (máx. 100), sin límite de 72 horas.

### `POST /api/notifications/read-all`
Marca todas como leídas en una sola operación SQL:
```sql
UPDATE notification SET is_read = 1 WHERE recipient_id = :user AND is_read = 0
```

### `POST /api/notifications/follow-requests/{followId}/accept`
Proceso completo de aceptar seguimiento:
1. Cambia `Follow.status` a `accepted`.
2. Crea notificación `follow_accepted` para el solicitante.
3. Elimina la notificación `follow_request` original.

### `DELETE /api/notifications/follow-requests/{followId}` (rechazar)
1. Elimina el registro `Follow`.
2. Elimina la notificación `follow_request` original.

---

## 6. Estructura de la tabla `notification` en BD

```sql
notification
├── id             INT (PK)
├── recipient_id   INT (FK → user, CASCADE)   -- quien recibe
├── actor_id       INT (FK → user, CASCADE)   -- quien genera la acción
├── type           VARCHAR(30)                 -- tipo de notificación
├── post_id        INT (FK → post, CASCADE)   -- post relacionado, opcional
├── club_id        INT (FK → club, CASCADE)   -- club relacionado, opcional
├── ref_id         INT                         -- ID auxiliar para acciones
├── is_read        TINYINT DEFAULT 0           -- 0=no leída, 1=leída
└── created_at     DATETIME
```

**Índices:** La tabla tiene índices en `recipient_id`, `actor_id`, `post_id` y `club_id` para acelerar las consultas filtradas por receptor.

**Cascade:** Todas las FK tienen `ON DELETE CASCADE`. Si se elimina un usuario, post o club, todas sus notificaciones relacionadas desaparecen automáticamente.

---

## 7. Ventana temporal de notificaciones recientes

El endpoint `GET /api/notifications` solo muestra notificaciones de las **últimas 72 horas**. Esto es una decisión de diseño consciente:
- Evita mostrar notificaciones muy antiguas que ya no son relevantes.
- Mantiene el panel de notificaciones limpio y activo.
- El historial completo está disponible en `/api/notifications/history` si el usuario quiere ver más.

---

## 8. Representación en el frontend

El frontend React usa el campo `type` para decidir qué texto y qué icono mostrar:

| `type` | Mensaje en pantalla |
|--------|-------------------|
| `follow` | **MariaG** ha empezado a seguirte |
| `follow_request` | **MariaG** quiere seguirte _(con botones Aceptar/Rechazar)_ |
| `follow_accepted` | **MariaG** ha aceptado tu solicitud |
| `like` | **MariaG** le gusta tu publicación |
| `comment` | **MariaG** ha comentado tu publicación |
| `club_request` | **MariaG** quiere unirse a **MiClub** _(con botones Aprobar/Rechazar)_ |
| `club_approved` | Tu solicitud para **MiClub** fue aprobada |
| `club_rejected` | Tu solicitud para **MiClub** fue rechazada |
