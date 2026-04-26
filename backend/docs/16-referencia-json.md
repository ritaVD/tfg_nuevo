# 16 — Referencia de respuestas JSON

Este documento recoge la estructura completa de todas las respuestas JSON de la API. Es una referencia rápida para el desarrollo del frontend o para integrar con la API.

---

## Convenciones

- Las fechas siguen el formato **ISO 8601** con zona horaria: `"2026-04-19T10:30:00+00:00"`.
- Las fechas solo de día (sin hora) usan `"YYYY-MM-DD"`: `"2026-04-30"`.
- Los campos opcionales pueden ser `null`.
- Las listas vacías devuelven `[]`, no `null`.
- Los endpoints de eliminación devuelven **204 No Content** (sin cuerpo).
- Los errores siempre tienen esta estructura: `{ "error": "Mensaje descriptivo" }`.

---

## 1. Autenticación

### `POST /api/login` → 200
```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "displayName": "MiNombre",
  "avatar": "abc123.jpg",
  "roles": ["ROLE_USER"]
}
```

### `GET /api/auth/me` → 200
```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "displayName": "MiNombre",
  "avatar": "abc123.jpg",
  "roles": ["ROLE_USER"]
}
```

### `POST /api/auth/register` → 201
```json
{
  "id": 42,
  "email": "nuevo@ejemplo.com"
}
```

---

## 2. Perfil de usuario

### `GET /api/profile` → 200 (perfil propio completo)
```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "displayName": "MiNombre",
  "bio": "Amante de la fantasía épica",
  "avatar": "abc123.jpg",
  "isPrivate": false,
  "shelvesPublic": true,
  "clubsPublic": true,
  "followers": 34,
  "following": 21,
  "shelves": [
    { "id": 3, "name": "Por leer" },
    { "id": 4, "name": "Leídos" }
  ],
  "clubs": [
    {
      "id": 5,
      "name": "Club de Fantasía",
      "visibility": "public",
      "role": "member"
    }
  ]
}
```

### `GET /api/users/{id}` → 200 (perfil público)
```json
{
  "id": 7,
  "displayName": "MariaG",
  "bio": "Lectora voraz",
  "avatar": "avatar_7.jpg",
  "followers": 120,
  "following": 45,
  "followStatus": "accepted",
  "isFollowing": true,
  "shelves": [
    {
      "id": 8,
      "name": "Favoritos",
      "books": [
        {
          "id": 12,
          "title": "Dune",
          "authors": ["Frank Herbert"],
          "coverUrl": "https://...",
          "thumbnail": "https://..."
        }
      ]
    }
  ],
  "clubs": [
    {
      "id": 5,
      "name": "Club de Fantasía",
      "visibility": "public",
      "role": "member"
    }
  ]
}
```

> `shelves` y `clubs` son `null` si el usuario tiene desactivada esa visibilidad.  
> `followStatus` puede ser `"none"`, `"pending"` o `"accepted"`.

### `GET /api/users/search?q=maria` → 200
```json
[
  {
    "id": 7,
    "displayName": "MariaG",
    "avatar": "avatar_7.jpg",
    "bio": "Lectora voraz",
    "followers": 120,
    "followStatus": "none",
    "isMe": false
  }
]
```

---

## 3. Posts

### `GET /api/posts` / `GET /api/users/{id}/posts` → 200
```json
[
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
]
```

### `POST /api/posts/{id}/like` → 200
```json
{
  "liked": true,
  "likes": 9
}
```

### `GET /api/posts/{id}/comments` → 200
```json
[
  {
    "id": 101,
    "content": "¡Qué buena foto!",
    "createdAt": "2026-04-19T11:00:00+00:00",
    "user": {
      "id": 3,
      "displayName": "JorgeL",
      "avatar": null
    }
  }
]
```

---

## 4. Estanterías

### `GET /api/shelves` → 200
```json
[
  { "id": 3, "name": "Por leer" },
  { "id": 4, "name": "Leídos" }
]
```

