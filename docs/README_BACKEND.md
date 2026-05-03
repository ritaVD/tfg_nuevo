# Documentación del Backend — TFGdaw
## Guía completa para la defensa del TFG

---

## Tabla de contenidos

1. [Stack tecnológico](#1-stack-tecnológico)
2. [Estructura del proyecto](#2-estructura-del-proyecto)
3. [Base de datos: Entidades y relaciones](#3-base-de-datos-entidades-y-relaciones)
4. [Autenticación y seguridad](#4-autenticación-y-seguridad)
5. [Feed de posts: proceso lógico completo](#5-feed-de-posts-proceso-lógico-completo)
6. [Sistema de seguimientos (Follows)](#6-sistema-de-seguimientos-follows)
7. [Posts: crear, listar, likear y comentar](#7-posts-crear-listar-likear-y-comentar)
8. [Estanterías y libros](#8-estanterías-y-libros)
9. [Integración con Google Books API](#9-integración-con-google-books-api)
10. [Clubes de lectura](#10-clubes-de-lectura)
11. [Chat de clubes](#11-chat-de-clubes)
12. [Reseñas de libros](#12-reseñas-de-libros)
13. [Progreso de lectura](#13-progreso-de-lectura)
14. [Notificaciones](#14-notificaciones)
15. [Perfil de usuario y privacidad](#15-perfil-de-usuario-y-privacidad)
16. [Referencia completa de endpoints](#16-referencia-completa-de-endpoints)

---

## 1. Stack tecnológico

| Componente       | Tecnología                        |
|-----------------|-----------------------------------|
| Lenguaje        | PHP 8.2+                          |
| Framework       | Symfony 7.4                       |
| ORM             | Doctrine ORM 3.6                  |
| Base de datos   | MySQL / MariaDB 10.4              |
| Autenticación   | Symfony Security Bundle           |
| API externa     | Google Books API v1               |
| Servidor HTTP   | Apache / Symfony CLI              |

El backend expone una **REST API stateful** (con sesión en cookie). El frontend (React) consume esta API mediante peticiones HTTP con `fetch`.

**¿Por qué Symfony?** Symfony es un framework PHP robusto y maduro que impone una arquitectura clara (MVC), incluye inyección de dependencias, un sistema de seguridad completo, y el ORM Doctrine que mapea objetos PHP a tablas de base de datos.

---

## 2. Estructura del proyecto

```
backend/
├── src/
│   ├── Controller/Api/        ← 11 controladores REST
│   │   ├── AuthApiController.php
│   │   ├── PostApiController.php
│   │   ├── FollowApiController.php
│   │   ├── UserApiController.php
│   │   ├── ShelfApiController.php
│   │   ├── BookExternalApiController.php
│   │   ├── BookReviewApiController.php
│   │   ├── ClubApiController.php
│   │   ├── ClubChatApiController.php
│   │   ├── NotificationApiController.php
│   │   └── ReadingProgressApiController.php
│   ├── Entity/                ← 16 modelos de BD
│   ├── Repository/            ← 16 repositorios (queries)
│   └── Security/              ← Handlers de login/logout
├── config/
│   ├── packages/security.yaml ← Configuración de autenticación
│   └── routes.yaml
├── migrations/                ← Historial de cambios en BD
└── public/
    └── uploads/
        ├── posts/             ← Imágenes de posts
        └── avatars/           ← Fotos de perfil
```

**Flujo de una petición HTTP:**
```
Cliente (React)
    → HTTP Request (fetch)
    → Symfony Router (identifica el controlador por URL + método HTTP)
    → Security Firewall (¿está autenticado? ¿tiene el rol necesario?)
    → Controlador (lógica: valida datos, consulta BD, devuelve JSON)
    → Doctrine ORM (traduce objetos PHP a SQL)
    → Base de datos MySQL
    ← JSON Response al cliente
```

---

## 3. Base de datos: Entidades y relaciones

Doctrine ORM utiliza **anotaciones PHP** (atributos `#[ORM\...]`) para mapear clases PHP a tablas de la base de datos. Cada propiedad de la clase se convierte en una columna.

### Entidades principales

#### User (usuarios)
```
id | email (UNIQUE) | password (hash) | displayName (UNIQUE)
bio | avatar | isVerified | roles (JSON)
shelvesPublic | clubsPublic | isPrivate
```

#### Post (publicaciones)
```
id | user_id (FK) | imagePath | description | createdAt
```
Relaciones: tiene muchos `PostLike` y `PostComment`.

#### Follow (seguimientos)
```
id | follower_id (FK) | following_id (FK) | status | createdAt
UNIQUE(follower_id, following_id)
```
`status` puede ser `'pending'` (solicitud pendiente) o `'accepted'` (seguimiento activo).

#### Shelf (estanterías)
```
id | user_id (FK) | name | orderIndex | createdAt | updatedAt
```

#### Book (libros, desde Google Books)
```
id | externalSource | externalId | title | authors (JSON)
publisher | publishedDate | language | description | pageCount
isbn10 | isbn13 | coverUrl | categories (JSON)
```

#### ShelfBook (libro en estantería)
```
id | shelf_id (FK) | book_id (FK) | status | orderIndex | addedAt
UNIQUE(shelf_id, book_id)
```
`status`: `'want_to_read'`, `'reading'`, `'read'`

#### Club (clubes de lectura)
```
id | owner_id (FK) | name | description | visibility
currentBook_id (FK) | currentBookSince | currentBookUntil
createdAt | updatedAt
```

#### ClubMember (membresía en club)
```
id | club_id (FK) | user_id (FK) | role | joinedAt
UNIQUE(club_id, user_id)
```
`role`: `'admin'` o `'member'`

#### Notification (notificaciones)
```
id | recipient_id (FK) | actor_id (FK) | type | post_id (FK nullable)
isRead | createdAt
```
`type`: `'follow'`, `'like'`, `'comment'`

#### ReadingProgress (progreso de lectura)
```
id | user_id (FK) | book_id (FK) | mode | currentPage | totalPages
percent | startedAt | updatedAt
UNIQUE(user_id, book_id)
```

### Diagrama de relaciones resumido

```
User ──< Post ──< PostLike
         │    └──< PostComment
         │
User ──< Follow >── User        (seguidor → seguido)
         │
User ──< Shelf ──< ShelfBook >── Book ──< BookReview >── User
         │
User ──< ClubMember >── Club ──< ClubChat ──< ClubChatMessage
                         │
                         └── Book (libro del mes)
         │
User ──< Notification
```

---

## 4. Autenticación y seguridad

### ¿Cómo funciona el login?

La autenticación usa el sistema **JSON Login** de Symfony, configurado en `config/packages/security.yaml`.

**Configuración clave:**
```yaml
# security.yaml
firewalls:
    main:
        json_login:
            check_path: api_login          # ruta POST /api/login
            username_path: email           # campo del JSON que es el "usuario"
            password_path: password        # campo del JSON que es la contraseña
            success_handler: App\Security\JsonLoginSuccessHandler
            failure_handler: App\Security\JsonLoginFailureHandler
        stateless: false                   # Usa sesión en cookie
```

**Proceso de login paso a paso:**

```
1. Cliente envía:
   POST /api/login
   { "email": "user@ejemplo.com", "password": "mipassword" }

2. Symfony Security intercepta la petición automáticamente
   (ni siquiera llega a ningún controlador nuestro)

3. Busca el usuario en BD por email (UserProvider con 'app_user_provider')

4. Verifica la contraseña con hash bcrypt:
   password_verify("mipassword", $hashGuardado)

5. Si correcto → llama a JsonLoginSuccessHandler
   → devuelve JSON con datos del usuario + crea sesión en cookie

6. Si incorrecto → llama a JsonLoginFailureHandler
   → devuelve JSON con error 401
```

### ¿Cómo se protegen los endpoints?

Cada método de controlador que requiere usuario autenticado tiene esta línea al principio:

```php
$this->denyAccessUnlessGranted('ROLE_USER');
```

Si el cliente no tiene sesión activa, Symfony devuelve automáticamente un error 401.

### ¿Cómo se registra un usuario?

El registro está en `AuthApiController::register()`:

```php
// POST /api/auth/register
// Body: { "email": "...", "password": "...", "displayName": "..." }

1. Valida email (filter_var FILTER_VALIDATE_EMAIL)
2. Valida contraseña (mínimo 6 caracteres)
3. Comprueba que el email no exista ya en BD
4. Hashea la contraseña con UserPasswordHasherInterface
5. Genera un displayName único:
   - Si el usuario envió uno, lo limpia (solo alfanumérico + guión bajo)
   - Si ya existe, añade sufijo numérico: "juan", "juan1", "juan2"...
6. Persiste el User en BD
7. Devuelve 201 Created con id y email
```

### Sesión vs JWT

Este backend usa **sesiones** (no JWT). Cuando el usuario hace login, Symfony crea una sesión y la guarda en una cookie `PHPSESSID`. En cada petición posterior, el navegador envía esa cookie y Symfony carga el usuario desde BD.

---

## 5. Feed de posts: proceso lógico completo

El feed es la pantalla principal: muestra los posts del propio usuario más los posts de los usuarios que sigue.

### Endpoint
```
GET /api/posts
```
Requiere autenticación (`ROLE_USER`).

### Código del controlador (`PostApiController.php`)

```php
#[Route('/posts', name: 'feed', methods: ['GET'])]
public function feed(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me    = $this->getUser();          // Usuario de la sesión activa
    $posts = $this->postRepo->findFeed($me, 40);  // Máx. 40 posts

    return $this->json(array_map(fn(Post $p) => $this->serialize($p, $me), $posts));
}
```

### La query del feed (`PostRepository.php`)

```php
public function findFeed(User $me, int $limit = 40): array
{
    return $this->createQueryBuilder('p')
        ->leftJoin(
            'App\Entity\Follow', 'f',
            'WITH',
            'f.follower = :me AND f.following = p.user AND f.status = :accepted'
        )
        ->andWhere('p.user = :me OR f.id IS NOT NULL')
        ->setParameter('me', $me)
        ->setParameter('accepted', 'accepted')
        ->orderBy('p.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

**Explicación de la query (DQL → SQL):**

```sql
SELECT p.*
FROM post p
LEFT JOIN follow f
    ON f.follower_id = :meId
    AND f.following_id = p.user_id
    AND f.status = 'accepted'
WHERE p.user_id = :meId        -- posts propios
   OR f.id IS NOT NULL         -- posts de usuarios que sigo (con follow aceptado)
ORDER BY p.created_at DESC
LIMIT 40
```

**¿Por qué LEFT JOIN y no INNER JOIN?**
- Con `LEFT JOIN`: si no hay ningún Follow que coincida, `f.id` será NULL.
- La condición `OR f.id IS NOT NULL` incluye solo los posts donde SÍ existe un follow activo.
- Además, con `p.user = :me` también se incluyen los posts propios aunque no haya Follow.
- Si usara `INNER JOIN`, se excluirían los posts propios (no hay Follow de uno mismo).

**¿Por qué solo `status = 'accepted'`?**
- Evita mostrar posts de usuarios a quienes solo has enviado una solicitud pendiente.
- Solo aparecen posts de usuarios que han aceptado tu seguimiento (o tú el suyo, si el usuario es público → status se crea directamente como 'accepted').

### Qué devuelve cada post

```json
{
    "id": 42,
    "imagePath": "post_6606ab12345.jpg",
    "description": "Mi lectura del día",
    "createdAt": "2026-04-06T10:30:00+00:00",
    "likes": 5,
    "liked": true,
    "commentCount": 3,
    "user": {
        "id": 7,
        "displayName": "maria_lee",
        "avatar": "avatar_abc123.jpg"
    }
}
```

El campo `liked` indica si el usuario autenticado ha dado like a ese post, lo que permite al frontend mostrar el icono de like en estado activo.

---

## 6. Sistema de seguimientos (Follows)

### Entidad Follow

```php
// src/Entity/Follow.php
class Follow
{
    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';

    private User $follower;    // quien sigue
    private User $following;   // a quien se sigue
    private string $status;    // 'pending' o 'accepted'
    private DateTimeImmutable $createdAt;
}
```

La tabla tiene una restricción `UNIQUE(follower_id, following_id)` para evitar duplicados.

### Proceso de seguir a un usuario

**Endpoint:** `POST /api/users/{id}/follow`

```
1. Comprobar que el usuario objetivo existe
2. Comprobar que no es uno mismo
3. Comprobar que no existe ya un Follow entre ambos (409 si ya existe)
4. Comprobar si el usuario objetivo tiene cuenta privada:
   - isPrivate = true  → status = 'pending'  (solicitud pendiente)
   - isPrivate = false → status = 'accepted' (sigue directamente)
5. Crear y persistir el Follow
6. Devolver JSON con el status y el nuevo nº de seguidores
```

```php
$status = $target->isPrivate() ? Follow::STATUS_PENDING : Follow::STATUS_ACCEPTED;
$this->em->persist(new Follow($me, $target, $status));
```

### Gestión de solicitudes (cuentas privadas)

Si el usuario objetivo es privado, el seguimiento queda pendiente hasta que lo acepte:

```
GET  /api/follow-requests              → ver mis solicitudes entrantes
POST /api/follow-requests/{id}/accept  → aceptar solicitud
DELETE /api/follow-requests/{id}       → rechazar solicitud
```

En `accept()`, simplemente se actualiza el status del Follow:
```php
$follow->accept();   // equivale a: $this->status = 'accepted';
$this->em->flush();
```

### Dejar de seguir

**Endpoint:** `DELETE /api/users/{id}/follow`

Busca el Follow entre el usuario actual y el objetivo y lo elimina de BD. Por el `CASCADE` configurado en Doctrine, si se elimina un usuario, todos sus follows se eliminan automáticamente.

### Eliminar un seguidor

**Endpoint:** `DELETE /api/users/{id}/followers`

A diferencia de dejar de seguir, aquí el usuario actual elimina a alguien que le sigue (es decir, busca el Follow donde `follower = otro` y `following = yo`).

---

## 7. Posts: crear, listar, likear y comentar

### Crear un post

**Endpoint:** `POST /api/posts`  
**Formato:** `multipart/form-data` (porque lleva un archivo de imagen)

```
Campos:
  image       (File, obligatorio)
  description (String, opcional)
```

**Proceso:**

```
1. Obtener el archivo del campo 'image'
2. Validar la extensión: solo jpg, jpeg, png, gif, webp
   (se usa guessExtension() que lee los magic bytes del archivo, no solo el nombre)
3. Generar nombre único: uniqid('post_', true) + '.' + ext
   Ejemplo: "post_6606ab12345678.0123456.jpg"
4. Mover el archivo a /public/uploads/posts/
5. Crear entidad Post con (usuario, nombreArchivo, descripción)
6. Persistir en BD
7. Devolver 201 con los datos del post
```

```php
$filename  = uniqid('post_', true) . '.' . $ext;
$uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
$file->move($uploadDir, $filename);

$post = new Post($me, $filename, $description);
$this->em->persist($post);
$this->em->flush();
```

**¿Por qué `uniqid`?** Para evitar colisiones de nombre cuando dos usuarios suben archivos con el mismo nombre. `uniqid('post_', true)` genera un string único basado en el tiempo con micros adicionales.

### Eliminar un post

```
1. Buscar el Post por id
2. Verificar que el post existe Y que pertenece al usuario actual
   (si el id no existe o es de otro usuario → 404, no 403, por seguridad)
3. Eliminar el archivo físico del disco con unlink()
4. Eliminar la entidad de BD (Doctrine borra en cascada los likes y comentarios)
5. Devolver 204 No Content
```

### Toggle Like

**Endpoint:** `POST /api/posts/{id}/like`

```
1. Buscar si ya existe un PostLike para (post, usuario_actual)
2. Si existe → eliminar (unlike) → devolver { liked: false, likes: N }
3. Si no existe → crear nuevo PostLike → devolver { liked: true, likes: N }
```

Es un **toggle**: la misma URL y método hacen like/unlike según el estado actual.

### Añadir comentario

**Endpoint:** `POST /api/posts/{id}/comments`  
**Body JSON:** `{ "content": "Gran libro!" }`

```
1. Validar que el contenido no esté vacío
2. Crear PostComment(post, usuario, content)
3. Persistir en BD
4. Devolver 201 con los datos del comentario
```

### Eliminar comentario

**Endpoint:** `DELETE /api/posts/{id}/comments/{commentId}`

Puede eliminarlo:
- El **autor del comentario**
- El **dueño del post** (puede moderar su propio post)

```php
$isOwner     = $comment->getUser()->getId() === $me->getId();
$isPostOwner = $post->getUser()->getId() === $me->getId();

if (!$isOwner && !$isPostOwner) {
    return $this->json(['error' => 'Sin permisos'], 403);
}
```

---

## 8. Estanterías y libros

Las estanterías son colecciones personales de libros. Cada usuario puede tener múltiples estanterías.

### Añadir un libro a una estantería

**Endpoint:** `POST /api/shelves/{id}/books`  
**Body JSON:** `{ "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }`

**Proceso (auto-import):**

```
1. Verificar que la estantería existe y pertenece al usuario actual
2. Buscar el libro en la BD local por (externalId, source='google_books')
3. Si NO existe en BD:
   → Llamar a Google Books API con el externalId
   → Parsear la respuesta y crear entidad Book
   → Persistir el Book en BD
4. Verificar que el libro no está ya en esa estantería (UNIQUE constraint)
5. Crear ShelfBook(estantería, libro, status, orderIndex)
6. Persistir en BD
7. Devolver los datos del libro añadido
```

**¿Por qué guardar en BD y no consultar Google siempre?**
- Evita dependencia constante de la API externa
- Mejora rendimiento (las queries locales son mucho más rápidas)
- Permite almacenar datos personalizados (estado, progreso, reseñas)

### Estados de un libro

| Estado | Descripción |
|--------|-------------|
| `want_to_read` | Quiero leer |
| `reading` | Leyendo actualmente |
| `read` | Ya leído |

---

## 9. Integración con Google Books API

### Búsqueda de libros

**Endpoint:** `GET /api/books/search`

**Parámetros disponibles:**
```
q         → búsqueda general
title     → buscar por título (usa intitle: de Google)
author    → buscar por autor (usa inauthor:)
isbn      → buscar por ISBN
subject   → buscar por categoría
publisher → buscar por editorial
page      → paginación (default: 1)
limit     → resultados por página (1-40, default: 20)
lang      → filtrar por idioma (ej: "es", "en")
orderBy   → relevance | newest
printType → books | magazines | all
filter    → free-ebooks | paid-ebooks | ebooks | full | partial
```

**Proceso interno de búsqueda:**

```
1. Leer y validar todos los parámetros de la URL
2. Construir la query de Google Books:
   - q=potter → "potter"
   - title=dune + author=herbert → "intitle:dune inauthor:herbert"
   - isbn=9788... → "isbn:9788..."
3. Llamar a Google Books API (siempre pidiendo máximo 40 resultados)
4. FILTRAR: descartar libros sin imagen de portada
5. ORDENAR por popularidad:
   a) Libros con ratings: ordenar por (ratingsCount × averageRating) DESC
   b) Libros sin ratings: ordenar por pageCount DESC (más páginas = más real)
   c) Los libros con ratings siempre van antes que los sin ratings
6. Cortar al límite solicitado
7. Devolver JSON con resultados normalizados
```

**¿Por qué este algoritmo de popularidad?**
Cuando buscas "Harry Potter", Google devuelve 40 resultados mezclados: ediciones populares, traducciones raras, resúmenes, etc. Ordenar por `ratingsCount × averageRating` asegura que las ediciones más populares y mejor valoradas aparezcan primero.

**Ejemplo de respuesta:**
```json
{
    "page": 1,
    "limit": 20,
    "totalItems": 834,
    "results": [
        {
            "externalId": "zyTCAlFPjgYC",
            "title": "Harry Potter y la piedra filosofal",
            "authors": ["J.K. Rowling"],
            "thumbnail": "https://books.google.com/...",
            "averageRating": 4.5,
            "ratingsCount": 12500,
            "pageCount": 309,
            "isbn13": "9788478884452"
        }
    ]
}
```

---

## 10. Clubes de lectura

Los clubes son espacios grupales donde los usuarios pueden compartir lecturas y chatear.

### Crear un club

**Endpoint:** `POST /api/clubs`  
**Body:** `{ "name": "...", "description": "...", "visibility": "public" }`

```
1. Validar nombre (obligatorio) y visibility (public|private)
2. Crear entidad Club con el usuario actual como owner
3. Crear automáticamente un ClubMember para el creador con role='admin'
4. Persistir ambos en la misma transacción
5. Devolver 201
```

```php
$club = new Club();
$club->setOwner($this->getUser());
$em->persist($club);

$member = new ClubMember();
$member->setRole('admin');   // El creador es admin automáticamente
$em->persist($member);

$em->flush();   // Una sola transacción → ambas inserciones son atómicas
```

### Unirse a un club

**Endpoint:** `POST /api/clubs/{id}/join`

```
┌─────────────────────┐
│  ¿Es club público?  │
├─────────────────────┤
│       SÍ            │ → Crear ClubMember con role='member' directamente
│       NO            │ → Club privado:
│                     │     ¿Ya existe una solicitud?
│                     │       SÍ → devolver 'already_requested'
│                     │       NO → Crear ClubJoinRequest con status='pending'
└─────────────────────┘
```

### Gestión de solicitudes (clubs privados)

El admin del club puede:
- `GET /api/clubs/{id}/requests` → ver solicitudes pendientes
- `POST /api/clubs/{id}/requests/{requestId}/approve` → aprobar
- `POST /api/clubs/{id}/requests/{requestId}/reject` → rechazar

Al **aprobar**:
```
1. Cambiar status de ClubJoinRequest a 'approved'
2. Guardar quién lo aprobó y cuándo (resolvedBy, resolvedAt)
3. Crear el ClubMember automáticamente
4. Persistir todo en una transacción
```

### Restricción: admin no puede irse si hay otros miembros

```php
if ($membership->getRole() === 'admin' && $clubMemberRepository->countByClub($club) > 1) {
    return $this->json([
        'error' => 'El administrador no puede abandonar el club si hay otros miembros.'
    ], 400);
}
```

Esto evita dejar un club sin administrador.

### Libro del mes

**Endpoint:** `PUT /api/clubs/{id}/current-book`  
**Body:** `{ "externalId": "zyTCAlFPjgYC", "dateFrom": "2026-04-01", "dateUntil": "2026-04-30" }`

```
1. Solo admins pueden establecerlo
2. Buscar el libro en BD local por externalId
3. Si no existe → importarlo automáticamente de Google Books API
4. Asignar al club con fechas de inicio/fin
5. Persistir
```

La lógica de importación es la misma que para estanterías: primero BD local, luego Google Books.

### Optimización de queries en la lista de clubes

Al listar todos los clubs, se necesita saber el número de miembros de cada uno y si el usuario actual es miembro. En lugar de hacer una query por cada club (problema N+1), se usa una **single query** para obtener todos los datos de golpe:

```php
// Una sola query para contar miembros de TODOS los clubs
$memberCounts = $clubMemberRepository->getMemberCountsForClubs($clubs);
// → devuelve: [ clubId => count, clubId => count, ... ]

// Una sola query para obtener membresías del usuario actual
$membershipsMap = $clubMemberRepository->getMembershipsMapForUser($user, $clubs);
// → devuelve: [ clubId => ClubMember, ... ]
```

---

## 11. Chat de clubes

Cada club tiene hilos de chat (como un foro). Solo los miembros pueden ver y escribir.

### Estructura

- Un **Club** tiene muchos **ClubChat** (hilos)
- Cada **ClubChat** tiene muchos **ClubChatMessage**
- Los hilos pueden estar abiertos (`isOpen = true`) o cerrados (no se pueden añadir mensajes nuevos)

### Índice de base de datos

```php
// ClubChatMessage.php
#[ORM\Index(columns: ['chat_id', 'created_at'])]
```

Este índice compuesto acelera la query más común: "dame los mensajes del hilo X ordenados por fecha". Sin él, la BD haría un full scan de toda la tabla.

### Paginación de mensajes

Los mensajes se cargan en páginas para no sobrecargar:
```
GET /api/clubs/{clubId}/chats/{chatId}/messages?page=1&limit=50
```
Máximo 100 mensajes por petición.

---

## 12. Reseñas de libros

**Endpoints:**
```
GET    /api/books/{externalId}/reviews    → ver reseñas de un libro
POST   /api/books/{externalId}/reviews    → crear/actualizar mi reseña
DELETE /api/books/{externalId}/reviews    → eliminar mi reseña
```

### Crear o actualizar reseña (upsert)

```
1. Buscar el libro en BD por externalId
2. Si no existe en BD → importarlo de Google Books
3. Buscar si el usuario ya tiene una reseña para ese libro:
   - Si existe → actualizar (rating + content)
   - Si no existe → crear nueva
4. Validar rating entre 1 y 5
5. Persistir
```

Una sola reseña por usuario/libro (restricción `UNIQUE(user_id, book_id)`).

### Estadísticas de un libro

```json
{
    "averageRating": 4.2,
    "totalReviews": 15,
    "myReview": {
        "rating": 5,
        "content": "Imprescindible"
    },
    "reviews": [...]
}
```

---

## 13. Progreso de lectura

Permite al usuario rastrear cuánto lleva leído de un libro.

### Dos modos de seguimiento

| Modo | Cómo funciona |
|------|---------------|
| `pages` | Guarda la página actual y el total de páginas |
| `percent` | Guarda directamente un porcentaje (0-100) |

### Cálculo automático del porcentaje

```php
public function getComputedPercent(): ?int
{
    if ($this->mode === 'percent') {
        return $this->percent;
    }

    // Modo pages: calcular % dinámicamente
    $total = $this->totalPages ?? null;
    if (!$total || !$this->currentPage) return null;
    return (int) round(($this->currentPage / $total) * 100);
}
```

Si el libro no tiene `pageCount` en Google Books, el usuario puede especificar `totalPages` manualmente.

---

## 14. Notificaciones

### ¿Cuándo se generan?

| Evento | Notificación |
|--------|-------------|
| Alguien te sigue | `type = 'follow'` |
| Alguien da like a tu post | `type = 'like'` |
| Alguien comenta en tu post | `type = 'comment'` |

### Obtener notificaciones

**Endpoint:** `GET /api/notifications`

```json
{
    "unread": 3,
    "items": [
        {
            "id": 101,
            "type": "like",
            "isRead": false,
            "createdAt": "2026-04-06T09:15:00+00:00",
            "actor": {
                "id": 12,
                "displayName": "carlos_lee",
                "avatar": "avatar_xyz.jpg"
            },
            "post": {
                "id": 42,
                "imagePath": "post_abc.jpg"
            }
        }
    ]
}
```

Se devuelven las últimas **30 notificaciones** del usuario.

### Marcar como leídas

**Endpoint:** `POST /api/notifications/read-all`

Ejecuta un `UPDATE` masivo en BD (no una por una) para mayor eficiencia:
```php
$this->repo->markAllRead($me);  // UPDATE notification SET is_read=1 WHERE recipient_id=:id
```

---

## 15. Perfil de usuario y privacidad

### Controles de privacidad

Cada usuario puede configurar tres niveles de privacidad:

| Campo | Qué controla |
|-------|-------------|
| `isPrivate` | Si otros necesitan solicitud para seguirte |
| `shelvesPublic` | Si otros pueden ver tus estanterías |
| `clubsPublic` | Si otros pueden ver a qué clubes perteneces |

**Endpoint:** `PUT /api/profile/privacy`  
**Body:** `{ "isPrivate": true, "shelvesPublic": false, "clubsPublic": true }`

### Ver perfil público

**Endpoint:** `GET /api/users/{id}`

```
1. Buscar usuario por ID
2. Determinar si el visitante puede ver los datos:
   - Datos básicos (displayName, avatar, bio) → siempre visibles
   - Estanterías → solo si shelvesPublic=true
   - Clubs → solo si clubsPublic=true
3. Incluir en la respuesta el estado de la relación:
   - isFollowing: ¿el visitante le sigue?
   - followStatus: 'accepted', 'pending', o null
```

### Subir avatar

**Endpoint:** `POST /api/profile/avatar`  
**Formato:** `multipart/form-data` con campo `avatar`

Mismo proceso que las imágenes de posts: validar extensión, generar nombre único, mover a `/public/uploads/avatars/`.

---

## 16. Referencia completa de endpoints

### Autenticación
| Método | URL | Descripción |
|--------|-----|-------------|
| POST | `/api/login` | Login (email + password) |
| POST | `/api/auth/register` | Registrar nuevo usuario |
| GET | `/api/auth/me` | Usuario autenticado actual |
| POST | `/api/auth/logout` | Cerrar sesión |

### Posts
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/posts` | Feed personalizado |
| GET | `/api/users/{id}/posts` | Posts de un usuario |
| POST | `/api/posts` | Crear post (multipart) |
| DELETE | `/api/posts/{id}` | Eliminar post propio |
| POST | `/api/posts/{id}/like` | Toggle like |
| GET | `/api/posts/{id}/comments` | Ver comentarios |
| POST | `/api/posts/{id}/comments` | Añadir comentario |
| DELETE | `/api/posts/{id}/comments/{cId}` | Eliminar comentario |

### Seguimientos
| Método | URL | Descripción |
|--------|-----|-------------|
| POST | `/api/users/{id}/follow` | Seguir usuario |
| DELETE | `/api/users/{id}/follow` | Dejar de seguir |
| GET | `/api/users/{id}/followers` | Ver seguidores |
| GET | `/api/users/{id}/following` | Ver seguidos |
| DELETE | `/api/users/{id}/followers` | Eliminar un seguidor |
| GET | `/api/follow-requests` | Solicitudes entrantes |
| POST | `/api/follow-requests/{id}/accept` | Aceptar solicitud |
| DELETE | `/api/follow-requests/{id}` | Rechazar solicitud |

### Libros
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/books/search` | Buscar en Google Books |
| GET | `/api/books/{externalId}` | Detalle de un libro |
| GET | `/api/books/{externalId}/reviews` | Ver reseñas |
| POST | `/api/books/{externalId}/reviews` | Crear/actualizar reseña |
| DELETE | `/api/books/{externalId}/reviews` | Eliminar reseña |

### Estanterías
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/shelves` | Mis estanterías |
| POST | `/api/shelves` | Crear estantería |
| PATCH | `/api/shelves/{id}` | Renombrar |
| DELETE | `/api/shelves/{id}` | Eliminar |
| GET | `/api/shelves/full` | Estanterías con libros |
| GET | `/api/shelves/{id}/books` | Libros de una estantería |
| POST | `/api/shelves/{id}/books` | Añadir libro |
| PATCH | `/api/shelves/{id}/books/{bId}` | Cambiar estado |
| DELETE | `/api/shelves/{id}/books/{bId}` | Quitar libro |

### Clubes
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/clubs` | Listar clubs públicos |
| POST | `/api/clubs` | Crear club |
| GET | `/api/clubs/{id}` | Detalle del club |
| PATCH | `/api/clubs/{id}` | Editar club (admin) |
| DELETE | `/api/clubs/{id}` | Eliminar club (admin) |
| POST | `/api/clubs/{id}/join` | Unirse al club |
| DELETE | `/api/clubs/{id}/leave` | Abandonar club |
| GET | `/api/clubs/{id}/members` | Ver miembros |
| DELETE | `/api/clubs/{id}/members/{mId}` | Expulsar miembro (admin) |
| GET | `/api/clubs/{id}/requests` | Solicitudes pendientes (admin) |
| POST | `/api/clubs/{id}/requests/{rId}/approve` | Aprobar solicitud (admin) |
| POST | `/api/clubs/{id}/requests/{rId}/reject` | Rechazar solicitud (admin) |
| PUT | `/api/clubs/{id}/current-book` | Establecer libro del mes (admin) |
| DELETE | `/api/clubs/{id}/current-book` | Quitar libro del mes (admin) |

### Chat de clubes
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/clubs/{cId}/chats` | Listar hilos |
| POST | `/api/clubs/{cId}/chats` | Crear hilo (admin) |
| GET | `/api/clubs/{cId}/chats/{id}` | Detalle del hilo |
| PATCH | `/api/clubs/{cId}/chats/{id}` | Editar hilo |
| DELETE | `/api/clubs/{cId}/chats/{id}` | Eliminar hilo (admin) |
| GET | `/api/clubs/{cId}/chats/{id}/messages` | Ver mensajes |
| POST | `/api/clubs/{cId}/chats/{id}/messages` | Enviar mensaje |
| DELETE | `/api/clubs/{cId}/chats/{id}/messages/{mId}` | Eliminar mensaje |

### Perfil
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/profile` | Mi perfil completo |
| PUT | `/api/profile` | Actualizar perfil |
| POST | `/api/profile/avatar` | Subir foto de perfil |
| PUT | `/api/profile/password` | Cambiar contraseña |
| PUT | `/api/profile/privacy` | Configurar privacidad |
| GET | `/api/users/{id}` | Ver perfil de otro usuario |
| GET | `/api/users/search?q=...` | Buscar usuarios |

### Notificaciones
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/notifications` | Ver notificaciones |
| POST | `/api/notifications/read-all` | Marcar todas como leídas |

### Progreso de lectura
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/reading-progress` | Mi progreso actual |
| POST | `/api/reading-progress` | Iniciar seguimiento |
| PATCH | `/api/reading-progress/{id}` | Actualizar progreso |
| DELETE | `/api/reading-progress/{id}` | Dejar de seguir |

### Administración (requiere ROLE_ADMIN)
| Método | URL | Descripción |
|--------|-----|-------------|
| GET | `/api/admin/stats` | Totales globales (usuarios, clubs, posts) |
| GET | `/api/admin/users` | Lista completa de usuarios |
| PATCH | `/api/admin/users/{id}/role` | Dar/quitar rol de admin |
| PATCH | `/api/admin/users/{id}/ban` | Banear/desbanear usuario |
| DELETE | `/api/admin/users/{id}` | Eliminar cuenta de usuario |
| GET | `/api/admin/clubs` | Lista completa de clubs |
| DELETE | `/api/admin/clubs/{id}` | Eliminar cualquier club |
| GET | `/api/admin/posts` | Últimas 100 publicaciones |
| DELETE | `/api/admin/posts/{id}` | Eliminar cualquier publicación |

---

## Resumen para la defensa

**¿Qué es este proyecto?**
Una red social para lectores. Los usuarios pueden publicar imágenes (como Instagram), seguirse entre sí, gestionar estanterías de libros, unirse a clubes de lectura con chat, y llevar un registro de su progreso lector.

**¿Qué tecnologías usa el backend?**
Symfony 7.4 (PHP), Doctrine ORM, MySQL, y la Google Books API.

**¿Cómo funciona el feed?**
Una query SQL con LEFT JOIN a la tabla `follow` que recupera los posts propios más los posts de usuarios a los que sigues con `status='accepted'`, ordenados por fecha descendente.

**¿Cómo funciona la autenticación?**
JSON Login de Symfony: el cliente envía email + contraseña, Symfony verifica el hash bcrypt y crea una sesión en cookie. Cada endpoint protegido comprueba `ROLE_USER`.

**¿Cómo se manejan los libros?**
Se buscan en Google Books API. Cuando un usuario añade un libro a su estantería, si no existe en BD local se importa automáticamente y se guarda para futuras peticiones.

**¿Cómo funciona la privacidad en follows?**
Si el usuario objetivo tiene `isPrivate=true`, el Follow se crea con `status='pending'`. Hasta que el destinatario acepte, el seguidor no aparece en el feed ni en la lista de seguidores activos.

**¿Cómo se protegen los posts de perfiles privados?**
El endpoint `GET /api/users/{id}/posts` comprueba si el perfil es privado. Si lo es, solo el propio usuario o sus seguidores con `status='accepted'` pueden ver los posts; el resto recibe HTTP 403. El frontend refleja esto mostrando un icono de candado con el mensaje "Perfil privado".
