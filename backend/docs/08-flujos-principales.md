# 08 — Flujos principales de la aplicación

Este documento describe los flujos más importantes de extremo a extremo, mostrando cómo interactúan los distintos componentes (controladores, entidades, repositorios) para completar cada caso de uso.

---

## Flujo 1: Registro e inicio de sesión

### 1.1 Registro

```
React                          AuthApiController          EntityManager
  │                                   │                        │
  │  POST /api/auth/register           │                        │
  │  { email, password, displayName }  │                        │
  │──────────────────────────────────►│                        │
  │                                   │ Valida email/contraseña │
  │                                   │ Comprueba email único  │
  │                                   │ Hashhea contraseña     │
  │                                   │ Genera displayName     │
  │                                   │────────────────────────►
  │                                   │                  persist(User)
  │                                   │                  flush()
  │  201 { id, email }                │                        │
  │◄──────────────────────────────────│                        │
```

**Generación de displayName:**
1. Si el cliente envía `displayName`, se sanitiza (solo letras, números y `_`).
2. Si no envía `displayName`, se usa la parte local del email (antes del `@`).
3. Si el nombre ya existe, se añade un sufijo numérico hasta encontrar uno libre: `usuario`, `usuario1`, `usuario2`...

### 1.2 Login

```
React                   Symfony Firewall      JsonLoginSuccessHandler
  │                           │                        │
  │  POST /api/login           │                        │
  │  { email, password }       │                        │
  │──────────────────────────►│                        │
  │                           │ Busca usuario por email│
  │                           │ Verifica contraseña    │
  │                           │ Crea sesión PHP         │
  │                           │────────────────────────►
  │                           │                   Devuelve datos usuario
  │  200 { id, email, displayName, avatar, roles }      │
  │◄──────────────────────────────────────────────────  │
  │  Set-Cookie: PHPSESSID=...│                        │
```

Desde este momento, todas las peticiones incluyen automáticamente la cookie `PHPSESSID` y el backend reconoce al usuario.

---

## Flujo 2: Añadir un libro a una estantería

Este flujo es uno de los más complejos porque involucra la Google Books API y la importación automática de libros.

```
React                  ShelfApiController     BookRepository    Google Books API
  │                           │                    │                  │
  │  POST /api/shelves/3/books │                    │                  │
  │  { externalId:"zyTC...", status:"reading" }     │                  │
  │──────────────────────────►│                    │                  │
  │                           │  findOneBy externalId                 │
  │                           │───────────────────►│                  │
  │                           │ null (no existe)   │                  │
  │                           │◄───────────────────│                  │
  │                           │                                       │
  │                           │  GET /volumes/zyTC...                 │
  │                           │──────────────────────────────────────►│
  │                           │  200 { volumeInfo: {...} }            │
  │                           │◄──────────────────────────────────────│
  │                           │ Crea entidad Book                     │
  │                           │ persist + flush                       │
  │                           │                                       │
  │                           │ Crea ShelfBook (libro + estantería)  │
  │                           │ persist + flush                       │
  │                           │                                       │
  │  201 { id, status, book:{...} }                                  │
  │◄──────────────────────────│                                       │
```

**Puntos clave:**
- El libro se importa **una sola vez**. La próxima vez que otro usuario añada el mismo libro, ya estará en la BD y no se hará ninguna llamada externa.
- Si Google Books no responde o el `externalId` no existe, se devuelve `404`.
- La restricción única `(shelf_id, book_id)` evita duplicados a nivel de BD.

---

## Flujo 3: Feed social y publicación de posts

### 3.1 Crear una publicación