### `GET /api/shelves/full` → 200
```json
[
  {
    "id": 3,
    "name": "Por leer",
    "books": [
      {
        "id": 23,
        "status": "want_to_read",
        "orderIndex": 0,
        "addedAt": "2026-04-01T12:00:00+00:00",
        "book": {
          "id": 7,
          "externalId": "zyTCAlFPjgYC",
          "title": "Dune",
          "authors": ["Frank Herbert"],
          "publisher": "Debolsillo",
          "publishedDate": "2003",
          "coverUrl": "https://...",
          "description": "En el planeta Arrakis...",
          "pageCount": 896,
          "categories": ["Fiction"],
          "language": "es",
          "isbn10": "8497594610",
          "isbn13": "9788497594615"
        }
      }
    ]
  }
]
```

---

## 5. Búsqueda de libros

### `GET /api/books/search?q=dune` → 200
```json
{
  "page": 1,
  "limit": 20,
  "totalItems": 143,
  "results": [
    {
      "externalId": "zyTCAlFPjgYC",
      "title": "Dune",
      "subtitle": null,
      "authors": ["Frank Herbert"],
      "publisher": "Debolsillo",
      "publishedDate": "2003",
      "categories": ["Fiction"],
      "language": "es",
      "description": "En el planeta Arrakis...",
      "pageCount": 896,
      "averageRating": 4.5,
      "ratingsCount": 8523,
      "thumbnail": "https://books.google.com/...",
      "previewLink": "https://books.google.es/...",
      "infoLink": "https://play.google.com/...",
      "isbn10": "8497594610",
      "isbn13": "9788497594615"
    }
  ]
}
```

---

## 6. Reseñas

### `GET /api/books/{externalId}/reviews` → 200
```json
{
  "stats": {
    "average": 4.3,
    "count": 27,
    "distribution": { "1": 1, "2": 2, "3": 3, "4": 8, "5": 13 }
  },
  "myRating": {
    "id": 5,
    "rating": 4,
    "content": "Una obra maestra de la ciencia ficción."
  },
  "reviews": [
    {
      "id": 5,
      "rating": 4,
      "content": "Una obra maestra de la ciencia ficción.",
      "createdAt": "2026-04-10T15:00:00+00:00",
      "user": {
        "id": 2,
        "displayName": "MariaG",
        "avatar": "avatar_2.jpg"
      }
    }
  ]
}
```

> Si no hay reseñas: `stats.average = null`, `stats.count = 0`, `myRating = null`, `reviews = []`.

---

## 7. Progreso de lectura

### `GET /api/reading-progress` / `POST /api/reading-progress` → 200/201
```json
[
  {
    "id": 12,
    "mode": "pages",
    "currentPage": 250,
    "totalPages": 896,
    "percent": null,
    "computed": 27.9,
    "startedAt": "2026-04-01T00:00:00+00:00",
    "updatedAt": "2026-04-19T20:00:00+00:00",
    "book": {
      "id": 7,
      "externalId": "zyTCAlFPjgYC",
      "title": "Dune",
      "authors": ["Frank Herbert"],
      "coverUrl": "https://...",
      "pageCount": 896
    }
  }
]
```

---

## 8. Clubes

### `GET /api/clubs` → 200
```json
[
  {
    "id": 5,
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
      "publishedDate": "2008",
      "since": "2026-04-01",
      "until": "2026-04-30"
    }
  }
]
```

> `userRole` es `null` si no eres miembro. `hasPendingRequest` es `true` si tienes una solicitud pendiente. `currentBook` es `null` si el club no tiene libro activo.

### `GET /api/clubs/{id}` → 200
```json
{
  "id": 5,
  "name": "Club de Fantasía",
  "description": "Lectores de fantasía épica",
  "visibility": "public",
  "memberCount": 12,
  "userRole": "admin",
  "hasPendingRequest": false,
  "currentBook": { ... },
  "owner": {
    "id": 1,
    "email": "creador@ejemplo.com",
    "displayName": "Creador"
  }
}
```

