# 13 — Módulo de Clubes

Los clubes de lectura son el núcleo de la plataforma. Un club agrupa a varios usuarios en torno a la lectura de libros, con su propio sistema de membresía, solicitudes de ingreso, libro activo y foros de debate internos.

---

## 1. Estructura de un club

```
Club
├── Datos básicos: name, description, visibility
├── Owner (User) → el creador, siempre admin
├── Members (ClubMember[]) → usuarios con rol 'admin' o 'member'
├── JoinRequests (ClubJoinRequest[]) → solicitudes pendientes (clubs privados)
├── CurrentBook (Book?) → el libro que el club lee ahora
│   ├── currentBookSince (fecha de inicio)
│   └── currentBookUntil (fecha objetivo de fin)
└── Chats (ClubChat[]) → hilos de debate
    └── Messages (ClubChatMessage[]) → mensajes en cada hilo
```

---

## 2. Tipos de club y acceso

| Visibilidad | Unirse | Ver miembros | Ver chats |
|-------------|--------|-------------|-----------|
| `public` | Inmediato (sin aprobación) | Cualquiera | Cualquiera |
| `private` | Requiere solicitud y aprobación | Solo miembros | Solo miembros |

---

## 3. Roles dentro del club

| Rol | Quién lo tiene | Permisos |
|-----|---------------|----------|
| `admin` | El creador del club y quien sea promovido | Editar club, gestionar miembros, crear hilos, aprobar/rechazar solicitudes, establecer libro del mes |
| `member` | Usuarios que se unieron | Ver contenido, enviar mensajes en hilos abiertos |

Un admin **no puede abandonar** el club si hay otros miembros. Debe transferir el rol de admin a otro miembro antes de salir.

---

## 4. Estados de membresía desde el punto de vista del usuario

```
No miembro
    │
    ├── Club público  →  POST /api/clubs/{id}/join  →  Miembro (member)
    │
    └── Club privado  →  POST /api/clubs/{id}/join  →  Solicitud pendiente
                                │
                          Admin aprueba  →  Miembro (member)
                          Admin rechaza  →  No miembro
```

---

## 5. Endpoints detallados

### `GET /api/clubs`
Lista todos los clubes. Para cada club incluye el rol del usuario autenticado (`userRole`) y si tiene una solicitud pendiente (`hasPendingRequest`). Esto permite al frontend mostrar el estado correcto en el botón de cada club sin peticiones adicionales.

```json
[
  {
    "id": 3,
    "name": "Club de Fantasía",
    "description": "Lectores de fantasía épica",
    "visibility": "public",
    "memberCount": 12,
    "userRole": "member",
    "hasPendingRequest": false,
    "currentBook": {
      "id": 7,
      "externalId": "zyTCAlFPjgYC",
      "title": "El Nombre del Viento",
      "authors": ["Patrick Rothfuss"],
      "coverUrl": "https://...",
      "since": "2026-04-01",
      "until": "2026-04-30"
    }
  }
]
```

### `POST /api/clubs`
Crea un club. El creador queda registrado automáticamente como **admin** del club al mismo tiempo que se crea el club (dos inserciones en la misma transacción).

**Body:**
```json
{ "name": "Mi Club", "description": "...", "visibility": "public" }
```

### `GET /api/clubs/{id}`
Detalle completo del club con el rol del usuario actual y si tiene solicitud pendiente.

### `PATCH /api/clubs/{id}`
Modifica nombre, descripción o visibilidad. Solo admins del club. Actualiza `updatedAt` automáticamente.

### `DELETE /api/clubs/{id}`
Elimina el club y, por `orphanRemoval`/`CASCADE`, todos sus miembros, solicitudes, hilos y mensajes. Solo el admin del club o un `ROLE_ADMIN` global.

---

## 6. Gestión de membresía

### `POST /api/clubs/{id}/join`
Resultado según la visibilidad:

| Visibilidad | Estado de solicitud | Respuesta |
|-------------|--------------------|-----------| 
| `public` | — | `{ "status": "joined", "role": "member" }` |
| `private` | pending | `{ "status": "requested" }` |
| ya miembro | — | `{ "status": "already_member", "role": "..." }` |
| ya solicitado | — | `{ "status": "already_requested" }` |

### `DELETE /api/clubs/{id}/leave`
Abandona el club. Regla especial: si eres el **único admin** y hay más miembros, el sistema rechaza la salida con `400` y pide que se transfiera el rol primero.

### `GET /api/clubs/{id}/members`
Lista de miembros. En clubs privados, solo accesible para los propios miembros.

### `DELETE /api/clubs/{id}/members/{memberId}`
Expulsa a un miembro. El admin no puede expulsarse a sí mismo (debe usar `/leave`).

---

## 7. Gestión de solicitudes (clubs privados)

