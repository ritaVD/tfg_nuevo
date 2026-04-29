# Documentación del Proyecto — TFGdaw

> Red social de lectura con gestión de estanterías, clubes y chat.  
> Para la defensa del TFG — guía completa de todo lo que necesitas saber.

---

## Índice

1. [Resumen del proyecto](#1-resumen-del-proyecto)
2. [Stack tecnológico](#2-stack-tecnológico)
3. [Arquitectura general](#3-arquitectura-general)
4. [Estructura de carpetas](#4-estructura-de-carpetas)
5. [Base de datos — Entidades](#5-base-de-datos--entidades)
6. [Backend — Autenticación y seguridad](#6-backend--autenticación-y-seguridad)
7. [Backend — Endpoints de la API](#7-backend--endpoints-de-la-api)
8. [Frontend — Rutas y páginas](#8-frontend--rutas-y-páginas)
9. [Frontend — Componentes y capa API](#9-frontend--componentes-y-capa-api)
10. [Funcionalidades principales explicadas](#10-funcionalidades-principales-explicadas)
11. [Despliegue y Docker](#11-despliegue-y-docker)
12. [Decisiones de diseño — Por qué elegí esto](#12-decisiones-de-diseño--por-qué-elegí-esto)
13. [Preguntas frecuentes en defensa](#13-preguntas-frecuentes-en-defensa)

---

## 1. Resumen del proyecto

**TFGdaw** es una red social orientada a lectores. Permite:

- Buscar libros reales a través de **Google Books API**
- Organizar libros en **estanterías personales** (quiero leer, leyendo, leído)
- Seguir a otros usuarios y ver su actividad en un **feed**
- Crear y unirse a **clubes de lectura** con chat interno
- Subir **publicaciones con imágenes** (como un Instagram de lectores)
- Recibir **notificaciones** en tiempo real de likes, comentarios y solicitudes
- Escribir **reseñas** de libros y llevar un **progreso de lectura**
- Configurar la **privacidad** del perfil

---

## 2. Stack tecnológico

### Backend
| Tecnología | Versión | Para qué se usa |
|---|---|---|
| **PHP** | 8.2 | Lenguaje principal del servidor |
| **Symfony** | 7.4 | Framework MVC backend |
| **Doctrine ORM** | 3.x | Mapeo de objetos PHP a tablas SQL |
| **MySQL** | 8.x | Base de datos relacional |
| **Symfony Security** | — | Autenticación por sesión (cookies) |
| **Symfony HttpClient** | — | Llamadas a Google Books API |

### Frontend
| Tecnología | Versión | Para qué se usa |
|---|---|---|
| **React** | 18 | Librería de interfaz de usuario |
| **TypeScript** | 5.x | JavaScript tipado |
| **Vite** | 5.x | Bundler y servidor de desarrollo |
| **React Router** | 6.x | Navegación SPA (sin recargar página) |
| **Lucide React** | — | Iconos SVG |
| **CSS puro** | — | Estilos (sin Tailwind ni Bootstrap) |

### Infraestructura
| Tecnología | Para qué se usa |
|---|---|
| **Docker** | Contenedores para backend en producción |
| **Railway** | Plataforma de despliegue en la nube |
| **Nginx** (dentro del contenedor) | Servidor web para la build del frontend |
| **Supervisor** | Gestor de procesos dentro del contenedor (PHP-FPM + Nginx) |

---

## 3. Arquitectura general

### Cómo se comunican frontend y backend

```
Navegador (React SPA)
      │
      │  HTTP fetch() con credentials:'include'
      │  (las cookies de sesión van automáticamente)
      ▼
Backend Symfony  (dominio Railway)
      │
      ├── Firewall de seguridad (comprueba si hay sesión activa)
      ├── Router (decide qué controlador llama)
      ├── Controlador (valida datos, ejecuta lógica)
      ├── Doctrine ORM (convierte objetos PHP ↔ SQL)
      └── MySQL (almacena y devuelve datos)
      │
      ▼
JSON response  →  React actualiza el estado  →  Re-render de la UI
```

### SPA (Single Page Application)
El frontend es una **SPA**: solo se carga el HTML una vez. Cuando navegas entre páginas (clubes, perfil, libros) no hay recarga del navegador — React Router cambia lo que se muestra cambiando el estado interno. La URL sí cambia, pero sin petición al servidor HTML.

### Sesión basada en cookies
La autenticación **no usa JWT**. Usa sesiones de PHP guardadas en el servidor. Al hacer login, Symfony crea una sesión y devuelve una cookie `PHPSESSID`. Todas las peticiones siguientes incluyen esa cookie automáticamente (`credentials: 'include'`), y Symfony sabe quién es el usuario.

---

## 4. Estructura de carpetas

Es importante saber dónde vive cada tipo de código para la defensa.

### Backend (`backend/`)
```
backend/
├── src/
│   ├── Controller/Api/        ← Un fichero por dominio (Auth, Club, Post…)
│   │   ├── AuthApiController.php
│   │   ├── ClubApiController.php
│   │   ├── PostApiController.php
│   │   └── … (12 controladores en total)
│   ├── Controller/
│   │   └── SpaController.php  ← Sirve el index.html de React para todas las rutas no-API
│   ├── Entity/                ← Las 16 clases que mapean a tablas de la BD
│   │   ├── User.php, Book.php, Post.php, Club.php…
│   ├── Repository/            ← Queries a la BD (una por entidad)
│   │   ├── PostRepository.php, FollowRepository.php…
│   └── Security/              ← Login personalizado con respuesta JSON
│       ├── JsonLoginSuccessHandler.php
│       └── JsonLoginFailureHandler.php
├── migrations/                ← Historial de cambios de esquema BD
├── config/
│   ├── packages/security.yaml ← Configuración del firewall y login
│   └── routes.yaml
├── docker/
│   └── entrypoint.sh          ← Script de arranque del contenedor
└── Dockerfile
```

### Frontend (`frontend/`)
```
frontend/src/
├── api/                       ← Una función por dominio: encapsulan los fetch()
│   ├── client.ts              ← apiFetch() y apiFormData(): utilidades base para HTTP
│   ├── auth.ts, books.ts, clubs.ts, posts.ts, shelves.ts…
├── context/
│   └── AuthContext.tsx        ← Estado global del usuario logueado
├── components/                ← Piezas reutilizables
│   ├── Navbar.tsx, PostCard.tsx, Toast.tsx, ConfirmDialog.tsx, Spinner.tsx
├── pages/                     ← Una página por ruta de la app
│   ├── HomePage.tsx, BooksPage.tsx, ClubDetailPage.tsx…
├── styles/                    ← CSS dividido en 9 ficheros temáticos
│   ├── tokens.css             ← Variables CSS (colores, bordes, sombras)
│   ├── layout.css, home.css, clubs.css, books.css, profile.css…
├── App.tsx                    ← Define las rutas con React Router
└── main.tsx                   ← Punto de entrada: monta React en el DOM
```

**Patrón clave del backend:** cada `Controller` recibe la petición, llama al `Repository` para acceder a la BD, y devuelve JSON. Los `Entity` son simples clases PHP con anotaciones que Doctrine convierte en SQL.

**Patrón clave del frontend:** cada página llama a funciones de `api/`, guarda el resultado en `useState`, y renderiza. La comunicación entre páginas y componentes distantes pasa por `AuthContext` (React Context).

---

## 5. Base de datos — Entidades

Hay **16 entidades** (tablas). Aquí están explicadas con sus relaciones:

### Usuario (`user`)
El centro de todo. Cada usuario tiene:
- `email` — único, para el login
- `displayName` — único, nombre visible
- `password` — hash bcrypt (nunca se guarda en texto plano)
- `bio` — descripción del perfil
- `avatar` — nombre del archivo subido
- `isPrivate` — si el perfil es privado, los seguidores deben ser aceptados
- `shelvesPublic` / `clubsPublic` — controlan qué se puede ver en el perfil público

### Libro (`book`)
Los libros **no se crean manualmente**, vienen de Google Books y se guardan en local:
- `externalId` — el ID de Google Books (ej. `"abc123"`)
- `externalSource` — siempre `"google_books"`
- `title`, `authors` (array JSON), `isbn10`, `isbn13`
- `coverUrl`, `description`, `pageCount`, `categories`

Cuando un usuario busca un libro y hace click en él, si no existe en la BD local se importa automáticamente.

### Post (`post`)
Publicaciones tipo Instagram:
- `imagePath` — nombre del archivo en `/uploads/posts/`
- `description` — texto opcional
- `user` → autor (ManyToOne)
- Tiene `PostLike` y `PostComment` asociados (OneToMany, CASCADE)

### Follow (`follow`)
Sistema de seguimiento entre usuarios:
- `follower` → quien sigue
- `following` → a quien se sigue
- `status` → `'pending'` (cuenta privada, esperando aceptación) o `'accepted'`
- UNIQUE(follower_id, following_id) — no puedes seguir dos veces a alguien

### Shelf / ShelfBook (`shelf`, `shelf_book`)
- Un usuario puede tener varias **estanterías** (Shelf)
- Cada estantería contiene **ShelfBook**: la relación libro+estantería
- ShelfBook tiene `status`: `'want_to_read'`, `'reading'`, `'read'`
- UNIQUE(shelf_id, book_id) — un libro no puede estar dos veces en la misma estantería

### Club (`club`)
Clubes de lectura:
- `visibility` → `'public'` o `'private'`
- `owner` → usuario creador
- `currentBook` → libro del mes (nullable, ManyToOne → Book). Si se borra el libro de la BD, se pone a NULL automáticamente (`onDelete: 'SET NULL'`)
- `currentBookSince` / `currentBookUntil` → fechas de inicio y fin del libro del mes. El admin elige estas fechas al asignarlo
- Tiene miembros (`ClubMember`), solicitudes (`ClubJoinRequest`) y chats (`ClubChat`)

### ClubMember (`club_member`)
- Relaciona usuario + club
- `role` → `'admin'` o `'member'`
- UNIQUE(club_id, user_id) — no puedes estar dos veces en el mismo club

### ClubChat / ClubChatMessage
- Un club puede tener varios **hilos de chat** (ClubChat) con `title`
- Cada hilo tiene **mensajes** (ClubChatMessage) con `content` y timestamp
- Los mensajes tienen índice compuesto `(chat_id, created_at)` para paginar eficientemente

### Notification (`notification`)
Tipos: `follow`, `follow_request`, `follow_accepted`, `like`, `comment`, `club_request`, `club_approved`, `club_rejected`
- `recipient` → quien recibe la notificación
- `actor` → quien la genera
- `isRead` → para mostrar el contador de sin leer
- `refId` → ID auxiliar (ej. ID del Follow para poder aceptar/rechazar desde la notif)

### ReadingProgress (`reading_progress`)
- Seguimiento de lectura de un libro
- `mode` → `'pages'` o `'percent'`
- `currentPage`, `totalPages`, `percent`
- UNIQUE(user_id, book_id) — un usuario solo tiene un progreso por libro

### BookReview (`book_review`)
- `rating` → 1 a 5 estrellas
- `content` → texto opcional
- UNIQUE(user_id, book_id) — un usuario solo puede reseñar un libro una vez (pero puede editarla)

---

## 6. Backend — Autenticación y seguridad

### Login
```
POST /api/login
Body: { "email": "...", "password": "..." }
```
Symfony comprueba las credenciales con `UserPasswordHasher`. Si son correctas, crea una sesión PHP y devuelve una cookie `PHPSESSID`. A partir de ahí, todas las peticiones identifican al usuario por esa cookie.

### Registro
```
POST /api/auth/register
Body: { "email": "...", "password": "...", "displayName": "..." }
```
Se valida que el email y el displayName sean únicos. La contraseña se hashea con bcrypt antes de guardarla.

### Rutas protegidas
Los endpoints que requieren estar logueado tienen:
```php
$this->denyAccessUnlessGranted('ROLE_USER');
```
Si la sesión no existe o ha expirado, Symfony devuelve `401 Unauthorized`.

### Roles
- `ROLE_USER` — todos los usuarios registrados
- `ROLE_ADMIN` — administradores (pueden borrar cualquier post o club)

### CSRF
No se usa protección CSRF explícita porque la API es stateless desde el punto de vista del cliente: las peticiones vienen de `fetch()` con `credentials: 'include'`, y Symfony gestiona la sesión internamente.

---

## 7. Backend — Endpoints de la API

Todos los endpoints tienen el prefijo `/api`. El backend devuelve siempre JSON.

### Autenticación
| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/login` | Login con email y password |
| `POST` | `/api/auth/register` | Crear cuenta nueva |
| `GET` | `/api/auth/me` | Obtener usuario actual |
| `POST` | `/api/auth/logout` | Cerrar sesión |

### Perfil de usuario
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/profile` | Mi perfil completo |
| `PUT` | `/api/profile` | Editar nombre y bio |
| `POST` | `/api/profile/avatar` | Subir imagen de avatar |
| `PUT` | `/api/profile/password` | Cambiar contraseña |
| `PUT` | `/api/profile/privacy` | Configurar privacidad |
| `GET` | `/api/users/search?q=...` | Buscar usuarios |
| `GET` | `/api/users/{id}` | Perfil público de otro usuario |

### Follows (seguimientos)
| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/users/{id}/follow` | Seguir a alguien |
| `DELETE` | `/api/users/{id}/follow` | Dejar de seguir |
| `GET` | `/api/users/{id}/followers` | Lista de seguidores |
| `GET` | `/api/users/{id}/following` | Lista de seguidos |
| `DELETE` | `/api/users/{id}/followers` | Eliminar un seguidor |

### Posts
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/posts` | Feed personalizado |
| `GET` | `/api/users/{id}/posts` | Posts de un usuario |
| `POST` | `/api/posts` | Crear post (con imagen) |
| `DELETE` | `/api/posts/{id}` | Borrar post |
| `POST` | `/api/posts/{id}/like` | Dar/quitar like |
| `GET` | `/api/posts/{id}/comments` | Ver comentarios |
| `POST` | `/api/posts/{id}/comments` | Comentar |
| `DELETE` | `/api/posts/{id}/comments/{cid}` | Borrar comentario |

### Libros (Google Books)
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/books/search` | Buscar en Google Books |
| `GET` | `/api/books/{externalId}` | Detalle de un libro |
| `GET` | `/api/books/{externalId}/reviews` | Reseñas y media |
| `POST` | `/api/books/{externalId}/reviews` | Crear/editar reseña |
| `DELETE` | `/api/books/{externalId}/reviews` | Borrar reseña |

### Estanterías
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/shelves` | Mis estanterías |
| `POST` | `/api/shelves` | Crear estantería |
| `PATCH` | `/api/shelves/{id}` | Renombrar estantería |
| `DELETE` | `/api/shelves/{id}` | Borrar estantería |
| `POST` | `/api/shelves/{id}/books` | Añadir libro |
| `PATCH` | `/api/shelves/{id}/books/{bookId}` | Cambiar estado del libro |
| `DELETE` | `/api/shelves/{id}/books/{bookId}` | Quitar libro |
| `POST` | `/api/shelves/{id}/books/{bookId}/move` | Mover a otra estantería |

### Progreso de lectura
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/reading-progress` | Mi progreso |
| `POST` | `/api/reading-progress` | Empezar a seguir un libro |
| `PATCH` | `/api/reading-progress/{id}` | Actualizar progreso |
| `DELETE` | `/api/reading-progress/{id}` | Dejar de seguir |

### Clubes
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/clubs` | Listar clubes |
| `POST` | `/api/clubs` | Crear club |
| `GET` | `/api/clubs/{id}` | Detalle del club |
| `PATCH` | `/api/clubs/{id}` | Editar club (admin) |
| `DELETE` | `/api/clubs/{id}` | Borrar club (admin) |
| `POST` | `/api/clubs/{id}/join` | Unirse al club |
| `DELETE` | `/api/clubs/{id}/leave` | Abandonar club |
| `GET` | `/api/clubs/{id}/members` | Listar miembros |
| `DELETE` | `/api/clubs/{id}/members/{userId}` | Expulsar miembro |
| `GET` | `/api/clubs/{id}/requests` | Ver solicitudes (admin) |
| `POST` | `/api/clubs/{id}/requests/{rid}/approve` | Aprobar solicitud |
| `POST` | `/api/clubs/{id}/requests/{rid}/reject` | Rechazar solicitud |
| `PUT` | `/api/clubs/{id}/current-book` | Poner libro del mes |
| `DELETE` | `/api/clubs/{id}/current-book` | Quitar libro del mes |

### Chat de clubes
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/clubs/{cid}/chats` | Listar hilos |
| `POST` | `/api/clubs/{cid}/chats` | Crear hilo (admin) |
| `DELETE` | `/api/clubs/{cid}/chats/{id}` | Borrar hilo |
| `GET` | `/api/clubs/{cid}/chats/{id}/messages` | Ver mensajes (paginado) |
| `POST` | `/api/clubs/{cid}/chats/{id}/messages` | Enviar mensaje |
| `DELETE` | `/api/clubs/{cid}/chats/{id}/messages/{mid}` | Borrar mensaje |

### Notificaciones
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/notifications` | Últimas 30 notificaciones |
| `GET` | `/api/notifications/history` | Historial completo |
| `POST` | `/api/notifications/read-all` | Marcar todas como leídas |
| `POST` | `/api/notifications/follow-requests/{id}/accept` | Aceptar solicitud de seguimiento |
| `DELETE` | `/api/notifications/follow-requests/{id}` | Rechazar solicitud |

---

## 8. Frontend — Rutas y páginas

El frontend es una SPA con React Router. Las rutas son:

| Ruta | Página | Pública/Privada |
|---|---|---|
| `/` | HomePage | Pública |
| `/login` | LoginPage | Pública |
| `/register` | RegisterPage | Pública |
| `/books` | BooksPage | Pública |
| `/books/:externalId` | BookDetailPage | Pública |
| `/clubs` | ClubsPage | Pública |
| `/clubs/:id` | ClubDetailPage | Pública |
| `/users` | UsersPage | Pública |
| `/users/:id` | PublicProfilePage | Pública |
| `/shelves` | ShelvesPage | **Privada** (login requerido) |
| `/profile` | ProfilePage | **Privada** (login requerido) |
| `/admin` | AdminPage | **Privada** (solo ROLE_ADMIN) |

Las rutas privadas usan el componente `PrivateRoute`, que comprueba si hay usuario en el contexto `AuthContext`. Si no hay sesión, redirige a `/login`.

### Descripción de cada página

**HomePage** — Página de inicio. Si el usuario está logueado muestra el feed de posts (propios y de usuarios a los que sigue). Si no está logueado muestra una landing con descripción de la app.

**LoginPage / RegisterPage** — Formularios de autenticación. Al hacer login se llama a `POST /api/login` y si tiene éxito se guarda el usuario en `AuthContext`.

**BooksPage** — Buscador de libros. Tiene un formulario con tres modos de búsqueda: **texto libre**, **por título** y **por autor**. La búsqueda se lanza al hacer submit (click o Enter). Muestra sugerencias predefinidas de libros populares para arrancar. Los resultados se paginan de 12 en 12 (`?page=1&limit=12`). Desde los resultados se puede añadir un libro directamente a una estantería sin entrar al detalle.

**BookDetailPage** — Detalle de un libro. Muestra: portada, descripción, autores, reseñas de usuarios, media de puntuación. Si estás logueado puedes añadir el libro a una estantería, escribir reseña y actualizar progreso de lectura.

**ClubsPage** — Lista todos los clubs públicos. Puedes buscar por nombre, unirte a clubs, crear uno nuevo. Al crear un club, eres automáticamente admin.

**ClubDetailPage** — Vista completa de un club. Tiene tres pestañas:
- **Debates** — hilos de chat con mensajes en estilo WhatsApp
- **Miembros** — lista con roles y opción de expulsar (admin)
- **Solicitudes** — solicitudes pendientes de ingreso (solo en clubs privados, solo admin)

Además muestra sidebar con info del club, libro del mes y quién lo creó.

**ShelvesPage** — Gestión de estanterías personales. Permite crear estanterías, añadir libros, cambiar el estado de lectura (quiero leer / leyendo / leído) y mover libros entre estanterías.

**ProfilePage** — Mi perfil. Permite editar nombre, bio, subir avatar, cambiar contraseña y configurar privacidad. Muestra mis posts y la lista de seguidores/seguidos en modales.

**PublicProfilePage** — Perfil de otro usuario. Respeta la privacidad: si el perfil es privado y no le sigues, no ves sus estanterías ni clubs. Puedes seguirle (o solicitar seguirle si es privado).

**UsersPage** — Buscador de usuarios por nombre. Muestra resultados con opción de seguir directamente desde la lista.

**AdminPage** — Panel de administración. Lista todos los usuarios (con opción de dar/quitar rol admin y borrar cuenta) y todos los clubs (con opción de borrarlos).

---

## 9. Frontend — Componentes y capa API

### Navbar
La barra de navegación tiene dos partes: desktop y móvil (hamburguesa). Incluye:
- Campana de notificaciones: hace polling cada 60 segundos para actualizar el contador. Al abrir, llama a la API y marca todas como leídas.
- Menú de usuario: avatar, nombre, links a perfil/estanterías/cerrar sesión.

### AuthContext
Es el corazón de la autenticación en el frontend. Usa React Context para compartir el estado del usuario logueado en toda la app. Al cargar la app hace `GET /api/auth/me` para comprobar si hay sesión activa.

```typescript
const { user, login, logout, refresh } = useAuth()
```

### PrivateRoute
Wrapper que comprueba si `user !== null`. Si no hay usuario redirige a `/login`. Si hay usuario pero no tiene el rol adecuado devuelve error.

### PostCard
Tarjeta de publicación. Muestra imagen, descripción, botón de like (con estado optimista), contador de comentarios y el panel de comentarios expandible. El autor del post y los autores de los comentarios son enlaces al perfil.

### Toast
Sistema de notificaciones visuales. Es un Provider que envuelve la app y expone `useToast()` para mostrar mensajes de éxito/error/info que desaparecen solos tras 3.5 segundos.

### ConfirmDialog
Modal de confirmación reutilizable que reemplaza el `confirm()` nativo del navegador. Tiene animación de entrada, blur de fondo, variante `danger` (rojo) para acciones destructivas. Se cierra con Escape o click fuera.

### Spinner
Componente de carga simple (rueda giratoria SVG). Se usa en botones mientras se procesa una petición y en páginas mientras cargan datos.

### Footer
Pie de página presente en todas las vistas. Muestra los links de navegación principales y adapta su contenido según si el usuario está logueado o no: si no hay sesión muestra "Iniciar sesión / Crear cuenta"; si hay sesión muestra "Configuración". Usa `useAuth()` para saber el estado de la sesión.

### La capa `api/` — cómo se hacen las peticiones HTTP

Todas las peticiones al backend pasan por dos funciones centrales en `api/client.ts`:

```typescript
// Para JSON normal (GET, POST con body JSON, DELETE…)
export async function apiFetch<T>(path, method = 'GET', body?) {
  const res = await fetch('/api' + path, {
    method,
    credentials: 'include',   // envía la cookie PHPSESSID automáticamente
    headers: body ? { 'Content-Type': 'application/json' } : {},
    body: body ? JSON.stringify(body) : undefined,
  })
  if (res.status === 204) return undefined   // no content → no parseamos
  const data = await res.json()
  if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`)
  return data
}

// Para subir imágenes (avatar, posts)
export async function apiFormData<T>(path, formData) {
  const res = await fetch('/api' + path, {
    method: 'POST',
    credentials: 'include',   // sin Content-Type: el navegador lo pone solo con el boundary
    body: formData,
  })
  …
}
```

**¿Por qué `credentials: 'include'`?**  
Sin esta opción, el navegador no envía las cookies en peticiones `fetch()`. Al incluirla, la cookie `PHPSESSID` viaja con cada petición y Symfony sabe quién es el usuario.

Los ficheros `api/clubs.ts`, `api/posts.ts`, etc. importan `apiFetch` y simplemente llaman a las rutas correctas. Así toda la lógica de red está en un único sitio.

### SpaController — cómo el backend sirve React en producción

```php
#[Route('/{any}', requirements: ['any' => '^(?!api/).*'], priority: -10)]
public function index(): Response
{
    $indexPath = '…/public/app/index.html';
    return new Response(file_get_contents($indexPath), 200, ['Content-Type' => 'text/html']);
}
```

Este controlador tiene **prioridad -10** (muy baja). Se activa para **cualquier ruta que no empiece por `/api/`**. Devuelve siempre el mismo `index.html` de React. Así si alguien navega directamente a `/clubs/5` en el navegador, no obtiene un 404 del servidor: recibe el HTML de React, que se encarga de mostrar la página correcta gracias a React Router.

---

## 10. Funcionalidades principales explicadas

### Cómo funciona el feed de posts
El endpoint `GET /api/posts` devuelve hasta 40 posts. Para saber qué posts mostrar, el repositorio hace una query SQL con un JOIN entre posts y la tabla `follow`, filtrando los follows con `status = 'accepted'`. Así el feed incluye posts del usuario actual y de los usuarios que sigue.

```sql
SELECT p FROM Post p
WHERE p.user = :me
   OR p.user IN (
       SELECT f.following FROM Follow f
       WHERE f.follower = :me AND f.status = 'accepted'
   )
ORDER BY p.createdAt DESC
LIMIT 40
```

### Cómo funciona el sistema de seguimiento
- Si el usuario al que quieres seguir tiene `isPrivate = false` → el follow se crea directamente con `status = 'accepted'`
- Si tiene `isPrivate = true` → se crea con `status = 'pending'` y se envía una notificación de tipo `follow_request`
- El receptor puede aceptar o rechazar desde el panel de notificaciones. Al aceptar se actualiza el status a `'accepted'` y se envía notificación `follow_accepted` al que solicitó

### Cómo funciona Google Books
1. El usuario escribe en el buscador
2. El frontend llama a `GET /api/books/search?q=...`
3. El backend hace una petición HTTP a `https://www.googleapis.com/books/v1/volumes`
4. Filtra los resultados que no tienen portada
5. Los ordena por popularidad (`ratingsCount × averageRating`)
6. Devuelve el JSON al frontend
7. Cuando el usuario hace click en un libro, si ese libro no está en nuestra BD, se importa automáticamente desde Google y se guarda como entidad `Book`

### Cómo funciona la subida de imágenes
**Avatar:**
1. El usuario selecciona un archivo en el formulario del perfil
2. El frontend crea un `FormData` y lo envía a `POST /api/profile/avatar` con `Content-Type: multipart/form-data`
3. El backend valida la extensión (jpg, jpeg, png, gif, webp), genera un nombre único (`avatar_XXXXX.jpg`) y lo guarda en `/public/uploads/avatars/`
4. Borra el avatar anterior si existía
5. Devuelve el perfil completo actualizado

**Posts:** igual pero guarda en `/public/uploads/posts/`

En producción (Railway) los archivos se guardan en el sistema de archivos efímero del contenedor, por eso al reiniciar se pierden — es una limitación conocida del despliegue actual.

### Cómo funcionan los clubes privados
- Los clubs pueden ser `public` o `private`
- Si un usuario intenta unirse a un club `private`, se crea un `ClubJoinRequest` con `status = 'pending'`
- Los admins del club ven las solicitudes en la pestaña "Solicitudes" del club
- Al aprobar se crea el `ClubMember` y se actualiza la solicitud a `approved`
- Al rechazar se actualiza a `rejected`
- El usuario que solicitó recibe una notificación de `club_approved` o `club_rejected`

### Sistema de notificaciones
Las notificaciones se crean en el backend cuando ocurren estos eventos:
- Alguien te sigue → `follow` o `follow_request`
- Alguien acepta tu follow → `follow_accepted`
- Alguien da like a tu post → `like`
- Alguien comenta en tu post → `comment`
- Alguien solicita unirse a tu club (eres admin) → `club_request`
- Te aprueban o rechazan en un club → `club_approved` / `club_rejected`

El frontend hace polling cada 60 segundos. Cuando abres el panel, las marca todas como leídas. Puedes aceptar/rechazar solicitudes de seguimiento o de club directamente desde el panel de notificaciones sin salir de la página.

**Deduplicación de notificaciones de follow:** antes de crear una notificación `follow` o `follow_request`, el backend borra cualquier notificación previa del mismo actor hacia el mismo receptor con esos tipos. Así si alguien sigue, deja de seguir y vuelve a seguir no se acumulan notificaciones — siempre hay como máximo una.

### Privacidad del perfil
Cada usuario controla:
- `isPrivate` → si activo, los nuevos follows van a `pending` hasta que los aceptes
- `shelvesPublic` → si activo, cualquiera puede ver tus estanterías en tu perfil
- `clubsPublic` → si activo, cualquiera puede ver los clubs a los que perteneces

---

## 11. Despliegue y Docker

### Estructura del contenedor
El backend corre en un único contenedor Docker con:
- **PHP-FPM** — ejecuta el código Symfony
- **Nginx** — sirve el frontend (build estática) y hace de proxy hacia PHP-FPM para las rutas `/api`
- **Supervisor** — mantiene ambos procesos corriendo y los reinicia si caen

### `entrypoint.sh` — qué hace al arrancar el contenedor
```sh
mkdir -p public/uploads/avatars public/uploads/posts   # crea directorios de uploads
php bin/console doctrine:migrations:migrate            # aplica migraciones pendientes en BD
php bin/console cache:warmup                           # precalienta la caché de Symfony
chown -R www-data:www-data var/ public/uploads/        # permisos correctos
supervisord                                            # arranca PHP-FPM + Nginx
```

### Railway
- El código se despliega desde Git automáticamente al hacer push a main
- Las variables de entorno (DATABASE_URL, APP_SECRET, etc.) se configuran en el dashboard de Railway
- La base de datos MySQL también corre en Railway como servicio separado

### Variables de entorno importantes
```env
DATABASE_URL=mysql://user:pass@host:3306/dbname?serverVersion=8.0
APP_ENV=prod
APP_SECRET=xxxxx
```

---

## 12. Decisiones de diseño — Por qué elegí esto

**¿Por qué Symfony y no Laravel?**
Symfony es el framework que se estudia en el grado. Además tiene un sistema de seguridad muy completo, inyección de dependencias y ORM (Doctrine) bien integrados.

**¿Por qué React y no Vue o Angular?**
React tiene la mayor comunidad y demanda laboral. Es componente-céntrico, lo que encaja bien con una SPA donde hay muchos elementos reutilizables (PostCard, ConfirmDialog, Spinner…).

**¿Por qué sesiones y no JWT?**
Las sesiones son más seguras para aplicaciones web: la cookie `HttpOnly` no es accesible desde JavaScript (protege contra XSS). JWT requiere gestión del token en el frontend lo que aumenta la superficie de ataque. Para una app que corre en el mismo dominio, las sesiones son la mejor opción.

**¿Por qué CSS puro y no Tailwind?**
Para tener control total sobre el diseño y aprender CSS en profundidad. Los estilos están organizados en 9 archivos con variables CSS (tokens) que definen el sistema de diseño (colores, bordes, sombras).

**¿Por qué Google Books y no crear una BD de libros propia?**
Google Books tiene millones de libros catalogados. Crear y mantener esa BD sería inviable para un TFG. La integración vía API permite tener datos reales con mínimo esfuerzo.

**¿Por qué Railway y no un VPS propio?**
Railway automatiza el despliegue desde Git, gestiona SSL, escala automáticamente y tiene un plan gratuito. Para un TFG es mucho más práctico que configurar un servidor desde cero.

---

## 13. Preguntas frecuentes en defensa

**¿Cómo se protegen las contraseñas?**
Con bcrypt a través del `UserPasswordHasher` de Symfony. Nunca se guarda la contraseña en texto plano. bcrypt incluye un salt aleatorio y es computacionalmente costoso, lo que hace inviable un ataque de fuerza bruta.

**¿Qué pasa si el token de sesión es robado?**
La cookie de sesión tiene `HttpOnly` (no accesible desde JS) y `SameSite=Lax` (no se envía en peticiones de otros dominios). En producción con HTTPS también sería `Secure`. El riesgo de robo es bajo en condiciones normales.

**¿Cómo evitas que un usuario borre el post de otro?**
Antes de borrar, el controlador comprueba que el `user.id` del post coincide con el `id` del usuario autenticado. Si no coincide (y no es admin) devuelve `404`. No se devuelve `403` para no revelar que el recurso existe.

**¿Cómo funciona la paginación del chat?**
Los mensajes se piden por páginas. `GET /api/clubs/{id}/chats/{id}/messages?page=1&limit=50`. Hay un índice compuesto en `(chat_id, created_at)` en la BD para que la consulta sea eficiente aunque haya miles de mensajes.

**¿Qué es Doctrine ORM?**
Es un mapeador objeto-relacional. Permite trabajar con objetos PHP en lugar de escribir SQL directamente. Doctrine traduce las operaciones sobre objetos a queries SQL. Por ejemplo: `$em->persist($post); $em->flush();` genera el INSERT correspondiente.

**¿Qué es una migración de base de datos?**
Un fichero PHP que describe un cambio en el esquema de la BD (crear tabla, añadir columna, etc.) y también cómo revertirlo (`up()` y `down()`). Doctrine Migrations gestiona qué migraciones ya se han aplicado para no repetirlas.

**¿Qué es React Context?**
Es una forma de compartir datos entre componentes sin tener que pasar props manualmente por todos los niveles. `AuthContext` guarda el usuario logueado y cualquier componente de la app puede leerlo con `useAuth()`.

**¿Qué es una SPA?**
Single Page Application. La página HTML se carga una sola vez. La navegación entre secciones la gestiona JavaScript (React Router) cambiando el estado interno y la URL sin pedir una nueva página al servidor. Esto hace la app más rápida y con transiciones más fluidas.

**¿Cómo se maneja el estado de carga asíncrona?**
Con `useState` para guardar los datos y `useEffect` para disparar la petición al montar el componente. Mientras carga se muestra un `Spinner`. Si hay error se muestra un mensaje. Es el patrón estándar de React para datos asíncronos.

**¿Por qué el feed solo muestra 40 posts?**
Para limitar el tiempo de respuesta y el consumo de memoria. En una app real se implementaría paginación infinita (cursor-based pagination), pero para el TFG es suficiente con los 40 más recientes.

**¿Qué es Docker y para qué lo usas?**
Docker permite empaquetar la aplicación con todas sus dependencias en un contenedor que se ejecuta de forma idéntica en cualquier máquina. Así no hay problemas de "en mi máquina funciona". El Dockerfile define cómo construir la imagen y el entrypoint.sh qué ejecutar al arrancar.

**¿Cómo funciona `apiFetch`? ¿Por qué no usas axios?**
`apiFetch` es una pequeña función propia en `api/client.ts` que envuelve el `fetch` nativo del navegador. Añade automáticamente `credentials: 'include'` (para las cookies), serializa el cuerpo a JSON y lanza un error si la respuesta no es `ok`. No hace falta axios para eso — axios añadiría una dependencia extra sin beneficio real.

**¿Qué pasa si el usuario recarga la página en `/clubs/5`?**
El navegador hace una petición GET a `/clubs/5` al servidor. El `SpaController` de Symfony tiene una ruta comodín que captura cualquier URL que no empiece por `/api/` y devuelve el `index.html` de React. Entonces React carga, React Router lee la URL y renderiza la página del club. Sin ese fallback, el servidor devolvería un 404.

**¿Qué es TypeScript y por qué lo usas?**
TypeScript es JavaScript con tipos estáticos. Permite al editor detectar errores antes de ejecutar el código: si llamas a `club.namee` cuando la propiedad se llama `name`, el compilador te lo dice en rojo inmediatamente. En una app con 40+ ficheros y varias APIs es clave para no perder el rastro de qué forma tiene cada objeto.

**¿Cómo validas los datos en el backend?**
Los controladores validan manualmente los campos del JSON recibido. Por ejemplo, antes de crear un post se comprueba que la imagen no esté vacía. Para el avatar se comprueba que la extensión esté en la lista permitida (`jpg, jpeg, png, gif, webp`). Para las contraseñas se pide una longitud mínima. No se usa el Validator de Symfony (que sería para formularios web), porque la API recibe JSON directamente.

**¿Cómo sabes que un usuario es admin del club antes de dejarle hacer una acción?**
El controlador hace una consulta al `ClubMemberRepository` buscando si existe un `ClubMember` con ese `user_id`, ese `club_id` y `role = 'admin'`. Si no existe, devuelve 403. Esto ocurre en endpoints como expulsar miembros, aprobar solicitudes o crear hilos de chat.

**¿Qué es el `dicebear` que aparece en el código de clubes?**
Es una API externa que genera avatares SVG automáticamente a partir de un texto (por ejemplo, las iniciales del nombre). Se usa como avatar por defecto cuando el usuario no ha subido ninguna imagen. La URL es pública y no requiere autenticación.

**¿Qué es `useEffect` y cuándo se ejecuta?**
`useEffect` es un hook de React que ejecuta una función cuando el componente se monta o cuando cambia alguna de sus dependencias. Se usa para disparar las peticiones a la API al cargar la página. Sin `useEffect`, no hay forma de ejecutar código asíncrono al montar un componente de forma segura en React.

**¿Cómo funciona el like optimista?**
Cuando el usuario da like a un post, el frontend actualiza el contador inmediatamente en la UI (estado local) sin esperar la respuesta del servidor. A la vez, envía `POST /api/posts/{id}/like`. Si la petición falla, revierte el cambio. Esto hace la app sentir más rápida.

**¿Por qué los posts se pueden perder al reiniciar el contenedor en producción?**
Los ficheros subidos (avatares, imágenes de posts) se guardan en el sistema de ficheros del contenedor en `/public/uploads/`. Railway no tiene almacenamiento persistente por defecto, así que al redesplegar el contenedor se crea uno nuevo y vacío. La solución en producción real sería usar un servicio de almacenamiento externo como S3 o Cloudinary. Para el TFG se documenta como limitación conocida.

---

---

## 14. Errores conocidos resueltos

**Reseñas daban error 500**
La tabla `book_review` no tenía migración. El contenedor arranca con `doctrine:migrations:migrate`, que solo aplica los ficheros `.php` de `migrations/`. Al no existir `Version20260408000000.php`, la tabla nunca se creó y cualquier operación de reseña fallaba con un error de "table not found". Solución: se creó la migración con `CREATE TABLE book_review` y sus claves foráneas hacia `user` y `book`.

**Notificaciones de follow acumuladas**
Al hacer follow/unfollow varias veces, cada nuevo follow creaba una `Notification` nueva sin borrar la anterior. Solución: en `FollowApiController::follow()` se llama a `NotificationRepository::deleteFollowNotifications()` justo antes de crear la notificación, que borra con una sola query DQL todas las previas del mismo actor→recipient de tipo `follow` o `follow_request`.

---

*Documentación generada el 28/04/2026*