### `GET /api/clubs/{id}/members` → 200
```json
[
  {
    "id": 10,
    "role": "admin",
    "joinedAt": "2026-02-15T09:00:00+00:00",
    "user": {
      "id": 1,
      "displayName": "Creador",
      "avatar": "abc.jpg"
    }
  },
  {
    "id": 11,
    "role": "member",
    "joinedAt": "2026-03-01T14:00:00+00:00",
    "user": {
      "id": 7,
      "displayName": "MariaG",
      "avatar": "avatar_7.jpg"
    }
  }
]
```

### `POST /api/clubs/{id}/join` → 200
```json
{ "status": "joined", "role": "member" }
// o
{ "status": "requested" }
// o
{ "status": "already_member", "role": "admin" }
```

---

## 9. Hilos de debate

### `GET /api/clubs/{id}/chats` → 200
```json
[
  {
    "id": 3,
    "title": "¿Qué os parece el capítulo 5?",
    "isOpen": true,
    "messageCount": 14,
    "createdAt": "2026-04-10T08:00:00+00:00",
    "closedAt": null,
    "createdBy": {
      "id": 1,
      "displayName": "Creador",
      "avatar": "abc.jpg"
    }
  }
]
```

### `GET /api/clubs/{id}/chats/{chatId}/messages` → 200
```json
{
  "page": 1,
  "limit": 50,
  "total": 14,
  "messages": [
    {
      "id": 101,
      "content": "Muy intenso, no me lo esperaba.",
      "createdAt": "2026-04-10T09:15:00+00:00",
      "user": {
        "id": 7,
        "displayName": "MariaG",
        "avatar": "avatar_7.jpg"
      }
    }
  ]
}
```

---

## 10. Notificaciones

### `GET /api/notifications` → 200
```json
{
  "unread": 3,
  "items": [
    {
      "id": 201,
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
        "imagePath": "post_6716a3b4e5f12.jpg"
      },
      "club": null
    },
    {
      "id": 202,
      "type": "follow_request",
      "isRead": false,
      "createdAt": "2026-04-19T09:00:00+00:00",
      "refId": 88,
      "actor": {
        "id": 12,
        "displayName": "PedroM",
        "avatar": null
      },
      "post": null,
      "club": null
    },
    {
      "id": 203,
      "type": "club_request",
      "isRead": true,
      "createdAt": "2026-04-18T14:00:00+00:00",
      "refId": 45,
      "actor": {
        "id": 15,
        "displayName": "LuisR",
        "avatar": "avatar_15.jpg"
      },
      "post": null,
      "club": {
        "id": 5,
        "name": "Club de Fantasía"
      }
    }
  ]
}
```

---

## 11. Administración

### `GET /api/admin/stats` → 200
```json
{
  "users": 154,
  "clubs": 23,
  "posts": 891
}
```

### `GET /api/admin/users` → 200
```json
[
  {
    "id": 1,
    "email": "admin@ejemplo.com",
    "displayName": "Admin",
    "avatar": null,
    "roles": ["ROLE_ADMIN", "ROLE_USER"],
    "isVerified": true,
    "isAdmin": true
  }
]
```

---

## 12. Códigos HTTP utilizados

| Código | Cuándo se usa |
|--------|--------------|
| `200 OK` | Petición exitosa con datos en la respuesta |
| `201 Created` | Recurso creado correctamente |
| `204 No Content` | Operación exitosa sin datos que devolver (eliminaciones) |
| `400 Bad Request` | Datos de entrada inválidos (campo vacío, formato incorrecto) |
| `401 Unauthorized` | Sin sesión activa (no autenticado) |
| `403 Forbidden` | Autenticado pero sin permiso para la acción |
| `404 Not Found` | El recurso no existe o no te pertenece |
| `409 Conflict` | Violación de unicidad (email duplicado, like duplicado) |
| `502 Bad Gateway` | Error al contactar con Google Books API |