### `GET /api/clubs/{id}/requests`
Lista las solicitudes con estado `pending`. Solo para admins.

### `POST /api/clubs/{id}/requests/{requestId}/approve`
Aprueba la solicitud:
1. `ClubJoinRequest.status` → `approved`
2. `ClubJoinRequest.resolvedBy` → admin actual
3. `ClubJoinRequest.resolvedAt` → ahora
4. Crea `ClubMember` con rol `member`
5. Envía `Notification(TYPE_CLUB_APPROVED)` al solicitante
6. Elimina la `Notification(TYPE_CLUB_REQUEST)` del admin

### `POST /api/clubs/{id}/requests/{requestId}/reject`
Rechaza la solicitud (mismo flujo pero sin crear `ClubMember` y con `TYPE_CLUB_REJECTED`).

---

## 8. Libro del mes

El club puede tener un libro activo que todos los miembros leen en paralelo.

### `PUT /api/clubs/{id}/current-book`
Establece el libro del mes. Si el libro no está en BD, se importa de Google Books automáticamente.

**Body:**
```json
{
  "externalId": "zyTCAlFPjgYC",
  "dateFrom": "2026-04-01",
  "dateUntil": "2026-04-30"
}
```

- `dateFrom` es opcional (default: hoy).
- `dateUntil` es opcional (sin fecha límite si no se indica).
- La fecha de fin debe ser posterior a la de inicio.

**Respuesta:**
```json
{
  "id": 7,
  "externalId": "zyTCAlFPjgYC",
  "title": "El Nombre del Viento",
  "authors": ["Patrick Rothfuss"],
  "coverUrl": "https://...",
  "since": "2026-04-01",
  "until": "2026-04-30"
}
```

### `DELETE /api/clubs/{id}/current-book`
Quita el libro del mes (pone `currentBook`, `currentBookSince` y `currentBookUntil` a `null`).

---

## 9. Hilos de debate (ClubChat)

Los hilos organizan las conversaciones del club en temas separados.

### Estados de un hilo

```
Creado (isOpen: true)
    │
    ├── Admin cierra  →  isOpen: false, closedAt: timestamp
    │                    (nadie puede enviar mensajes)
    │
    └── Admin reabre  →  isOpen: true, closedAt: null
```

### Permisos por acción

| Acción | ¿Quién puede? |
|--------|--------------|
| Ver lista de hilos | Cualquiera (clubs públicos) / Solo miembros (clubs privados) |
| Crear hilo | Solo admins del club |
| Editar hilo (título, isOpen) | El creador del hilo o cualquier admin |
| Eliminar hilo | Solo admins del club |
| Enviar mensaje | Cualquier miembro (solo en hilos abiertos) |
| Borrar mensaje | El autor del mensaje o cualquier admin |

### `GET /api/clubs/{clubId}/chats/{chatId}/messages`
Los mensajes se devuelven paginados, ordenados de más antiguo a más reciente (para lectura cronológica natural):

```json
{
  "page": 1,
  "limit": 50,
  "total": 127,
  "messages": [
    {
      "id": 1,
      "content": "¿Qué os parece el capítulo 3?",
      "createdAt": "2026-04-05T10:30:00+00:00",
      "user": {
        "id": 2,
        "displayName": "MariaG",
        "avatar": "abc.jpg"
      }
    }
  ]
}
```

**Parámetros de paginación:**
- `page` (default: 1)
- `limit` (default: 50, máximo: 100)

La paginación aprovecha el índice compuesto `(chat_id, created_at)` de la tabla `club_chat_message`.

---

## 10. Helper `isAdmin()`

Todos los endpoints que requieren ser admin del club usan el método privado `isAdmin()`:

```php
private function isAdmin(Club $club, ClubMemberRepository $repo): bool
{
    $membership = $repo->findOneBy([
        'club' => $club,
        'user' => $this->getUser(),
    ]);
    return $membership?->getRole() === 'admin';
}
```

Esto es una **autorización a nivel de recurso**: no basta con ser `ROLE_USER`, hay que ser admin de ese club concreto.

---

## 11. Helper `resolveChat()`

El `ClubChatApiController` usa un helper que valida tanto el club como el hilo en una sola llamada, evitando repetir el mismo código en todos los métodos:

```php
private function resolveChat(int $clubId, int $chatId, ...): array
{
    $club = $clubRepo->find($clubId);
    if (!$club) return [null, null, $this->json(['error' => '...'], 404)];

    $chat = $chatRepo->find($chatId);
    if (!$chat || $chat->getClub() !== $club) 
        return [null, null, $this->json(['error' => '...'], 404)];

    return [$club, $chat, null];
}
```

Verificar que `$chat->getClub() !== $club` previene que se acceda a un hilo de otro club usando una URL manipulada (ej: `/api/clubs/1/chats/99` donde el chat 99 pertenece al club 5).