```
React (multipart/form-data)    PostApiController         Disco local
  │                                  │                       │
  │  POST /api/posts                 │                       │
  │  image: [archivo.jpg]            │                       │
  │  description: "Mi lectura..."    │                       │
  │─────────────────────────────────►│                       │
  │                                  │ Valida extensión      │
  │                                  │ Genera nombre único   │
  │                                  │  "post_abc123.jpg"    │
  │                                  │──────────────────────►│
  │                                  │               public/uploads/posts/
  │                                  │ Crea entidad Post     │
  │                                  │ persist + flush       │
  │  201 { id, imagePath, ... }      │                       │
  │◄─────────────────────────────────│                       │
```

### 3.2 Cargar el feed

```
React              PostApiController      PostRepository (QueryBuilder)    BD
  │                       │                        │                       │
  │  GET /api/posts        │                        │                       │
  │──────────────────────►│                        │                       │
  │                       │  findFeed(me, 40)       │                       │
  │                       │───────────────────────►│                       │
  │                       │                        │  SELECT p.* FROM post p
  │                       │                        │  LEFT JOIN follow f ON
  │                       │                        │    f.follower=me AND
  │                       │                        │    f.following=p.user AND
  │                       │                        │    f.status='accepted'
  │                       │                        │  WHERE p.user=me OR f.id IS NOT NULL
  │                       │                        │  ORDER BY created_at DESC LIMIT 40
  │                       │                        │──────────────────────►│
  │                       │   array de Post[]       │                       │
  │                       │◄───────────────────────│                       │
  │                       │ Para cada post:         │                       │
  │                       │  - cuenta likes         │                       │
  │                       │  - ¿liked por mí?       │                       │
  │                       │  - cuenta comentarios   │                       │
  │  200 [ {...}, {...} ]  │                        │                       │
  │◄──────────────────────│                        │                       │
```

---

## Flujo 4: Sistema de seguimiento (Follow)

### 4.1 Seguir a un usuario con perfil público

```
React            FollowApiController         EntityManager
  │                     │                         │
  │  POST /api/users/7/follow                     │
  │────────────────────►│                         │
  │                     │ ¿Ya le sigo? → No       │
  │                     │ ¿Es privado? → No        │
  │                     │                         │
  │                     │ new Follow(me, target, 'accepted')
  │                     │─────────────────────────►│  persist + flush
  │                     │                         │
  │                     │ new Notification(target, me, 'follow')
  │                     │─────────────────────────►│  persist + flush
  │                     │                         │
  │  200 { status:"accepted", isFollowing:true }  │
  │◄────────────────────│                         │
```

### 4.2 Seguir a un usuario con perfil privado

```
React            FollowApiController         EntityManager
  │                     │                         │
  │  POST /api/users/7/follow                     │
  │────────────────────►│                         │
  │                     │ ¿Es privado? → SÍ       │
  │                     │                         │
  │                     │ new Follow(me, target, 'pending')
  │                     │─────────────────────────►│  persist + flush
  │                     │                         │
  │                     │ new Notification(target, me, 'follow_request', refId=follow.id)
  │                     │─────────────────────────►│  persist + flush
  │                     │                         │
  │  200 { status:"pending", isFollowing:false }  │
  │◄────────────────────│                         │
```

### 4.3 Aceptar una solicitud de seguimiento

```
React (perfil privado)   NotificationApiController    EntityManager
  │                              │                         │
  │  POST /api/notifications/follow-requests/42/accept    │
  │─────────────────────────────►│                         │
  │                              │ Busca Follow(id=42)     │
  │                              │ Verifica que yo soy el  │
  │                              │ destinatario            │
  │                              │ follow.accept()         │
  │                              │ flush()                 │
  │                              │                         │
  │                              │ new Notification(requester, me, 'follow_accepted')
  │                              │─────────────────────────►│
  │                              │                         │
  │                              │ deleteByRefIdAndType(me, 'follow_request', 42)
  │                              │ (limpia la notif original)
  │                              │─────────────────────────►│
  │  200 { status:"accepted" }  │                         │
  │◄─────────────────────────────│                         │
```

---

## Flujo 5: Unirse a un club privado

```
React                ClubApiController           EntityManager     Notifications
  │                        │                          │                 │
  │  POST /api/clubs/5/join│                          │                 │
  │───────────────────────►│                          │                 │
  │                        │ ¿Es privado? → SÍ        │                 │
  │                        │ ¿Ya soy miembro? → No    │                 │
  │                        │ ¿Solicitud pendiente? → No│                │
  │                        │                          │                 │
  │                        │ new ClubJoinRequest(club, me, 'pending')  │
  │                        │──────────────────────────►│                │
  │                        │                          │                 │
  │                        │ Para cada admin del club:│                 │
  │                        │  new Notification(admin, me, 'club_request', club, refId=req.id)
  │                        │──────────────────────────────────────────►│
  │                        │                          │                 │
  │  200 { status:"pending" }                        │                 │
  │◄───────────────────────│                          │                 │
```

Cuando el admin aprueba desde las notificaciones:

```
Admin → POST /api/notifications/club-requests/{reqId}/approve
      → ClubJoinRequest.status = 'approved'
      → new ClubMember(club, user, 'member')
      → new Notification(user, admin, 'club_approved', club)
      → deleteByRefIdAndType(admin, 'club_request', reqId)
```

---

## Flujo 6: Reseña de un libro (upsert)

El endpoint de reseñas usa un patrón **upsert** (crear si no existe, actualizar si ya existe):

```
React                  BookReviewApiController     BookReviewRepository
  │                           │                           │
  │  POST /api/books/zyTC.../reviews                      │
  │  { rating: 4, content:"..." }                         │
  │──────────────────────────►│                           │
  │                           │ ¿Existe el libro en BD? → No → importar de Google
  │                           │                           │
  │                           │  findOneByUserAndBook(me, book)
  │                           │──────────────────────────►│
  │                           │                           │
  │                           │  ── Si existe ──           │
  │                           │  review.setRating(4)       │
  │                           │  review.setContent("...")  │
  │                           │  flush()                  │
  │                           │                           │
  │                           │  ── Si no existe ──        │
  │                           │  new BookReview(me, book, 4, "...")
  │                           │  persist + flush           │
  │                           │                           │
  │                           │  getStats(book) → media, distribución
  │                           │──────────────────────────►│
  │  201 { review:{...}, stats:{average, count, dist} }  │
  │◄──────────────────────────│                           │
```

---

## Flujo 7: Progreso de lectura

El progreso soporta dos modos que el usuario puede cambiar en cualquier momento:

```
Modo "pages"                     Modo "percent"
─────────────────────            ─────────────────────
POST → mode: "pages"             POST → mode: "percent"
        totalPages: 350                  (sin totalPages)
                                 
PATCH → currentPage: 125         PATCH → percent: 35
                                 
computed = (125/350)*100 = 35.7% computed = 35%
```

La propiedad `computed` en la respuesta siempre devuelve el porcentaje calculado independientemente del modo, para que el frontend pueda mostrar una barra de progreso unificada.

---

## Resumen de patrones comunes

| Patrón | Descripción | Dónde se usa |
|--------|-------------|--------------|
| **Importación lazy de libros** | El libro se crea en BD la primera vez que se referencia | AddBook, CreateReview, AddProgress |
| **Upsert** | Crear si no existe, actualizar si existe | BookReview |
| **Notificación automática** | Cada acción social genera una notificación | Follow, Like, Comment, ClubJoin |
| **Limpieza de notificaciones** | Al procesar una solicitud, se borra la notificación original | AcceptFollow, ApproveClubJoin |
| **404 en vez de 403** | Para no revelar existencia de recursos ajenos | Todos los controladores de recursos |
| **Toggle** | Una misma ruta añade o quita según el estado actual | PostLike |
| **Cascade delete** | Borrar usuario/post/club limpia datos relacionados | FK `ON DELETE CASCADE` en migraciones |
