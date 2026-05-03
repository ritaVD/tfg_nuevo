# Documentación Backend — TFGdaw

Bienvenido a la documentación técnica del backend de **TFGdaw**, una plataforma social de clubes de lectura construida con Symfony 7 y una API REST que consume un frontend en React.

---

## Índice de documentos

| # | Documento | Descripción |
|---|-----------|-------------|
| 1 | [Arquitectura general](01-arquitectura.md) | Stack tecnológico, estructura de carpetas y flujo de una petición |
| 2 | [Configuración](02-configuracion.md) | Archivos `.env`, bundles, paquetes y seguridad |
| 3 | [Modelo de datos — Entidades](03-entidades.md) | Todas las entidades Doctrine y sus relaciones |
| 4 | [Controladores API](04-controladores.md) | Endpoints REST: rutas, parámetros y respuestas |
| 5 | [Seguridad y autenticación](05-seguridad.md) | Login JSON, handlers, roles y autorización |
| 6 | [Repositorios](06-repositorios.md) | Consultas personalizadas a la base de datos |
| 7 | [Migraciones](07-migraciones.md) | Historial de cambios en el esquema de la base de datos |
| 8 | [Flujos principales](08-flujos-principales.md) | Casos de uso end-to-end: registro, posts, follows, clubes |
| 9 | [Integración Google Books](09-google-books.md) | Búsqueda, importación automática y re-ranking de libros |
| 10 | [Sistema de notificaciones](10-sistema-notificaciones.md) | Ciclo de vida completo de las notificaciones |
| 11 | [Gestión de imágenes](11-gestion-imagenes.md) | Subida, almacenamiento y eliminación de archivos |
| 12 | [Instalación y despliegue](12-despliegue.md) | Guía paso a paso para desarrollo y producción |
| 13 | [Módulo de Clubes](13-modulo-clubs.md) | Membresía, solicitudes, libro del mes y hilos de debate |
| 14 | [Módulo Social](14-modulo-social.md) | Posts, likes, comentarios y sistema de follows |
| 15 | [Módulo de Libros](15-modulo-libros.md) | Estanterías, progreso de lectura y reseñas |
| 16 | [Referencia JSON](16-referencia-json.md) | Estructura completa de todas las respuestas de la API |
| 17 | [Patrones de código](17-patrones-codigo.md) | Convenciones recurrentes: constructores, serialización, autorización, validación |
| 18 | [Optimización de consultas](18-optimizacion-consultas.md) | Problema N+1, batch queries, eager loading, paginación con Doctrine |
| 19 | [Modelo de privacidad](19-modelo-privacidad.md) | Flags `isPrivate`, `shelvesPublic`, `clubsPublic` y visibilidad de clubes |
| 20 | [Panel de administración](20-panel-administracion.md) | Endpoints admin: estadísticas, gestión de usuarios, clubes y posts |
| 21 | [Módulo de perfil de usuario](21-modulo-perfil-usuario.md) | Editar perfil, avatar, contraseña, privacidad, búsqueda y perfil público |
| 22 | [Registro, sesión y SPA](22-registro-y-sesion.md) | Registro con displayName único, login/logout con handlers, SpaController |
| 23 | [Repositorios — detalle de consultas](23-repositorios-detalle.md) | NotificationRepository, FollowRepository, UserRepository y patrones DQL avanzados |
| 24 | [Controlador de Posts](24-controlador-posts.md) | Feed, creación multipart, toggle like, comentarios, permisos de borrado |
| 25 | [Controlador de Follows](25-controlador-follows.md) | Seguir, dejar de seguir, expulsar, solicitudes para cuentas privadas |
| 26 | [Controlador de Clubes](26-controlador-clubs.md) | CRUD, membresía, solicitudes de ingreso, libro del mes con rango de fechas |
| 27 | [Controlador de Chat de Clubes](27-controlador-clubchat.md) | Hilos de debate, mensajes paginados, permisos por rol, helper resolveChat() |
| 28 | [Controlador de Reseñas](28-controlador-resenas.md) | Patrón upsert, reseña única por libro, estadísticas actualizadas en tiempo real |
| 29 | [Controlador de Progreso de Lectura](29-controlador-progreso-lectura.md) | Modos pages/percent, idempotencia al crear, computed percent, array_key_exists vs isset |
| 30 | [Controlador de Estanterías](30-controlador-estanterias.md) | CRUD estanterías, añadir/mover/quitar libros, auto-importación, ruta /full |
| 31 | [Manual de usuario](31-manual-usuario.md) | Guía paso a paso de todas las funcionalidades desde el punto de vista del usuario final |
| 32 | [Diagrama E/R y paso a tablas](32-paso-a-tablas.md) | Modelo Entidad-Relación completo y traducción a tablas relacionales con tipos, PKs, FKs y restricciones |
| 33 | [Accesibilidad WAI-A y comunicación asíncrona](33-accesibilidad-y-comunicacion.md) | Criterios WCAG 2.1 nivel A aplicados y documentación de la Fetch API asíncrona con validación cliente |
| 34 | [Usabilidad — Heurísticas de Nielsen](34-usabilidad.md) | Las 10 heurísticas de usabilidad de Jakob Nielsen aplicadas a TFGdaw con ejemplos de código |
| 35 | [Figuras para el TFG.docx](35-figuras-para-tfg-docx.md) | Pies de figura y párrafos de referencia listos para insertar en el documento Word del TFG |

---

## Resumen rápido

- **Framework:** Symfony 7
- **Base de datos:** MySQL / PostgreSQL a través de Doctrine ORM
- **Autenticación:** Sesiones PHP con login JSON
- **API externa:** Google Books API
- **Frontend:** React SPA servida desde `public/app/`
- **Almacenamiento de imágenes:** `public/uploads/posts/`

---

## Usuario de prueba — Administrador general

Al arrancar el contenedor por primera vez, el comando `app:create-admin` crea automáticamente una cuenta con privilegios de administrador. Estas credenciales están pensadas únicamente para pruebas y evaluación de la plataforma.

| Campo        | Valor              |
|--------------|--------------------|
| Email        | `admin@gmail.com`  |
| Contraseña   | `123456`           |
| Rol          | `ROLE_ADMIN`       |

Con esta cuenta se puede acceder al panel de administración (`/admin`) y realizar cualquier acción privilegiada: ver estadísticas globales, gestionar usuarios (banear/desbanear, eliminar), moderar posts y clubes. El comando es idempotente: si el usuario ya existe simplemente le asigna `ROLE_ADMIN` sin duplicarlo.

---

> Consulta cada documento en orden para obtener una comprensión completa del sistema.

---

# 01 — Arquitectura General

## 1. Descripción del proyecto

**TFGdaw** es una plataforma social de clubes de lectura. El backend expone una **API REST en JSON** que consume un frontend en React. Ambas partes viven en el mismo repositorio pero se desarrollan de forma independiente:

```
TFGdaw/
├── backend/    ← Symfony 7 (este documento)
└── frontend/   ← React + Vite
```

---

## 2. Stack tecnológico

| Capa | Tecnología | Versión |
|------|------------|---------|
| Lenguaje | PHP | ≥ 8.2 |
| Framework | Symfony | 7.x |
| ORM | Doctrine ORM | 3.x |
| Base de datos | MySQL / PostgreSQL | – |
| Autenticación | Sesiones PHP + JSON Login | – |
| API externa | Google Books API | v1 |
| Servidor (dev) | Symfony CLI / PHP built-in server | – |
| Contenedores | Docker + Docker Compose | – |
| Pruebas | PHPUnit | – |

---

## 3. Estructura de carpetas

```
backend/
├── config/                  # Configuración de Symfony (bundles, seguridad, rutas, etc.)
│   ├── packages/            # Configuración de cada bundle/paquete
│   ├── routes/              # Archivos de rutas adicionales
│   ├── bundles.php          # Lista de bundles habilitados
│   ├── routes.yaml          # Punto de entrada del sistema de rutas
│   └── services.yaml        # Contenedor de servicios e inyección de dependencias
│
├── migrations/              # Migraciones de base de datos (Doctrine Migrations)
│
├── public/                  # Directorio público (accesible desde el navegador)
│   ├── index.php            # Front controller — punto de entrada de toda petición HTTP
│   ├── app/                 # SPA React compilada (generada con `npm run build`)
│   └── uploads/posts/       # Imágenes subidas por los usuarios
│
├── src/                     # Código fuente de la aplicación
│   ├── Controller/          # Controladores HTTP
│   │   ├── Api/             # Controladores de la API REST
│   │   └── SpaController.php# Sirve el frontend React
│   ├── Entity/              # Entidades Doctrine (modelo de datos)
│   ├── Repository/          # Repositorios con consultas personalizadas
│   ├── Security/            # Autenticadores y handlers de login/logout
│   └── Kernel.php           # Núcleo de la aplicación Symfony
│
├── templates/               # Plantillas Twig (emails, páginas de error, etc.)
├── tests/                   # Pruebas PHPUnit
├── var/                     # Caché, logs y archivos temporales (generados, no versionar)
├── vendor/                  # Dependencias PHP (generadas por Composer, no versionar)
├── .env                     # Variables de entorno (base de datos, claves API, etc.)
├── composer.json            # Definición de dependencias PHP
├── Dockerfile               # Imagen Docker del backend
└── compose.yaml             # Orquestación Docker Compose
```

---

## 4. Flujo de una petición HTTP

El siguiente diagrama describe el ciclo completo desde que el navegador hace una petición hasta que recibe una respuesta:

```
Navegador / React
       │
       │  HTTP Request (GET /api/clubs, POST /api/login, etc.)
       ▼
  public/index.php          ← Front Controller (único punto de entrada)
       │
       ▼
  Symfony Kernel             ← Inicializa la aplicación, carga bundles y configuración
       │
       ▼
  Firewall de Seguridad      ← Verifica si la ruta requiere autenticación
       │ (si requiere auth y no hay sesión → 401 Unauthorized)
       │ (si hay sesión válida → continúa)
       ▼
  Router                     ← Mapea la URL al controlador correcto
       │
       ▼
  Controlador API            ← Ejecuta la lógica de negocio
       │
       ├── Repositorio       ← Consulta la base de datos vía Doctrine ORM
       │       │
       │       ▼
       │   Base de datos (MySQL/PostgreSQL)
       │
       ├── Google Books API  ← (solo BookExternalApiController)
       │
       ▼
  JsonResponse               ← Respuesta en formato JSON
       │
       ▼
Navegador / React
```

### Caso especial: rutas no-API (SPA)

Cualquier ruta que **no empiece por `/api/`** es capturada por `SpaController` con prioridad `-10` (la más baja posible), que devuelve el `index.html` del frontend React. Esto permite que React Router gestione la navegación del lado del cliente sin que Symfony interfiera.

---

## 5. Modo de desarrollo vs. producción

### Desarrollo
1. El frontend corre en `localhost:5173` (servidor Vite) con **hot-reload**.
2. El backend corre en `localhost:8000` (Symfony CLI).
3. Vite tiene configurado un proxy para redirigir `/api/*` al backend.

### Producción
1. Se ejecuta `npm run build` dentro de `frontend/`, que genera los archivos estáticos en `backend/public/app/`.
2. El backend sirve tanto la API como el frontend desde el mismo servidor.
3. Doctrine desactiva la generación automática de proxies y activa el caché de consultas.

---

## 6. Variables de entorno principales

Definidas en `.env` (no versionar valores sensibles; usar `.env.local` en local):

| Variable | Descripción |
|----------|-------------|
| `DATABASE_URL` | Cadena de conexión a la base de datos (DSN de Doctrine) |
| `APP_SECRET` | Clave secreta de Symfony (tokens CSRF, cookies firmadas) |
| `GOOGLE_BOOKS_API_KEY` | Clave de la API de Google Books para búsqueda de libros |
| `MAILER_DSN` | Configuración del servidor de correo (verificación de email) |
| `APP_ENV` | Entorno activo: `dev`, `prod` o `test` |

---

## 7. Docker

El proyecto incluye configuración Docker para facilitar el despliegue:

- **`Dockerfile`**: construye la imagen PHP del backend con las extensiones necesarias.
- **`compose.yaml`**: define los servicios (PHP, base de datos) para producción.
- **`compose.override.yaml`**: sobreescrituras locales (puertos, volúmenes de código, etc.) para desarrollo.

---

# 02 — Configuración

Este documento explica todos los archivos de configuración del backend, ubicados en la carpeta `config/`.

---

## 1. `config/bundles.php` — Bundles habilitados

Un **bundle** es un plugin de Symfony que añade funcionalidad al framework. Este archivo declara qué bundles están activos y en qué entornos.

| Bundle | Entornos | Función |
|--------|----------|---------|
| `FrameworkBundle` | todos | Núcleo de Symfony: rutas, controladores, sesiones, caché... |
| `DoctrineBundle` | todos | Integración con Doctrine ORM (base de datos) |
| `DoctrineMigrationsBundle` | todos | Gestión de migraciones de base de datos |
| `SecurityBundle` | todos | Sistema de autenticación y autorización |
| `TwigBundle` | todos | Motor de plantillas (usado en emails y páginas de error) |
| `MonologBundle` | todos | Sistema de logging |
| `SymfonyCastsVerifyEmailBundle` | todos | Verificación de email en el registro |
| `StimulusBundle` | todos | Symfony UX Stimulus (JS controllers) |
| `TurboBundle` | todos | Symfony UX Turbo (navegación SPA-like con Hotwire) |
| `TwigExtraBundle` | todos | Filtros y funciones extra para Twig |
| `DebugBundle` | solo `dev` | Herramientas de depuración (`dump()`, etc.) |
| `WebProfilerBundle` | `dev` y `test` | Barra de depuración y profiler web |
| `MakerBundle` | solo `dev` | Generador de código (`make:entity`, `make:controller`, etc.) |

---

## 2. `config/routes.yaml` — Rutas principales

Define el punto de entrada del sistema de rutas:

```yaml
controllers:
    resource: routing.controllers   # Importa automáticamente las rutas definidas con #[Route]

api_login:
    path: /api/login
    methods: [POST]                 # Ruta especial gestionada por el firewall de seguridad
```

### Nota sobre `api_login`
Esta ruta **no tiene un controlador explícito**. Es interceptada directamente por el firewall `json_login` de Symfony (ver `security.yaml`), que procesa el email y contraseña y delega en los handlers de éxito/fallo.

---

## 3. `config/services.yaml` — Contenedor de servicios

Configura el sistema de **inyección de dependencias** de Symfony:

```yaml
services:
    _defaults:
        autowire: true      # Inyecta dependencias automáticamente por tipo
        autoconfigure: true # Registra automáticamente servicios especiales (event listeners, etc.)

    App\:
        resource: '../src/'  # Registra todo lo que hay en src/ como servicio
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'          # Las entidades no son servicios
            - '../src/Kernel.php'
```

Gracias a `autowire: true`, los controladores, repositorios y servicios reciben sus dependencias automáticamente en el constructor sin necesidad de configuración manual.

---

## 4. `config/packages/doctrine.yaml` — Base de datos

Configura Doctrine ORM, la capa de abstracción de base de datos:

### DBAL (capa de conexión)
```yaml
dbal:
    url: '%env(resolve:DATABASE_URL)%'  # Lee la URL de conexión de .env
    use_savepoints: true                 # Soporte para transacciones anidadas
```

### ORM (mapeo objeto-relacional)
```yaml
orm:
    naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
    # Convierte CamelCase a snake_case en los nombres de tablas/columnas
    # Ej: ShelfBook → shelf_book, createdAt → created_at
    
    mappings:
        App:
            type: attribute     # Las entidades se definen con atributos PHP #[Entity], #[Column], etc.
            dir: '%kernel.project_dir%/src/Entity'
```

### En producción (`when@prod`)
- Se desactiva la generación automática de proxies (se precompilan).
- Se activan pools de caché para consultas y metadatos, mejorando el rendimiento.

---

## 5. `config/packages/security.yaml` — Seguridad

Es el archivo más importante para el control de acceso. Ver también el documento [05-seguridad.md](05-seguridad.md) para más detalle.

### Hashing de contraseñas
```yaml
password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```
`'auto'` selecciona el algoritmo más seguro disponible en el servidor (actualmente **bcrypt** o **argon2id**). Las contraseñas nunca se almacenan en texto plano.

### Proveedor de usuarios
```yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
            property: email   # El usuario se busca en BD por su email
```

### Firewalls
Los firewalls definen cómo se gestiona la seguridad en cada área de la aplicación:

```yaml
firewalls:
    dev:
        pattern: ^/(_profiler|_wdt|assets|build)/
        security: false   # El profiler y los assets no requieren autenticación

    main:
        lazy: true          # El usuario no se carga de sesión hasta que sea necesario
        provider: app_user_provider
        json_login:
            check_path: api_login           # POST /api/login
            username_path: email            # Campo del JSON que contiene el email
            password_path: password         # Campo del JSON que contiene la contraseña
            success_handler: App\Security\JsonLoginSuccessHandler
            failure_handler: App\Security\JsonLoginFailureHandler
        stateless: false    # Usa sesiones PHP (no JWT/tokens)
```

### Control de acceso
```yaml
access_control:
    # Las reglas de acceso por ruta están gestionadas directamente
    # en cada controlador con #[IsGranted('ROLE_USER')] o lógica interna.
```

---

## 6. `config/packages/doctrine_migrations.yaml` — Migraciones

```yaml
doctrine_migrations:
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/migrations'
```

Los archivos de migración se guardan en `migrations/` con el namespace `DoctrineMigrations`. Cada archivo representa un cambio en el esquema de la base de datos. Ver [07-migraciones.md](07-migraciones.md).

---

## 7. Otros paquetes de configuración

### `framework.yaml`
- Activa el componente de sesiones PHP.
- Define el `app_secret` desde la variable de entorno.

### `mailer.yaml`
- Configura el transporte de correo mediante `MAILER_DSN`.
- Usado por `EmailVerifier` para enviar correos de verificación de cuenta.

### `monolog.yaml`
- Configura los canales y handlers de logging.
- En `dev`: logs detallados en `var/log/dev.log`.
- En `prod`: solo errores y warnings para no saturar el log.

### `cache.yaml`
- Define los pools de caché de la aplicación.
- En producción, Doctrine usa estos pools para cachear resultados de consultas.

### `messenger.yaml`
- Configura el componente de mensajería asíncrona de Symfony.
- Permite enviar emails y procesar tareas en segundo plano mediante colas.

### `http_client.yaml`
- Configura el cliente HTTP de Symfony.
- Usado por `BookExternalApiController` para hacer peticiones a la API de Google Books.

### `validator.yaml`
- Activa la validación automática de entidades con anotaciones/atributos.

### `translator.yaml`
- Configura el idioma por defecto (`es` o `en`).
- Permite internacionalizar mensajes de error y de la interfaz.

### `web_profiler.yaml` (solo dev/test)
- Activa la barra de depuración visible en el navegador durante el desarrollo.
- Muestra: tiempo de respuesta, consultas SQL ejecutadas, memoria usada, rutas, etc.

---

## 8. `config/routes/` — Rutas adicionales

| Archivo | Función |
|---------|---------|
| `framework.yaml` | Rutas internas del framework (assets, etc.) |
| `security.yaml` | Puede definir rutas de logout si se usa form_login |
| `web_profiler.yaml` | Rutas del profiler (`/_profiler`, `/_wdt`) — solo dev |

---

# 03 — Modelo de datos: Entidades

Las entidades son las clases PHP que representan las tablas de la base de datos. Doctrine ORM mapea automáticamente cada clase a una tabla y cada propiedad a una columna. Todas las entidades se encuentran en `src/Entity/`.

---

## Diagrama de relaciones (simplificado)

```
User ──────────────────────────────────────────────────────┐
  │                                                         │
  ├── (1:N) Shelf ── (1:N) ShelfBook ── (N:1) Book          │
  │                                                         │
  ├── (1:N) ReadingProgress ── (N:1) Book                   │
  │                                                         │
  ├── (1:N) BookReview ── (N:1) Book                        │
  │                                                         │
  ├── (1:N) Post ── (1:N) PostLike                          │
  │            └── (1:N) PostComment                        │
  │                                                         │
  ├── (N:M via Follow) User                                 │
  │                                                         │
  ├── (1:N) Club (owner) ─── (1:N) ClubMember ◄────────────┘
  │              └── (1:N) ClubJoinRequest
  │              └── (1:N) ClubChat ── (1:N) ClubChatMessage
  │              └── (N:1) Book (currentBook)
  │
  └── (1:N) Notification
```

---

## 1. `User` — Usuario

**Tabla:** `user`

Entidad central del sistema. Representa a cada persona registrada en la plataforma. Implementa `UserInterface` y `PasswordAuthenticatedUserInterface` de Symfony para integrarse con el sistema de seguridad.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único autoincremental |
| `email` | string(180) | Email del usuario — único en la BD |
| `displayName` | string(80) | Nombre visible público — único en la BD |
| `password` | string | Contraseña hasheada (bcrypt/argon2) — nunca en texto plano |
| `roles` | array (JSON) | Lista de roles: siempre incluye `ROLE_USER`; opcionalmente `ROLE_ADMIN` |
| `bio` | string(255)? | Descripción opcional del perfil |
| `avatar` | string(255)? | URL o path del avatar del usuario |
| `isVerified` | bool | Si ha verificado su email (default: `false`) |
| `isPrivate` | bool | Perfil privado: los seguidores deben ser aprobados (default: `false`) |
| `shelvesPublic` | bool | Si sus estanterías son visibles para otros (default: `true`) |
| `clubsPublic` | bool | Si sus clubes son visibles para otros (default: `true`) |
| `isBanned` | bool | Si el usuario está suspendido y no puede iniciar sesión (default: `false`) |

**Relaciones:**
- → `Shelf` (1:N): estanterías propias
- → `Club` (1:N): clubes que ha creado (como propietario)
- → `ClubMember` (1:N): membresías en clubes ajenos
- → `ClubJoinRequest` (1:N): solicitudes de unión enviadas
- → `ClubChat` (1:N): hilos de chat creados
- → `ClubChatMessage` (1:N): mensajes enviados

**Nota de seguridad:** El método `__serialize()` almacena en sesión un hash CRC32c de la contraseña en lugar del hash completo, evitando que el hash bcrypt quede expuesto en los datos de sesión.

---

## 2. `Book` — Libro

**Tabla:** `book`

Almacena los metadatos de un libro. Los libros se obtienen originalmente desde la Google Books API y se guardan localmente para no depender siempre de la API externa.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador interno |
| `externalId` | string? | ID del libro en Google Books (ej: `"OL7353617M"`) |
| `externalSource` | string? | Fuente externa (actualmente siempre `"google_books"`) |
| `title` | string(255) | Título del libro |
| `authors` | array? | Lista de autores en JSON |
| `isbn10` | string? | ISBN-10 |
| `isbn13` | string? | ISBN-13 |
| `coverUrl` | text? | URL de la portada |
| `description` | text? | Sinopsis / descripción |
| `publisher` | string? | Editorial |
| `publishedDate` | string? | Fecha de publicación (como string por variabilidad de formato) |
| `language` | string? | Código de idioma (ej: `"es"`, `"en"`) |
| `pageCount` | int? | Número de páginas |
| `categories` | array? | Categorías/géneros en JSON |
| `createdAt` | DateTimeImmutable | Fecha de creación en nuestra BD |
| `updatedAt` | DateTimeImmutable | Fecha de última actualización |

**Lifecycle callbacks** (con `#[ORM\HasLifecycleCallbacks]`):
- `#[ORM\PrePersist]` → establece `createdAt` y `updatedAt` automáticamente al crear.
- `#[ORM\PreUpdate]` → actualiza `updatedAt` automáticamente al modificar.

---

## 3. `Shelf` — Estantería

**Tabla:** `shelf`

Una colección de libros perteneciente a un usuario. Un usuario puede tener múltiples estanterías con nombres personalizados (ej: "Leídos", "Por leer", "Favoritos").

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Propietario de la estantería |
| `name` | string(255) | Nombre de la estantería |
| `orderIndex` | int | Posición en la lista (para ordenar estanterías) |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `updatedAt` | DateTimeImmutable | Fecha de última modificación |

**Relaciones:**
- → `ShelfBook` (1:N): libros contenidos en esta estantería

---

## 4. `ShelfBook` — Libro en estantería

**Tabla:** `shelf_book`

Tabla de unión entre `Shelf` y `Book` con metadatos adicionales. Permite que el mismo libro aparezca en varias estanterías (de distintos usuarios) con estado diferente.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `shelf` | FK → Shelf | Estantería que contiene el libro |
| `book` | FK → Book | Libro añadido |
| `orderIndex` | int | Posición del libro dentro de la estantería |
| `status` | string(20)? | Estado de lectura: `"reading"`, `"read"`, `"want_to_read"`, etc. |
| `addedAt` | DateTimeImmutable | Cuándo se añadió el libro a la estantería |

**Restricción única:** `(shelf_id, book_id)` — un libro no puede estar dos veces en la misma estantería.

---

## 5. `ReadingProgress` — Progreso de lectura

**Tabla:** `reading_progress`

Permite al usuario llevar un seguimiento de su avance en la lectura de un libro, bien por páginas o por porcentaje.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Usuario que lleva el seguimiento |
| `book` | FK → Book | Libro del que se lleva seguimiento |
| `mode` | string | `"pages"` o `"percent"` |
| `currentPage` | int? | Página actual (si mode = "pages") |
| `totalPages` | int? | Total de páginas del libro |
| `percent` | float? | Porcentaje completado (si mode = "percent") |
| `startedAt` | DateTimeImmutable | Cuándo empezó a registrar el progreso |
| `updatedAt` | DateTimeImmutable | Última actualización del progreso |

**Restricción única:** `(user_id, book_id)` — solo un registro de progreso por usuario y libro.

---

## 6. `BookReview` — Reseña de libro

**Tabla:** `book_review`

Una reseña y puntuación que un usuario escribe sobre un libro.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Autor de la reseña |
| `book` | FK → Book | Libro reseñado |
| `rating` | int | Puntuación del 1 al 5 |
| `content` | text? | Texto de la reseña (opcional) |
| `createdAt` | DateTimeImmutable | Fecha de la reseña |

**Restricción única:** `(user_id, book_id)` — una sola reseña por usuario y libro.

---

## 7. `Post` — Publicación

**Tabla:** `post`

Una publicación en el feed social de la plataforma. Contiene una imagen y una descripción opcional, similar a una red social de fotos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `user` | FK → User | Autor de la publicación |
| `imagePath` | string | Ruta de la imagen subida (en `public/uploads/posts/`) |
| `description` | text? | Descripción o comentario de la publicación |
| `createdAt` | DateTimeImmutable | Fecha de creación |

**Relaciones:**
- → `PostLike` (1:N): likes recibidos
- → `PostComment` (1:N): comentarios recibidos

---

## 8. `PostLike` — Like en publicación

**Tabla:** `post_like`

Registra que un usuario ha dado like a una publicación.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `post` | FK → Post | Publicación likeada |
| `user` | FK → User | Usuario que dio el like |
| `createdAt` | DateTimeImmutable | Cuándo se dio el like |

**Restricción única:** `(post_id, user_id)` — un usuario solo puede dar like una vez por publicación.

---

## 9. `PostComment` — Comentario en publicación

**Tabla:** `post_comment`

Un comentario escrito por un usuario en una publicación.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `post` | FK → Post | Publicación comentada |
| `user` | FK → User | Autor del comentario |
| `content` | text | Texto del comentario |
| `createdAt` | DateTimeImmutable | Fecha del comentario |

---

## 10. `Follow` — Seguimiento entre usuarios

**Tabla:** `follow`

Representa la relación de seguimiento entre dos usuarios. El estado puede ser `pending` (esperando aprobación, en cuentas privadas) o `accepted` (seguimiento activo).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `follower` | FK → User | Usuario que sigue (el que envía la solicitud) |
| `following` | FK → User | Usuario seguido (el que recibe la solicitud) |
| `status` | string(10) | `"pending"` o `"accepted"` (default: `"accepted"`) |
| `createdAt` | DateTimeImmutable | Fecha de creación de la relación |

**Restricción única:** `(follower_id, following_id)` — no se puede seguir dos veces a la misma persona.

**Lógica de estados:**
- Si el perfil del usuario seguido es **público** → el estado se crea directamente como `accepted`.
- Si el perfil es **privado** → el estado se crea como `pending` y el usuario debe aprobar la solicitud desde las notificaciones.

**Métodos de negocio:** `accept()`, `isPending()`, `isAccepted()`.

---

## 11. `Club` — Club de lectura

**Tabla:** `club`

Un grupo de usuarios organizados en torno a la lectura de libros. Tiene un propietario, miembros, y puede tener un libro de lectura activo.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `owner` | FK → User | Usuario que creó y administra el club |
| `name` | string(255) | Nombre del club |
| `description` | text? | Descripción del club |
| `visibility` | string(10) | `"public"` (cualquiera puede unirse) o `"private"` (requiere solicitud) |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `updatedAt` | DateTimeImmutable | Fecha de última modificación |
| `currentBook` | FK → Book? | Libro que el club está leyendo actualmente |
| `currentBookSince` | DateTimeImmutable? | Desde cuándo se lee el libro actual |
| `currentBookUntil` | DateTimeImmutable? | Fecha objetivo para terminar el libro |

**Relaciones:**
- → `ClubMember` (1:N, orphanRemoval): miembros del club
- → `ClubJoinRequest` (1:N, orphanRemoval): solicitudes de unión
- → `ClubChat` (1:N, orphanRemoval): hilos de discusión
- → `Book` (N:1): libro actual (se pone a NULL si el libro es eliminado)

---

## 12. `ClubMember` — Miembro de club

**Tabla:** `club_member`

Representa la membresía de un usuario en un club, con un rol específico.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `club` | FK → Club | Club al que pertenece |
| `user` | FK → User | Miembro del club |
| `role` | string | `"admin"` (gestiona el club) o `"member"` (miembro normal) |
| `joinedAt` | DateTimeImmutable | Fecha de incorporación al club |

**Restricción única:** `(club_id, user_id)` — un usuario solo puede ser miembro una vez por club.

---

## 13. `ClubJoinRequest` — Solicitud de unión a club

**Tabla:** `club_join_request`

Cuando un club es privado, los usuarios envían una solicitud que debe ser aprobada o rechazada por un administrador.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `club` | FK → Club | Club al que se solicita unirse |
| `user` | FK → User | Usuario que envía la solicitud |
| `resolvedBy` | FK → User? | Administrador que procesó la solicitud |
| `status` | string | `"pending"`, `"approved"` o `"rejected"` |
| `requestedAt` | DateTimeImmutable | Cuándo se envió la solicitud |
| `resolvedAt` | DateTimeImmutable? | Cuándo fue procesada |

**Restricción única:** `(club_id, user_id)` — solo una solicitud activa por usuario y club.

---

## 14. `ClubChat` — Hilo de debate en club

**Tabla:** `club_chat`

Un hilo de discusión dentro de un club. Solo los administradores pueden crear hilos y abrirlos/cerrarlos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `club` | FK → Club | Club al que pertenece el hilo |
| `createdBy` | FK → User | Administrador que creó el hilo |
| `title` | string | Título del hilo (ej: "¿Qué os parece el capítulo 5?") |
| `isOpen` | bool | Si el hilo está abierto a nuevos mensajes (default: `true`) |
| `createdAt` | DateTimeImmutable | Fecha de creación |
| `closedAt` | DateTimeImmutable? | Fecha en que fue cerrado |

**Relaciones:**
- → `ClubChatMessage` (1:N): mensajes del hilo

---

## 15. `ClubChatMessage` — Mensaje en hilo de debate

**Tabla:** `club_chat_message`

Un mensaje escrito por un miembro del club dentro de un hilo de debate.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `chat` | FK → ClubChat | Hilo al que pertenece el mensaje |
| `user` | FK → User | Autor del mensaje |
| `content` | text | Contenido del mensaje |
| `createdAt` | DateTimeImmutable | Fecha del mensaje |

**Índice de base de datos:** `(chat_id, created_at)` para acelerar la consulta de mensajes por hilo ordenados cronológicamente.

---

## 16. `Notification` — Notificación

**Tabla:** `notification`

Registra los eventos que generan alertas para los usuarios (likes, comentarios, solicitudes de seguimiento, actividad en clubes).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int (PK) | Identificador único |
| `recipient` | FK → User | Usuario que recibe la notificación |
| `actor` | FK → User | Usuario que generó la acción |
| `type` | string(30) | Tipo de notificación (ver tabla de tipos) |
| `post` | FK → Post? | Publicación relacionada (en likes/comentarios) |
| `club` | FK → Club? | Club relacionado (en solicitudes de club) |
| `refId` | int? | ID auxiliar: `Follow.id` (follow_request) o `ClubJoinRequest.id` (club_request) |
| `isRead` | bool | Si el usuario ya ha visto la notificación (default: `false`) |
| `createdAt` | DateTimeImmutable | Cuándo se generó |

**Tipos de notificación:**

| Constante | Valor | Cuándo se genera |
|-----------|-------|-----------------|
| `TYPE_FOLLOW` | `"follow"` | Alguien empieza a seguirte (cuenta pública) |
| `TYPE_FOLLOW_REQUEST` | `"follow_request"` | Alguien solicita seguirte (cuenta privada) |
| `TYPE_FOLLOW_ACCEPTED` | `"follow_accepted"` | Aceptaron tu solicitud de seguimiento |
| `TYPE_LIKE` | `"like"` | Alguien le da like a tu publicación |
| `TYPE_COMMENT` | `"comment"` | Alguien comenta tu publicación |
| `TYPE_CLUB_REQUEST` | `"club_request"` | Alguien solicita unirse a tu club (notif. para admin) |
| `TYPE_CLUB_APPROVED` | `"club_approved"` | Tu solicitud de unión a un club fue aprobada |
| `TYPE_CLUB_REJECTED` | `"club_rejected"` | Tu solicitud de unión a un club fue rechazada |

**Método de negocio:** `markRead()` — marca la notificación como leída.

---

## Resumen de restricciones únicas

| Entidad | Columnas únicas | Propósito |
|---------|-----------------|-----------|
| `User` | `email` | No puede haber dos cuentas con el mismo email |
| `User` | `displayName` | No puede haber dos usuarios con el mismo nombre visible |
| `ShelfBook` | `(shelf_id, book_id)` | Un libro no puede estar dos veces en la misma estantería |
| `ReadingProgress` | `(user_id, book_id)` | Un solo registro de progreso por libro y usuario |
| `BookReview` | `(user_id, book_id)` | Una sola reseña por libro y usuario |
| `PostLike` | `(post_id, user_id)` | No se puede dar like dos veces a la misma publicación |
| `Follow` | `(follower_id, following_id)` | No se puede seguir dos veces a la misma persona |
| `ClubMember` | `(club_id, user_id)` | Un usuario solo puede ser miembro una vez por club |
| `ClubJoinRequest` | `(club_id, user_id)` | Solo una solicitud pendiente por usuario y club |

---

# 04 — Controladores API

Todos los controladores de la API se encuentran en `src/Controller/Api/`. Cada uno extiende `AbstractController` de Symfony y devuelve exclusivamente respuestas en formato JSON. Las rutas se definen con el atributo PHP `#[Route]`.

---

## Convenciones generales

- **Autenticación:** Los endpoints protegidos llaman a `$this->denyAccessUnlessGranted('ROLE_USER')` al inicio. Si el usuario no tiene sesión activa, Symfony devuelve automáticamente `401 Unauthorized`.
- **Autorización:** Se comprueba dentro del propio controlador (ej: verificar que el recurso pertenece al usuario antes de modificarlo).
- **Respuestas de error:** Siempre JSON con clave `error` y el código HTTP correspondiente.
- **Respuestas de éxito:** JSON con los datos del recurso. Las creaciones devuelven `201 Created`.
- **Eliminaciones:** Devuelven `204 No Content` (sin cuerpo).

---

## 1. `AuthApiController` — Autenticación

**Prefijo de ruta:** `/api/auth`

Gestiona el registro, la consulta del usuario actual y el cierre de sesión.

### `GET /api/auth/me`
Devuelve los datos básicos del usuario con sesión activa.

**Sin autenticación:** devuelve `401`.

**Respuesta (200):**
```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "displayName": "MiNombre",
  "avatar": "abc123.jpg",
  "roles": ["ROLE_USER"]
}
```

---

### `POST /api/auth/register`
Registra un nuevo usuario.

**Body JSON:**
```json
{ "email": "usuario@ejemplo.com", "password": "miClave123", "displayName": "MiNombre" }
```

**Validaciones:**
- `email` y `password` son obligatorios.
- El email debe tener formato válido.
- La contraseña debe tener al menos 6 caracteres.
- No puede existir otra cuenta con el mismo email.
- Si `displayName` ya está en uso, se le añade un sufijo numérico automáticamente.

**Respuesta (201):**
```json
{ "id": 42, "email": "usuario@ejemplo.com" }
```

---

### `POST /api/auth/logout`
Invalida la sesión actual del usuario.

**Respuesta (200):**
```json
{ "status": "logged_out" }
```

---

## 2. `UserApiController` — Perfil de usuario

**Prefijo de ruta:** `/api`

Gestiona el perfil del usuario autenticado y la consulta de perfiles públicos.

### `GET /api/profile`
Devuelve el perfil completo del usuario autenticado (email, bio, avatar, estadísticas, estanterías, clubes).

### `PUT /api/profile`
Actualiza `displayName` y/o `bio`.

**Validaciones de displayName:** mínimo 3 caracteres, solo letras/números/puntos/guiones, debe ser único.

### `POST /api/profile/avatar`
Sube una imagen de avatar. Recibe un archivo en el campo `avatar` (`multipart/form-data`). Guarda el archivo en `public/uploads/avatars/`.

### `PUT /api/profile/password`
Cambia la contraseña. Requiere enviar la contraseña actual para verificar identidad.

**Body JSON:**
```json
{ "currentPassword": "antigua", "newPassword": "nuevaClave123" }
```

### `PUT /api/profile/privacy`
Configura la privacidad del perfil.

**Body JSON:**
```json
{ "isPrivate": true, "shelvesPublic": false, "clubsPublic": true }
```

### `GET /api/users/search?q=...`
Busca usuarios por `displayName` (mínimo 2 caracteres). Devuelve lista con estado de seguimiento respecto al usuario actual.

### `GET /api/users/{id}`
Devuelve el perfil público de un usuario. Respeta su configuración de privacidad: si `shelvesPublic` es `false`, no incluye las estanterías; si `clubsPublic` es `false`, no incluye los clubes.

### `GET /api/my-requests`
Lista las solicitudes de unión a clubes enviadas por el usuario actual.

### `GET /api/admin-requests`
Lista las solicitudes pendientes en los clubes donde el usuario es administrador.

---

## 3. `PostApiController` — Publicaciones

**Prefijo de ruta:** `/api`

Gestiona el feed social de publicaciones con imágenes.

### `GET /api/posts`
Devuelve el **feed** del usuario: sus propias publicaciones y las de los usuarios a los que sigue (máx. 40 publicaciones ordenadas por fecha descendente).

**Respuesta:** array de posts con `id`, `imagePath`, `description`, `createdAt`, `likes`, `liked` (bool), `commentCount`, y datos del autor.

### `GET /api/users/{id}/posts`
Devuelve todas las publicaciones de un usuario específico.

### `POST /api/posts`
Crea una nueva publicación. Recibe `multipart/form-data` con:
- `image`: archivo de imagen (jpg, jpeg, png, gif, webp).
- `description`: texto opcional.

La imagen se guarda en `public/uploads/posts/` con nombre único generado con `uniqid()`.

### `DELETE /api/posts/{id}`
Elimina una publicación. Solo el autor o un `ROLE_ADMIN` pueden eliminarla. También borra el archivo de imagen del disco.

### `POST /api/posts/{id}/like`
Actúa como **toggle**: si el usuario ya dio like lo quita, si no lo dio lo añade. Devuelve el nuevo estado y el total de likes.

### `GET /api/posts/{id}/comments`
Lista todos los comentarios de una publicación.

### `POST /api/posts/{id}/comments`
Añade un comentario. Body JSON: `{ "content": "..." }`.

### `DELETE /api/posts/{id}/comments/{commentId}`
Elimina un comentario. Puede hacerlo el autor del comentario **o** el autor de la publicación.

---

## 4. `ShelfApiController` — Estanterías

**Prefijo de ruta:** `/api/shelves`

Gestiona las colecciones de libros personales del usuario.

### `GET /api/shelves`
Lista las estanterías del usuario (solo `id` y `name`).

### `GET /api/shelves/full`
Lista las estanterías con todos sus libros completos.

### `POST /api/shelves`
Crea una nueva estantería. Body JSON: `{ "name": "..." }`.

### `PATCH /api/shelves/{id}`
Renombra una estantería. Body JSON: `{ "name": "..." }`.

### `DELETE /api/shelves/{id}`
Elimina una estantería y todos sus libros (por `orphanRemoval` en la relación).

### `GET /api/shelves/{id}/books`
Lista los libros de una estantería con su estado y metadatos.

### `POST /api/shelves/{id}/books`
Añade un libro a la estantería. Body JSON: `{ "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }`.

**Lógica de importación automática:** Si el libro no existe en la base de datos local, el backend lo obtiene de la Google Books API y lo guarda antes de añadirlo.

**Estados válidos:** `want_to_read`, `reading`, `read`.

### `PATCH /api/shelves/{id}/books/{bookId}`
Actualiza el estado de lectura de un libro en la estantería.

### `POST /api/shelves/{id}/books/{bookId}/move`
Mueve un libro a otra estantería del mismo usuario. Body JSON: `{ "targetShelfId": 3 }`.

### `DELETE /api/shelves/{id}/books/{bookId}`
Quita un libro de una estantería.

---

## 5. `BookExternalApiController` — Búsqueda de libros

**Prefijo de ruta:** `/api/books`

Actúa como **proxy** hacia la Google Books API para que el frontend no exponga la API key. Cuando la API externa no está disponible, cae automáticamente sobre la base de datos local (ver [fallback](#fallback-a-base-de-datos-local)).

### `GET /api/books/search`
Búsqueda avanzada de libros.

**Parámetros de query:**

| Parámetro | Descripción |
|-----------|-------------|
| `q` | Texto libre de búsqueda |
| `title` | Buscar por título |
| `author` | Buscar por autor |
| `isbn` | Buscar por ISBN |
| `subject` | Buscar por categoría/género |
| `publisher` | Buscar por editorial |
| `startIndex` | Paginación (offset) |
| `maxResults` | Número de resultados (max 40) |
| `orderBy` | `relevance` o `newest` |
| `langRestrict` | Filtro por idioma (ej: `es`, `en`) |

---

## 6. `BookReviewApiController` — Reseñas

**Prefijo de ruta:** `/api/books/{externalId}/reviews`

### `GET /api/books/{externalId}/reviews`
Devuelve las reseñas y estadísticas de un libro (media de puntuaciones, distribución por estrellas, reseñas con texto).

### `POST /api/books/{externalId}/reviews`
Crea o actualiza la reseña del usuario para ese libro. Body JSON: `{ "rating": 4, "content": "Muy buen libro..." }`.

Si el usuario ya tenía una reseña, se actualiza (upsert).

---

## 7. `ReadingProgressApiController` — Progreso de lectura

**Prefijo de ruta:** `/api/reading-progress`

### `GET /api/reading-progress`
Lista todos los registros de progreso del usuario.

### `POST /api/reading-progress`
Empieza a rastrear un libro. Body JSON:
```json
{ "externalId": "zyTCAlFPjgYC", "mode": "pages", "totalPages": 350 }
```

### `PATCH /api/reading-progress/{id}`
Actualiza el progreso actual. Body JSON:
```json
{ "currentPage": 125 }
```
o para modo porcentaje:
```json
{ "percent": 35.5 }
```

### `DELETE /api/reading-progress/{id}`
Elimina el registro de progreso de un libro.

---

## 8. `ClubApiController` — Clubes de lectura

**Prefijo de ruta:** `/api/clubs`

Es el controlador más extenso. Gestiona la creación, administración, membresía y libro actual de los clubes.

### `GET /api/clubs`
Lista todos los clubes con nombre, descripción, visibilidad y número de miembros.

### `POST /api/clubs`
Crea un nuevo club. Body JSON:
```json
{ "name": "Club de Fantasía", "description": "...", "visibility": "public" }
```
El creador es añadido automáticamente como miembro con rol `admin`.

### `GET /api/clubs/{id}`
Devuelve los detalles de un club: datos básicos, libro actual y lista de miembros.

### `PATCH /api/clubs/{id}`
Actualiza nombre, descripción o visibilidad. Solo el `admin` del club puede hacerlo.

### `DELETE /api/clubs/{id}`
Elimina el club. Solo el propietario (`owner`) puede hacerlo.

### `POST /api/clubs/{id}/join`
Solicita unirse a un club.
- Si el club es **público**: se añade al usuario directamente como `member`.
- Si el club es **privado**: se crea una `ClubJoinRequest` en estado `pending` y se notifica a los admins.

### `DELETE /api/clubs/{id}/join`
Cancela una solicitud de unión pendiente o abandona el club si ya era miembro.

### `GET /api/clubs/{id}/members`
Lista todos los miembros con su rol y fecha de incorporación.

### `PATCH /api/clubs/{id}/members/{userId}/role`
Cambia el rol de un miembro entre `admin` y `member`. Solo los admins pueden hacerlo.

### `DELETE /api/clubs/{id}/members/{userId}`
Expulsa a un miembro del club. Solo los admins pueden hacerlo.

### `POST /api/clubs/{id}/requests/{reqId}/approve`
Aprueba una solicitud de unión pendiente. El usuario pasa a ser `member` del club.

### `POST /api/clubs/{id}/requests/{reqId}/reject`
Rechaza una solicitud de unión.

### `PATCH /api/clubs/{id}/currentBook`
Establece el libro que el club está leyendo actualmente. Body JSON:
```json
{ "externalId": "zyTCAlFPjgYC", "since": "2026-04-01", "until": "2026-04-30" }
```

---

## 9. `ClubChatApiController` — Hilos de debate

**Prefijo de ruta:** `/api/clubs/{clubId}/chats`

Gestiona los foros de discusión internos de cada club.

### `GET /api/clubs/{clubId}/chats`
Lista todos los hilos de debate del club.

### `POST /api/clubs/{clubId}/chats`
Crea un nuevo hilo. Solo los admins del club pueden crear hilos. Body JSON: `{ "title": "..." }`.

### `PATCH /api/clubs/{clubId}/chats/{chatId}`
Abre o cierra un hilo. Solo admins. Body JSON: `{ "isOpen": false }`.

### `GET /api/clubs/{clubId}/chats/{chatId}/messages`
Lista los mensajes de un hilo ordenados por fecha.

### `POST /api/clubs/{clubId}/chats/{chatId}/messages`
Publica un mensaje en el hilo. Cualquier miembro del club puede hacerlo. Solo en hilos abiertos.

Body JSON: `{ "content": "..." }`.

### `DELETE /api/clubs/{clubId}/chats/{chatId}/messages/{msgId}`
Elimina un mensaje. Solo el autor del mensaje o un admin del club.

---

## 10. `FollowApiController` — Seguimientos

**Prefijo de ruta:** `/api/users/{id}`

### `POST /api/users/{id}/follow`
Sigue a un usuario.
- Si su perfil es **público**: la relación se crea con estado `accepted`.
- Si su perfil es **privado**: se crea con estado `pending` y se envía una notificación de solicitud.

### `DELETE /api/users/{id}/follow`
Deja de seguir a un usuario o cancela una solicitud pendiente.

---

## 11. `NotificationApiController` — Notificaciones

**Prefijo de ruta:** `/api`

### `GET /api/notifications`
Devuelve las 30 notificaciones más recientes del usuario, con el número de no leídas.

### `GET /api/notifications/history`
Historial completo de notificaciones (100 últimas).

### `POST /api/notifications/read-all`
Marca todas las notificaciones como leídas.

### `POST /api/notifications/follow-requests/{followId}/accept`
Acepta una solicitud de seguimiento pendiente. Cambia el estado del `Follow` a `accepted` y envía notificación al solicitante.

### `POST /api/notifications/follow-requests/{followId}/reject`
Rechaza una solicitud de seguimiento eliminando el registro `Follow`.

### `POST /api/notifications/club-requests/{reqId}/approve`
Aprueba una solicitud de unión a club desde las notificaciones (mismo efecto que el endpoint del club).

### `POST /api/notifications/club-requests/{reqId}/reject`
Rechaza una solicitud de unión a club.

---

## 12. `AdminApiController` — Panel de administración

**Prefijo de ruta:** `/api/admin`

**Todos los endpoints requieren `ROLE_ADMIN`.** Si el usuario no tiene este rol, Symfony devuelve `403 Forbidden`.

### `GET /api/admin/stats`
Estadísticas globales de la plataforma.
```json
{ "users": 154, "clubs": 23, "posts": 891 }
```

### `GET /api/admin/users`
Lista todos los usuarios con email, roles y estado de verificación.

### `PATCH /api/admin/users/{id}/role`
Promueve o degrada un usuario a/de admin. No puede aplicarse al propio admin.

Body JSON: `{ "isAdmin": true }`.

### `DELETE /api/admin/users/{id}`
Elimina un usuario. No puede eliminarse a sí mismo.

### `GET /api/admin/clubs`
Lista todos los clubes con su propietario y número de miembros.

### `DELETE /api/admin/clubs/{id}`
Elimina cualquier club de la plataforma.

### `GET /api/admin/posts`
Lista los 100 posts más recientes con datos de su autor.

### `DELETE /api/admin/posts/{id}`
Elimina cualquier post y su imagen del disco.

---

## Resumen de endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/login` | — | Login con email/password |
| GET | `/api/auth/me` | — | Usuario actual |
| POST | `/api/auth/register` | — | Registro |
| POST | `/api/auth/logout` | ✓ | Cerrar sesión |
| GET | `/api/profile` | ✓ | Mi perfil completo |
| PUT | `/api/profile` | ✓ | Editar perfil |
| POST | `/api/profile/avatar` | ✓ | Subir avatar |
| PUT | `/api/profile/password` | ✓ | Cambiar contraseña |
| PUT | `/api/profile/privacy` | ✓ | Configurar privacidad |
| GET | `/api/users/search` | — | Buscar usuarios |
| GET | `/api/users/{id}` | — | Perfil público |
| GET | `/api/posts` | ✓ | Feed |
| POST | `/api/posts` | ✓ | Crear post |
| DELETE | `/api/posts/{id}` | ✓ | Eliminar post |
| POST | `/api/posts/{id}/like` | ✓ | Toggle like |
| GET | `/api/posts/{id}/comments` | — | Ver comentarios |
| POST | `/api/posts/{id}/comments` | ✓ | Añadir comentario |
| DELETE | `/api/posts/{id}/comments/{cid}` | ✓ | Borrar comentario |
| GET | `/api/shelves` | ✓ | Mis estanterías |
| POST | `/api/shelves` | ✓ | Crear estantería |
| POST | `/api/shelves/{id}/books` | ✓ | Añadir libro |
| DELETE | `/api/shelves/{id}/books/{bid}` | ✓ | Quitar libro |
| GET | `/api/books/search` | — | Buscar libros (Google) |
| GET | `/api/books/{eid}/reviews` | — | Ver reseñas |
| POST | `/api/books/{eid}/reviews` | ✓ | Crear/actualizar reseña |
| GET | `/api/reading-progress` | ✓ | Mi progreso |
| POST | `/api/reading-progress` | ✓ | Iniciar seguimiento |
| PATCH | `/api/reading-progress/{id}` | ✓ | Actualizar progreso |
| GET | `/api/clubs` | — | Lista de clubes |
| POST | `/api/clubs` | ✓ | Crear club |
| POST | `/api/clubs/{id}/join` | ✓ | Unirse/solicitar |
| GET | `/api/notifications` | ✓ | Notificaciones |
| POST | `/api/notifications/read-all` | ✓ | Marcar leídas |
| GET | `/api/admin/stats` | ADMIN | Estadísticas |
| GET | `/api/admin/users` | ADMIN | Gestionar usuarios |

---

# 05 — Seguridad y Autenticación

Este documento explica en detalle cómo funciona el sistema de autenticación y autorización del backend.

---

## 1. Visión general

El sistema de seguridad usa **sesiones PHP** (no tokens JWT). Cuando un usuario hace login, Symfony crea una sesión en el servidor y devuelve una cookie `PHPSESSID` al navegador. Las siguientes peticiones incluyen esa cookie automáticamente para que el servidor reconozca al usuario.

```
Cliente (React)                    Servidor (Symfony)
     │                                    │
     │  POST /api/login                   │
     │  { "email": "...", "password": "..." }
     │──────────────────────────────────► │
     │                                    │ Verifica credenciales
     │                                    │ Crea sesión PHP
     │  200 OK + Set-Cookie: PHPSESSID   │
     │◄────────────────────────────────── │
     │                                    │
     │  GET /api/profile                  │
     │  Cookie: PHPSESSID=abc123          │
     │──────────────────────────────────► │
     │                                    │ Carga usuario de sesión
     │  200 OK { ... datos usuario ... }  │
     │◄────────────────────────────────── │
```

---

## 2. Hashing de contraseñas

Las contraseñas **nunca** se almacenan en texto plano. Al registrarse o cambiar contraseña, se pasan por `UserPasswordHasherInterface`:

```php
$user->setPassword($hasher->hashPassword($user, $plainPassword));
```

La configuración `'auto'` en `security.yaml` selecciona el algoritmo más seguro disponible en el servidor:
- PHP ≥ 8.0: **bcrypt** o **argon2id**
- El hash incluye salt y coste integrados, por lo que es seguro aunque la BD se comprometa.

En tests, el coste se reduce al mínimo (`cost: 4`) para que las pruebas sean rápidas.

---

## 3. Proveedor de usuarios

```yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
            property: email
```

Cuando llega una petición con sesión activa, Symfony carga el usuario de la base de datos usando su email como identificador. Si el usuario no existe (fue eliminado), la sesión se invalida automáticamente.

---

## 4. Firewalls

### Firewall `dev`
```yaml
dev:
    pattern: ^/(_profiler|_wdt|assets|build)/
    security: false
```
Las herramientas de desarrollo (profiler, assets) no requieren autenticación. Sin este firewall, el profiler quedaría bloqueado en desarrollo.

### Firewall `main`
Es el firewall principal que protege toda la aplicación:

```yaml
main:
    lazy: true
    json_login:
        check_path: api_login         # POST /api/login
        username_path: email
        password_path: password
        success_handler: App\Security\JsonLoginSuccessHandler
        failure_handler: App\Security\JsonLoginFailureHandler
    stateless: false                  # Usa sesiones (no tokens)
```

`lazy: true` significa que el usuario no se carga de la base de datos hasta que sea necesario (optimización de rendimiento para rutas públicas).

---

## 5. `UserChecker` — verificación de estado de cuenta

**Archivo:** `src/Security/UserChecker.php`

Antes de que el login tenga éxito, Symfony llama a `UserChecker::checkPreAuth()`. Si el usuario está baneado, se lanza una excepción que devuelve un error de autenticación sin llegar a crear la sesión:

```php
public function checkPreAuth(UserInterface $user): void
{
    if (!$user instanceof User) return;

    if ($user->isBanned()) {
        throw new CustomUserMessageAccountStatusException(
            'Tu cuenta ha sido suspendida por un administrador.'
        );
    }
}
```

El `UserChecker` se registra en `security.yaml` bajo el firewall `main`:
```yaml
main:
    user_checker: App\Security\UserChecker
```

Un usuario baneado recibe `401` al intentar hacer login con el mensaje de suspensión. Su sesión actual también queda invalidada la próxima vez que Symfony recargue el usuario de BD.

---

## 6. El proceso de login paso a paso

Cuando el frontend envía `POST /api/login`:

```
1. El firewall `main` intercepta la petición (ruta: api_login)
2. Symfony lee los campos `email` y `password` del JSON
3. Llama al provider para buscar el usuario por email en BD
4. Si no existe → llama a JsonLoginFailureHandler → 401
5. UserChecker::checkPreAuth() — si isBanned=true → 401 con mensaje de suspensión
6. Verifica la contraseña con UserPasswordHasher
7. Si es incorrecta → llama a JsonLoginFailureHandler → 401
8. Si es correcta → crea la sesión PHP con los datos del usuario
9. Llama a JsonLoginSuccessHandler → 200 con datos del usuario
```

---

## 7. `JsonLoginSuccessHandler`

**Archivo:** `src/Security/JsonLoginSuccessHandler.php`

Se ejecuta cuando el login es exitoso. Devuelve los datos básicos del usuario en JSON:

```json
{
  "id": 1,
  "email": "usuario@ejemplo.com",
  "displayName": "MiNombre",
  "avatar": "avatar.jpg",
  "roles": ["ROLE_USER"]
}
```

El frontend React almacena esta respuesta en su estado/contexto para saber quién está logueado.

---

## 8. `JsonLoginFailureHandler`

**Archivo:** `src/Security/JsonLoginFailureHandler.php`

Se ejecuta cuando las credenciales son incorrectas (email no encontrado o contraseña incorrecta). Siempre devuelve el mismo mensaje genérico para no revelar si el email existe:

```json
{ "error": "Credenciales incorrectas" }
```

**Código HTTP:** `401 Unauthorized`.

---

## 9. Roles y autorización

El sistema usa dos roles:

| Rol | Quién lo tiene | Qué puede hacer |
|-----|---------------|-----------------|
| `ROLE_USER` | Todos los usuarios registrados (se añade automáticamente) | Acceder a endpoints protegidos normales |
| `ROLE_ADMIN` | Usuarios promovidos por otro admin | Acceder al panel de administración `/api/admin/*` |

### Verificación en controladores

La autorización se comprueba al principio de cada método con:

```php
$this->denyAccessUnlessGranted('ROLE_USER');
// o
$this->denyAccessUnlessGranted('ROLE_ADMIN');
```

Si el usuario no tiene el rol requerido, Symfony lanza una excepción que se convierte automáticamente en:
- `401 Unauthorized` — si no hay sesión activa.
- `403 Forbidden` — si hay sesión pero el rol no es suficiente.

### Autorización a nivel de recurso

Más allá de los roles, los controladores verifican que el recurso pertenezca al usuario antes de modificarlo:

```php
// Ejemplo: solo el propietario puede modificar su estantería
$shelf = $shelfRepo->find($id);
if (!$shelf || $shelf->getUser() !== $this->getUser()) {
    return $this->json(['error' => 'Estantería no encontrada'], 404);
}
```

Se devuelve `404` en lugar de `403` para no revelar que el recurso existe.

---

## 10. Serialización segura de contraseñas en sesión

En `User.php`:

```php
public function __serialize(): array
{
    $data = (array) $this;
    $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
    return $data;
}
```

En lugar de guardar el hash bcrypt completo en los datos de sesión, se guarda un hash CRC32c (rápido, no criptográfico) del mismo. Esto tiene dos ventajas:
1. Si los datos de sesión son interceptados, el hash bcrypt no queda expuesto.
2. Si la contraseña cambia, la sesión queda invalidada automáticamente (el CRC32c no coincidirá).

---

## 11. Verificación de email

La clase `src/Security/EmailVerifier.php` utiliza el bundle `SymfonyCastsVerifyEmailBundle` para generar y validar tokens de verificación de email firmados. Se envía un enlace al email del usuario al registrarse.

> **Nota actual:** En el flujo de registro via API (`/api/auth/register`), `isVerified` se establece directamente a `true` para simplificar el proceso durante el desarrollo. La verificación por email está disponible pero no es obligatoria en la versión actual.

---

## 12. CORS y cookies en desarrollo

En desarrollo, el frontend React corre en `localhost:5173` y el backend en `localhost:8000`. Para que las cookies de sesión funcionen en peticiones cross-origin, el frontend debe configurar:

```javascript
// En todas las peticiones fetch/axios:
credentials: 'include'  // o withCredentials: true
```

Y el servidor debe responder con las cabeceras `Access-Control-Allow-Origin` y `Access-Control-Allow-Credentials: true`. Esto se configura en el servidor web (Apache/Nginx) o mediante un bundle de CORS en Symfony.

---

# 06 — Repositorios

Los repositorios son clases que encapsulan las consultas a la base de datos. Todos se encuentran en `src/Repository/` y extienden `ServiceEntityRepository` de Doctrine, lo que les da acceso a métodos básicos como `find()`, `findBy()`, `findOneBy()` y `count()`. Los repositorios personalizados añaden métodos más complejos usando el **QueryBuilder** de Doctrine.

---

## Por qué usar repositorios

Centralizar las consultas en repositorios (en lugar de escribirlas en los controladores) tiene varias ventajas:
- El controlador no necesita saber cómo se construye la consulta SQL.
- Si cambia la lógica de la consulta, solo se modifica en un lugar.
- Es más fácil de testear de forma aislada.

---

## 1. `UserRepository`

**Implementa:** `PasswordUpgraderInterface`

### `search(string $q, int $limit = 20): User[]`
Busca usuarios por `displayName` de forma insensible a mayúsculas/minúsculas.

```sql
SELECT * FROM user
WHERE LOWER(display_name) LIKE LOWER('%q%')
ORDER BY display_name ASC
LIMIT 20
```

Utilizada por `GET /api/users/search?q=...`.

### `upgradePassword(User $user, string $newHashedPassword): void`
Implementación requerida por Symfony. Si el algoritmo de hashing mejora en una nueva versión de PHP, Symfony llama automáticamente a este método al hacer login para re-hashear la contraseña del usuario con el nuevo algoritmo más seguro. Actualiza el hash directamente en BD sin que el usuario lo note.

---

## 2. `PostRepository`

### `findByUser(User $user, int $limit = 30): Post[]`
Devuelve las publicaciones de un usuario ordenadas de más reciente a más antigua.

```sql
SELECT * FROM post WHERE user_id = :user ORDER BY created_at DESC LIMIT 30
```

### `findFeed(User $me, int $limit = 40): Post[]`
La consulta más importante del módulo social. Devuelve el **feed** del usuario: sus propios posts más los posts de los usuarios a quienes sigue con estado `accepted`.

```sql
SELECT p.*
FROM post p
LEFT JOIN follow f ON f.follower_id = :me AND f.following_id = p.user_id AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

La clave está en el `LEFT JOIN` con la tabla `follow`: si `f.id IS NOT NULL`, significa que `p.user_id` es alguien a quien `:me` sigue.

---

## 3. `FollowRepository`

### `findFollow(User $follower, User $following): ?Follow`
Busca si existe alguna relación de seguimiento (en cualquier estado) entre dos usuarios. Usado para mostrar el estado del botón "Seguir" en perfiles.

### `countFollowers(User $user): int`
Cuenta cuántos usuarios siguen a `$user` con estado `accepted`.

### `countFollowing(User $user): int`
Cuenta a cuántos usuarios sigue `$user` con estado `accepted`.

### `findFollowers(User $user): Follow[]`
Lista todos los seguidores aceptados de un usuario, ordenados por fecha descendente.

### `findFollowing(User $user): Follow[]`
Lista todos los usuarios que sigue `$user`, ordenados por fecha descendente.

### `findIncomingRequests(User $user): Follow[]`
Lista las solicitudes de seguimiento pendientes recibidas por un usuario con cuenta privada.

### `countIncomingRequests(User $user): int`
Cuenta las solicitudes de seguimiento pendientes entrantes.

---

## 4. `NotificationRepository`

### `findForUser(User $user, int $limit = 30): Notification[]`
Devuelve las notificaciones de las últimas **72 horas** del usuario, ordenadas de más reciente a más antigua. Usado por `GET /api/notifications`.

```sql
SELECT * FROM notification
WHERE recipient_id = :user AND created_at >= NOW() - INTERVAL 72 HOUR
ORDER BY created_at DESC
LIMIT 30
```

### `findAllForUser(User $user, int $limit = 100): Notification[]`
Historial completo sin límite temporal. Usado por `GET /api/notifications/history`.

### `countUnread(User $user): int`
Cuenta las notificaciones no leídas del usuario. Usado para mostrar el badge rojo en el icono de notificaciones.

```sql
SELECT COUNT(id) FROM notification WHERE recipient_id = :user AND is_read = 0
```

### `markAllRead(User $user): void`
Marca todas las notificaciones no leídas como leídas en una sola operación `UPDATE` (sin cargar cada entidad individualmente), lo que es mucho más eficiente.

```sql
UPDATE notification SET is_read = 1 WHERE recipient_id = :user AND is_read = 0
```

### `deleteByRefIdAndType(User $recipient, string $type, int $refId): void`
Elimina notificaciones específicas por tipo y ID de referencia. Se usa al procesar solicitudes de seguimiento o de club: una vez que el usuario acepta/rechaza, la notificación de solicitud se elimina.

---

## 5. `BookRepository`

Repositorio básico sin métodos personalizados. Usa `findOneBy(['externalId' => ..., 'externalSource' => 'google_books'])` para comprobar si un libro ya está importado.

---

## 6. `ShelfRepository`

Repositorio básico. Usa `findBy(['user' => $user])` para obtener las estanterías de un usuario.

---

## 7. `ShelfBookRepository`

Repositorio básico. Usa `findOneBy(['shelf' => $shelf, 'book' => $book])` para comprobar si un libro ya está en una estantería.

---

## 8. `ClubRepository`

Repositorio básico. Usa `findBy([], ['id' => 'DESC'])` para listar todos los clubes.

---

## 9. `ClubMemberRepository`

Repositorio básico. Usa `findBy(['club' => $club, 'role' => 'admin'])` para obtener los administradores de un club y `findBy(['user' => $user, 'role' => 'admin'])` para los clubs donde el usuario es admin.

---

## 10. `ClubJoinRequestRepository`

Repositorio básico. Consultas frecuentes:
- `findBy(['club' => $club, 'status' => 'pending'])` — solicitudes pendientes de un club.
- `findBy(['user' => $user])` — solicitudes enviadas por el usuario.

---

## 11. `ClubChatRepository` y `ClubChatMessageRepository`

Repositorios básicos. Las consultas de mensajes aprovechan el índice compuesto `(chat_id, created_at)` definido en la entidad para recuperar mensajes de un hilo de forma eficiente.

---

## 12. `PostLikeRepository`

### Métodos destacados:
- `findByPostAndUser(Post $post, User $user): ?PostLike` — comprueba si el usuario ya dio like.
- `countByPost(Post $post): int` — cuenta el total de likes de una publicación.

---

## 13. `PostCommentRepository`

### `findByPost(Post $post): PostComment[]`
Devuelve los comentarios de una publicación ordenados por fecha ascendente (los más antiguos primero).

---

## 14. `ReadingProgressRepository`

Repositorio básico. Usa `findBy(['user' => $user])` para listar el progreso del usuario.

---

## 15. `BookReviewRepository`

Repositorio básico. Usa `findBy(['book' => $book])` para obtener todas las reseñas de un libro y calcular estadísticas de puntuación.

---

## Patrón QueryBuilder

Los repositorios que tienen consultas complejas usan el `QueryBuilder` de Doctrine:

```php
return $this->createQueryBuilder('alias')
    ->where('alias.campo = :valor')
    ->setParameter('valor', $miValor)
    ->orderBy('alias.fecha', 'DESC')
    ->setMaxResults(40)
    ->getQuery()
    ->getResult();
```

- `createQueryBuilder('alias')`: crea el builder con el alias para la entidad principal.
- `->where()` / `->andWhere()`: condiciones de filtrado.
- `->setParameter()`: previene SQL injection al no interpolar valores directamente.
- `->orderBy()`: ordenación de resultados.
- `->setMaxResults()`: límite de resultados (equivalente a `LIMIT`).
- `->getQuery()->getResult()`: ejecuta y devuelve array de entidades.
- `->getQuery()->getSingleScalarResult()`: para consultas `COUNT` que devuelven un número.
- `->getQuery()->execute()`: para `UPDATE` y `DELETE` masivos.

---

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

### `Version20260429000000` — Campo de ban en usuario
**Fecha:** 29 de abril de 2026

Añade la columna `is_banned` a la tabla `user` para soportar la suspensión de cuentas por parte del administrador:

```sql
ALTER TABLE "user" ADD is_banned BOOLEAN NOT NULL DEFAULT FALSE
```

Un usuario con `is_banned = true` no puede iniciar sesión. El `UserChecker` de Symfony verifica este campo antes de crear la sesión (ver [05-seguridad.md](05-seguridad.md)).

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

---

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

---

# 09 — Integración con Google Books API

El backend actúa como **intermediario** entre el frontend React y la Google Books API. Esto tiene dos ventajas: la API key nunca se expone al navegador, y los libros se pueden cachear localmente en la base de datos.

---

## 1. Arquitectura de la integración

```
React                 Backend Symfony              Google Books API
  │                        │                              │
  │  GET /api/books/search  │                              │
  │  ?q=potter             │                              │
  │───────────────────────►│                              │
  │                        │  GET /books/v1/volumes        │
  │                        │  ?q=potter&key=API_KEY        │
  │                        │─────────────────────────────►│
  │                        │  { items: [...] }             │
  │                        │◄─────────────────────────────│
  │                        │ Filtra, ordena y normaliza    │
  │  200 { results: [...] }│                              │
  │◄───────────────────────│                              │
```

---

## 2. Controladores involucrados

| Controlador | Función |
|-------------|---------|
| `BookExternalApiController` | Búsqueda y detalle de libros directamente desde Google |
| `ShelfApiController` | Importa libros de Google al añadirlos a una estantería |
| `BookReviewApiController` | Importa libros de Google al crear una reseña |
| `ReadingProgressApiController` | Importa libros de Google al registrar el progreso |

---

## 3. `GET /api/books/search` — Búsqueda avanzada

### Parámetros de consulta

| Parámetro | Tipo | Descripción | Default |
|-----------|------|-------------|---------|
| `q` | string | Texto libre (busca en todo) | — |
| `title` | string | Filtro por título (`intitle:`) | — |
| `author` | string | Filtro por autor (`inauthor:`) | — |
| `isbn` | string | Filtro por ISBN (`isbn:`) | — |
| `subject` | string | Filtro por categoría (`subject:`) | — |
| `publisher` | string | Filtro por editorial (`inpublisher:`) | — |
| `page` | int | Número de página (paginación) | `1` |
| `limit` | int | Resultados por página (máx. 40) | `20` |
| `orderBy` | string | `relevance` o `newest` | `relevance` |
| `lang` | string | Filtro por idioma (`es`, `en`, etc.) | — |
| `printType` | string | `books`, `magazines` o `all` | `books` |
| `filter` | string | `free-ebooks`, `paid-ebooks`, `ebooks`, `partial`, `full` | — |

Al menos uno de los filtros de texto (`q`, `title`, `author`, `isbn`, `subject`, `publisher`) es obligatorio.

### Construcción de la consulta a Google

Los filtros se combinan en un único parámetro `q` usando los operadores de búsqueda de Google Books:

```
q=potter intitle:harry inauthor:rowling subject:fantasy
```

### Algoritmo de re-ranking

Google Books devuelve hasta 40 resultados, pero la calidad varía mucho. El backend aplica un algoritmo propio antes de devolver los resultados:

```
1. Filtrar: descartar libros sin imagen de portada
   (libros sin portada son generalmente entradas incompletas o no publicados)

2. Separar en dos grupos:
   - Con puntuaciones (ratingsCount > 0)
   - Sin puntuaciones

3. Ordenar los libros CON puntuaciones por:
   score = ratingsCount × averageRating (descendente)
   → Un libro con 1000 valoraciones y 4.5★ aparece antes que uno con 10 y 5★

4. Ordenar los libros SIN puntuaciones por número de páginas (descendente)
   → Un libro de 400 páginas es más probable que sea real que uno de 5

5. Combinar: primero los valorados, luego los no valorados

6. Recortar al límite solicitado (máx. 40)
```

Este algoritmo mejora la experiencia porque los resultados más relevantes y populares aparecen primero.

---

## 4. `GET /api/books/{externalId}` — Detalle de un libro

Devuelve los metadatos completos de un volumen de Google Books directamente, sin almacenar en BD.

El `externalId` es el identificador de volumen de Google Books (ej: `zyTCAlFPjgYC`, `OL7353617M`).

---

## 5. Importación automática de libros (lazy import)

Cuando el usuario quiere **añadir un libro a una estantería**, **crear una reseña** o **registrar progreso**, el backend necesita tener el libro en su base de datos local. El flujo es:

```php
// 1. Buscar en BD local
$book = $bookRepo->findOneBy([
    'externalId' => $externalId,
    'externalSource' => 'google_books'
]);

// 2. Si no existe, importarlo
if (!$book) {
    $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
    if ($book === null) {
        return $this->json(['error' => 'No se encontró el libro'], 404);
    }
}
```

### Datos que se importan

```php
$book = new Book();
$book->setExternalSource('google_books');
$book->setExternalId($item['id']);
$book->setTitle($vi['title'] ?? 'Sin título');
$book->setAuthors($vi['authors'] ?? []);
$book->setPublisher($vi['publisher'] ?? null);
$book->setPublishedDate($vi['publishedDate'] ?? null);
$book->setLanguage($vi['language'] ?? null);
$book->setDescription($vi['description'] ?? null);
$book->setPageCount((int) $vi['pageCount']);
$book->setCategories($vi['categories'] ?? []);
$book->setCoverUrl($links['thumbnail'] ?? $links['smallThumbnail'] ?? null);
$book->setIsbn10($isbn10);
$book->setIsbn13($isbn13);
```

Una vez importado, el libro queda en la BD y las siguientes referencias no requieren llamada a la API externa.

---

## 6. Normalización de ISBNs

Los ISBNs se extraen del campo `industryIdentifiers` de la respuesta de Google Books:

```json
"industryIdentifiers": [
  { "type": "ISBN_10", "identifier": "8408172174" },
  { "type": "ISBN_13", "identifier": "9788408172179" }
]
```

El backend itera el array y extrae `isbn10` e `isbn13` por separado. Si Google devuelve solo uno de los dos, el otro queda en `null`.

En las búsquedas por ISBN, se limpia la entrada del usuario eliminando todo lo que no sea dígitos o la letra `X`:
```php
$isbn = preg_replace('/[^0-9Xx]/', '', $request->query->get('isbn', ''));
```

---

## 7. Manejo de errores de la API externa

El controlador gestiona tres tipos de fallo en el endpoint de búsqueda:

| Situación | Respuesta al cliente |
|-----------|---------------------|
| Google Books devuelve 4xx/5xx | `200 OK` con resultados locales y `"fallback": true` |
| Timeout o error de red | `200 OK` con resultados locales y `"fallback": true` |
| `externalId` no existe (404 de Google) en detalle | `404 Not Found` |

En los contextos de importación (añadir libro, crear reseña, etc.), si la importación falla, la operación completa se aborta y se informa al usuario.

### Fallback a base de datos local

Cuando Google Books no está disponible en el endpoint `/api/books/search`, en lugar de devolver un error `502`, el backend busca en la tabla `book` de la base de datos local usando el mismo término de búsqueda (LIKE sobre `title` y `authors`). La respuesta incluye el campo `"fallback": true` para que el frontend pueda informar al usuario:

```json
{
  "page": 1,
  "limit": 12,
  "totalItems": 3,
  "results": [ ... ],
  "fallback": true
}
```

El frontend muestra un aviso amarillo: *"No se pueden cargar más libros en este momento. Vuelve a intentarlo más tarde."* acompañado de los resultados guardados localmente si los hay. Los libros locales son aquellos que cualquier usuario importó previamente al añadirlos a una estantería, crear una reseña o registrar progreso de lectura.

---

## 8. Configuración de la API key

La API key se configura en el archivo `.env`:

```
GOOGLE_BOOKS_API_KEY=AIzaSy...
```

Y se lee en el controlador con:
```php
$apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;
```

Si no hay API key configurada, las peticiones se hacen sin autenticación. Google Books permite un número limitado de peticiones anónimas, pero con la API key el límite sube considerablemente. En producción, la API key es **obligatoria**.

---

## 9. Estructura de respuesta normalizada

Tanto la búsqueda como el detalle devuelven los libros con la misma estructura:

```json
{
  "externalId":    "zyTCAlFPjgYC",
  "title":         "Harry Potter y la piedra filosofal",
  "subtitle":      null,
  "authors":       ["J.K. Rowling"],
  "publisher":     "Salamandra",
  "publishedDate": "1999",
  "categories":    ["Juvenile Fiction"],
  "language":      "es",
  "description":   "Harry Potter es un joven...",
  "pageCount":     309,
  "averageRating": 4.5,
  "ratingsCount":  15820,
  "thumbnail":     "https://books.google.com/books/content?id=...",
  "previewLink":   "https://books.google.es/books?id=...",
  "infoLink":      "https://play.google.com/store/books/...",
  "isbn10":        "8478884459",
  "isbn13":        "9788478884452"
}
```

La búsqueda además incluye paginación:
```json
{
  "page": 1,
  "limit": 20,
  "totalItems": 1243,
  "results": [ {...}, {...}, ... ]
}
```

---

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

---

# 11 — Gestión de imágenes y archivos

El backend gestiona dos tipos de archivos subidos por los usuarios: imágenes de publicaciones (posts) y avatares de perfil. Ambos se almacenan en el sistema de archivos local del servidor.

---

## 1. Estructura de directorios

```
backend/public/
├── uploads/
│   ├── posts/      ← Imágenes de publicaciones
│   └── avatars/    ← Fotos de perfil de usuarios
└── app/            ← Frontend React compilado (no gestión de usuario)
```

Estos directorios están dentro de `public/`, por lo que son accesibles directamente desde el navegador:
- `http://localhost:8000/uploads/posts/post_abc123.jpg`
- `http://localhost:8000/uploads/avatars/6716a3b.jpg`

---

## 2. Subida de imágenes de publicaciones

**Endpoint:** `POST /api/posts`  
**Tipo de petición:** `multipart/form-data`

### Validaciones

```php
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext     = strtolower($file->guessExtension() ?? '');

if (!in_array($ext, $allowed, true)) {
    return $this->json(['error' => 'Formato de imagen no permitido'], 400);
}
```

El método `guessExtension()` detecta el tipo real del archivo por su contenido (no solo por la extensión del nombre), lo que previene que un usuario suba un archivo `.exe` renombrado como `.jpg`.

### Generación de nombre único

```php
$filename = uniqid('post_', true) . '.' . $ext;
```

`uniqid('post_', true)` genera un nombre como `post_6716a3b4e5f12.3456789012`. El segundo argumento `true` añade entropía adicional basada en microsegundos, minimizando colisiones.

### Guardado

```php
$uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
$file->move($uploadDir, $filename);
```

Solo el nombre del archivo se guarda en la entidad `Post` (campo `imagePath`), no la ruta completa. El frontend construye la URL completa prefijando `/uploads/posts/`.

---

## 3. Subida de avatares de perfil

**Endpoint:** `POST /api/profile/avatar`  
**Tipo de petición:** `multipart/form-data`  
**Campo:** `avatar`

```php
$filename = uniqid() . '.' . $file->guessExtension();
$file->move(
    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
    $filename
);
$user->setAvatar($filename);
$this->em->flush();
```

A diferencia de los posts, los avatares no tienen validación de extensión explícita en el código actual. El campo `avatar` de `User` almacena solo el nombre del archivo.

---

## 4. Eliminación de imágenes al borrar un post

Cuando se elimina una publicación, el archivo físico también se borra del disco:

```php
$imgPath = $this->getParameter('kernel.project_dir')
         . '/public/uploads/posts/'
         . $post->getImagePath();

if (file_exists($imgPath)) {
    @unlink($imgPath);
}

$this->em->remove($post);
$this->em->flush();
```

El operador `@` antes de `unlink()` suprime posibles errores si el archivo ya no existe (por ejemplo, si fue eliminado manualmente del servidor). La entidad se borra de BD siempre, independientemente de si el archivo existía.

> **Importante:** Esta misma lógica se ejecuta también cuando un **admin** elimina un post desde el panel de administración (`AdminApiController`).

---

## 5. Acceso a las imágenes desde el frontend

El frontend recibe el nombre del archivo en las respuestas JSON:

```json
{
  "id": 42,
  "imagePath": "post_6716a3b4e5f12.jpg",
  "user": {
    "avatar": "6716c001b2345.jpg"
  }
}
```

Y construye la URL completa para mostrar la imagen:

```javascript
// Post
`/uploads/posts/${post.imagePath}`

// Avatar
`/uploads/avatars/${user.avatar}`
```

En desarrollo, Vite tiene configurado un proxy que redirige `/uploads/` al servidor Symfony en `localhost:8000`, por lo que las imágenes se sirven correctamente aunque el frontend corra en `localhost:5173`.

---

## 6. Consideraciones de producción

En el entorno actual, las imágenes se almacenan en el disco del servidor. Esto es suficiente para un TFG, pero en una aplicación real con múltiples servidores o despliegue en la nube habría que considerar:

- **Almacenamiento en la nube:** Amazon S3, Google Cloud Storage o Azure Blob Storage.
- **CDN:** Para servir imágenes más rápido desde ubicaciones cercanas al usuario.
- **Límite de tamaño:** Configurar `upload_max_filesize` y `post_max_size` en `php.ini`.
- **Procesamiento:** Redimensionar y comprimir imágenes al subir (con librerías como Intervention Image o Imagine).

---

## 7. Configuración de PHP para subidas

El tamaño máximo de archivo permitido se controla en la configuración de PHP (`php.ini`), no en Symfony. Los valores clave son:

```ini
upload_max_filesize = 10M   ; tamaño máximo de un archivo individual
post_max_size = 12M          ; tamaño máximo del body completo (debe ser > upload_max_filesize)
max_file_uploads = 20        ; número máximo de archivos por petición
```

Para cambiar estos valores en el entorno de desarrollo con Symfony CLI:
```bash
# En symfony.lock o en un archivo .php.ini del proyecto
upload_max_filesize=10M
post_max_size=12M
```

---

# 12 — Guía de instalación y despliegue

Este documento explica cómo poner en marcha el backend en un entorno local de desarrollo y cómo prepararlo para producción.

---

## 1. Requisitos previos

| Software | Versión mínima | Comprobación |
|----------|---------------|--------------|
| PHP | 8.2 | `php -v` |
| Composer | 2.x | `composer --version` |
| MySQL o PostgreSQL | 8.0 / 14 | `mysql --version` |
| Symfony CLI (recomendado) | 5.x | `symfony version` |
| Node.js (para el frontend) | 18.x | `node -v` |

---

## 2. Instalación en desarrollo

### Paso 1: Instalar dependencias PHP

```bash
cd backend
composer install
```

Esto instala todas las librerías listadas en `composer.json` dentro de la carpeta `vendor/`.

### Paso 2: Configurar variables de entorno

Copiar el archivo de entorno y editarlo:

```bash
cp .env .env.local
```

Editar `.env.local` con los valores reales (este archivo no se versiona):

```
APP_ENV=dev
APP_SECRET=una-clave-secreta-aleatoria-de-32-caracteres

DATABASE_URL="mysql://usuario:contraseña@127.0.0.1:3306/tfgdaw?serverVersion=8.0&charset=utf8mb4"
# o para PostgreSQL:
# DATABASE_URL="postgresql://usuario:contraseña@127.0.0.1:5432/tfgdaw?serverVersion=14"

GOOGLE_BOOKS_API_KEY=AIzaSy...

MAILER_DSN=null://null
```

> **Nota:** `MAILER_DSN=null://null` descarta todos los emails. Útil en desarrollo para no necesitar un servidor de correo.

### Paso 3: Crear la base de datos

```bash
php bin/console doctrine:database:create
```

### Paso 4: Ejecutar las migraciones

```bash
php bin/console doctrine:migrations:migrate
```

Esto crea todas las tablas aplicando las 11 migraciones en orden.

### Paso 5: Arrancar el servidor de desarrollo

```bash
symfony server:start
# o sin Symfony CLI:
php -S localhost:8000 -t public/
```

El backend quedará disponible en `http://localhost:8000`.

### Paso 6: Arrancar el frontend (en otra terminal)

```bash
cd ../frontend
npm install
npm run dev
```

El frontend correrá en `http://localhost:5173` con hot-reload y proxy a la API en `:8000`.

---

## 3. Comandos de desarrollo útiles

### Consola de Symfony

```bash
# Ver todas las rutas registradas
php bin/console debug:router

# Ver todos los servicios registrados
php bin/console debug:container

# Listar migraciones y su estado
php bin/console doctrine:migrations:status

# Generar una nueva migración (tras cambiar entidades)
php bin/console doctrine:migrations:diff

# Aplicar migraciones pendientes
php bin/console doctrine:migrations:migrate

# Validar el mapeo de entidades
php bin/console doctrine:schema:validate

# Limpiar la caché (útil si algo no funciona bien)
php bin/console cache:clear
```

### Crear el usuario administrador inicial

El proyecto incluye el comando `app:create-admin` en `src/Command/CreateAdminCommand.php`. Crea el usuario `admin` con contraseña `admin` y rol `ROLE_ADMIN`, o le asigna el rol si ya existe:

```bash
php bin/console app:create-admin
```

**Credenciales creadas:** email `admin` / contraseña `admin`.

> En producción con Railway, este comando se ejecuta automáticamente en cada despliegue desde `docker/entrypoint.sh`, tras las migraciones. No es necesario ejecutarlo manualmente.

Si se necesita promover a otro usuario existente desde SQL:
```sql
UPDATE "user" SET roles = '["ROLE_ADMIN"]' WHERE email = 'usuario@ejemplo.com';
```

Desde el panel admin (`/admin` → pestaña Usuarios) también se puede hacer admin a cualquier usuario registrado con el botón "Hacer admin".

---

## 4. Estructura del archivo `.env`

| Variable | Ejemplo | Descripción |
|----------|---------|-------------|
| `APP_ENV` | `dev` | Entorno: `dev`, `prod` o `test` |
| `APP_SECRET` | `abc123...` | Clave secreta para CSRF, cookies firmadas. Debe ser única y aleatoria |
| `DATABASE_URL` | `mysql://user:pass@host/db` | Cadena de conexión a la base de datos |
| `GOOGLE_BOOKS_API_KEY` | `AIzaSy...` | Clave de la Google Books API |
| `MAILER_DSN` | `smtp://user:pass@smtp.server:587` | Configuración del servidor de correo |

---

## 5. Preparar para producción

### 5.1 Variables de entorno de producción

```
APP_ENV=prod
APP_SECRET=clave-muy-segura-y-unica
DATABASE_URL="mysql://..."
GOOGLE_BOOKS_API_KEY=...
```

### 5.2 Instalar dependencias sin paquetes de desarrollo

```bash
composer install --no-dev --optimize-autoloader
```

`--no-dev` omite bundles como `MakerBundle` y `WebProfilerBundle`.  
`--optimize-autoloader` genera un classmap optimizado para mayor velocidad.

### 5.3 Compilar el frontend

```bash
cd frontend
npm install
npm run build
```

Esto genera los archivos estáticos en `backend/public/app/`. El `SpaController` los servirá automáticamente.

### 5.4 Limpiar y precalentar la caché

```bash
cd backend
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup
```

### 5.5 Ejecutar migraciones en producción

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

El flag `--no-interaction` evita la confirmación manual (útil en scripts de CI/CD).

---

## 6. Despliegue con Docker

El proyecto incluye configuración Docker lista para usar.

### Arrancar con Docker Compose

```bash
# Producción
docker compose up -d

# Con overrides de desarrollo
docker compose -f compose.yaml -f compose.override.yaml up -d
```

### Estructura de los archivos Docker

| Archivo | Propósito |
|---------|-----------|
| `Dockerfile` | Imagen PHP 8.2-FPM + Nginx + Supervisor (Railway-compatible) |
| `docker/entrypoint.sh` | Script de arranque: migraciones → crear admin → warmup caché → supervisor |
| `docker/nginx-railway.conf` | Configuración Nginx para Railway (puerto 8080, SPA fallback) |
| `docker/supervisord.conf` | Supervisor: gestiona php-fpm y nginx como procesos paralelos |
| `compose.yaml` | Servicios para producción local: PHP-FPM + Nginx + base de datos |
| `compose.override.yaml` | Sobreescrituras para desarrollo: mapeo de puertos, volúmenes de código fuente |

### Notas del `Dockerfile` (producción / Railway)

El `Dockerfile` usa un patrón de dos fases para que `symfony/runtime` se genere correctamente:

```dockerfile
# Fase 1: instala paquetes sin scripts (capa cacheada)
COPY composer.json composer.lock symfony.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-scripts --no-interaction --prefer-dist

# Fase 2: copia el código y regenera el autoloader con plugins activos
COPY . .
RUN COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=prod composer dump-autoload --optimize --no-dev
```

`COMPOSER_ALLOW_SUPERUSER=1` es necesario porque el contenedor corre como `root`, y sin este flag Composer desactiva los plugins (incluyendo `symfony/runtime`), lo que impide generar `vendor/autoload_runtime.php`.

El `entrypoint.sh` ejecuta en cada arranque:
```sh
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:create-admin || true
php bin/console cache:warmup --no-debug --env=prod
exec /usr/bin/supervisord -c /etc/supervisord.conf
```

---

## 7. Configuración del servidor web (producción sin Docker)

### Apache (`VirtualHost`)

```apache
<VirtualHost *:80>
    ServerName tfgdaw.ejemplo.com
    DocumentRoot /var/www/tfgdaw/backend/public

    <Directory /var/www/tfgdaw/backend/public>
        AllowOverride All
        Require all granted

        FallbackResource /index.php
    </Directory>

    # Servir imágenes directamente
    <Directory /var/www/tfgdaw/backend/public/uploads>
        Options -Indexes
        Require all granted
    </Directory>
</VirtualHost>
```

`FallbackResource /index.php` es el equivalente Apache del `try_files` de Nginx: todas las rutas que no correspondan a un archivo real se redirigen al front controller de Symfony.

### Nginx

```nginx
server {
    listen 80;
    server_name tfgdaw.ejemplo.com;
    root /var/www/tfgdaw/backend/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

---

## 8. Acceso cifrado HTTPS

Para cumplir con el criterio de comunicación segura, el servidor de producción debe servir la aplicación mediante HTTPS. Se recomiendan dos opciones:

### Opción A: Let's Encrypt con Certbot (servidor con dominio público)

```bash
# Instalar certbot y el plugin de Nginx (o Apache)
sudo apt install certbot python3-certbot-nginx

# Obtener y configurar el certificado automáticamente
sudo certbot --nginx -d tfgdaw.ejemplo.com

# Certbot modifica el VirtualHost de Nginx añadiendo:
# listen 443 ssl;
# ssl_certificate /etc/letsencrypt/live/tfgdaw.ejemplo.com/fullchain.pem;
# ssl_certificate_key /etc/letsencrypt/live/tfgdaw.ejemplo.com/privkey.pem;
# Y añade una redirección 301 de HTTP a HTTPS automáticamente.

# Renovación automática (certbot crea un cron o timer de systemd):
sudo certbot renew --dry-run
```

### Opción B: Certificado autofirmado (entorno local / desarrollo)

```bash
# Generar clave privada y certificado autofirmado con OpenSSL
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/tfgdaw.key \
  -out /etc/ssl/certs/tfgdaw.crt \
  -subj "/CN=localhost"
```

Configuración Nginx con HTTPS y redirección automática de HTTP:

```nginx
# Bloque HTTP: redirige todo a HTTPS
server {
    listen 80;
    server_name tfgdaw.ejemplo.com;
    return 301 https://$host$request_uri;
}

# Bloque HTTPS
server {
    listen 443 ssl;
    server_name tfgdaw.ejemplo.com;
    root /var/www/tfgdaw/backend/public;

    ssl_certificate     /etc/ssl/certs/tfgdaw.crt;
    ssl_certificate_key /etc/ssl/private/tfgdaw.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

### Restricción de acceso a usuarios autenticados

El acceso a determinadas secciones ya está restringido a nivel de API mediante el sistema de roles de Symfony (ver sección 5 — Seguridad). A nivel de servidor web, se puede añadir protección adicional con autenticación HTTP básica para el entorno de staging:

```nginx
location /api/admin {
    auth_basic "Area restringida";
    auth_basic_user_file /etc/nginx/.htpasswd;
    # luego continúa con la configuración PHP normal
    try_files $uri /index.php$is_args$args;
}
```

> En producción real, la restricción de acceso se gestiona exclusivamente mediante los roles de Symfony (`ROLE_ADMIN`), no mediante autenticación HTTP básica.

---

## 9. Ejecutar los tests

```bash
php bin/phpunit
```

Para los tests, se usa una base de datos separada (`tfgdaw_test`) configurada en `.env.test`. El coste del hashing de contraseñas se reduce al mínimo (`cost: 4`) para que los tests sean rápidos.

---

## 9. Solución de problemas comunes

| Problema | Causa probable | Solución |
|----------|---------------|----------|
| `An exception occurred in driver: ...` | La base de datos no está arrancada o el `DATABASE_URL` es incorrecto | Verificar que MySQL/PostgreSQL está activo y el DSN es correcto |
| `The "GOOGLE_BOOKS_API_KEY" variable is not defined` | Falta la variable en `.env.local` | Añadirla al archivo `.env.local` |
| `Expired CSRF token` | Las cookies de sesión no se envían desde el frontend | Añadir `credentials: 'include'` a las peticiones fetch |
| `401` en endpoints protegidos | Sin sesión activa | Hacer login primero con `POST /api/login` |
| `No such file or directory: public/uploads/posts` | El directorio no existe | Crearlo manualmente: `mkdir -p public/uploads/posts public/uploads/avatars` |
| `Class not found` en producción | Caché no regenerada tras despliegue | Ejecutar `php bin/console cache:clear` |

---

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

---

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

---

# 15 — Módulo de Libros

El módulo de libros engloba todo lo relacionado con la gestión de lecturas personales: búsqueda de libros, organización en estanterías, seguimiento del progreso de lectura y reseñas.

---

## 1. Visión general

```
Google Books API
       │
       │  búsqueda / detalle
       ▼
BookExternalApiController ──► [resultados temporales, no se guardan]
       │
       │  al añadir a estantería / reseña / progreso
       ▼
  Book (entidad) ──────────────────────────────────────────────┐
       │                                                        │
       ├── ShelfBook (libro en estantería con estado)           │
       │       └── Shelf (estantería del usuario)              │
       │                                                        │
       ├── ReadingProgress (progreso por páginas o %)          │
       │                                                        │
       └── BookReview (reseña y puntuación 1-5)                │
                                                               │
                                              Club.currentBook ┘
```

Un libro (`Book`) se crea en la BD **la primera vez** que alguien lo referencia (estantería, reseña, progreso o libro del mes de un club). A partir de ese momento, todos los usuarios comparten la misma entidad `Book`.

---

## 2. Búsqueda de libros

### `GET /api/books/search`

Proxy hacia Google Books. Los resultados **no se guardan** en la BD; son temporales y solo se muestran al usuario para que elija qué libro quiere usar.

Ver [09-google-books.md](09-google-books.md) para la documentación completa de parámetros y el algoritmo de re-ranking.

### `GET /api/books/{externalId}`

Detalle completo de un libro desde Google Books. Tampoco se guarda en BD.

---

## 3. Estanterías

### 3.1 Qué es una estantería

Una estantería (`Shelf`) es una colección nombrada de libros que pertenece a un usuario. Cada usuario puede tener tantas estanterías como quiera con nombres personalizados: "Leídos", "Por leer", "Favoritos", etc.

### 3.2 Estados de un libro en la estantería

Cuando se añade un libro, se asigna un estado de lectura (`ShelfBook.status`):

| Estado | Descripción |
|--------|-------------|
| `want_to_read` | Quiero leerlo (lista de deseos) |
| `reading` | Lo estoy leyendo ahora |
| `read` | Ya lo he leído |

El estado se puede cambiar con `PATCH /api/shelves/{id}/books/{bookId}`.

### 3.3 Flujo completo de una estantería

```
1. Crear estantería
   POST /api/shelves
   { "name": "Ciencia Ficción" }
   → { "id": 5, "name": "Ciencia Ficción" }

2. Añadir libro (importa de Google si no existe en BD)
   POST /api/shelves/5/books
   { "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }
   → { "id": 23, "status": "want_to_read", "book": {...} }

3. Cambiar estado cuando lo empieces a leer
   PATCH /api/shelves/5/books/23
   { "status": "reading" }

4. Marcar como leído
   PATCH /api/shelves/5/books/23
   { "status": "read" }

5. Mover a otra estantería
   POST /api/shelves/5/books/23/move
   { "targetShelfId": 8 }

6. Quitar de la estantería
   DELETE /api/shelves/5/books/23
```

### 3.4 Listado completo

`GET /api/shelves/full` devuelve todas las estanterías con sus libros en una sola petición, evitando hacer N peticiones adicionales:

```json
[
  {
    "id": 5,
    "name": "Ciencia Ficción",
    "books": [
      {
        "id": 23,
        "status": "read",
        "orderIndex": 0,
        "addedAt": "2026-03-15T12:00:00+00:00",
        "book": {
          "id": 7,
          "externalId": "zyTCAlFPjgYC",
          "title": "Dune",
          "authors": ["Frank Herbert"],
          "coverUrl": "https://...",
          "pageCount": 896,
          ...
        }
      }
    ]
  }
]
```

### 3.5 Importación automática al añadir un libro

Cuando se hace `POST /api/shelves/{id}/books`, el backend comprueba si el libro ya existe en la BD por su `externalId`. Si no existe, lo importa de Google Books en ese mismo momento. Este proceso es transparente para el usuario.

```
Usuario selecciona libro en buscador
        │
        │  externalId: "zyTCAlFPjgYC"
        ▼
¿Existe en BD?
    ├── Sí → usa el existente
    └── No → GET https://books.googleapis.com/volumes/zyTCAlFPjgYC
              → Crea Book en BD
              → Añade a estantería
```

---

## 4. Progreso de lectura

### 4.1 Dos modos de seguimiento

**Modo páginas (`pages`):**
```json
{ "externalId": "zyTC...", "mode": "pages", "totalPages": 896 }
```
El progreso se actualiza con `currentPage`:
```json
{ "currentPage": 250 }
→ computed: 27.9%
```

**Modo porcentaje (`percent`):**
```json
{ "externalId": "zyTC...", "mode": "percent" }
```
El progreso se actualiza directamente con el porcentaje:
```json
{ "percent": 30 }
→ computed: 30%
```

El campo `computed` en la respuesta siempre calcula el porcentaje independientemente del modo:
- En modo `pages`: `computed = (currentPage / totalPages) * 100`
- En modo `percent`: `computed = percent`

### 4.2 Respuesta del progreso

```json
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
```

### 4.3 Restricción única

Solo puede haber **un registro de progreso por usuario y libro**. Si el usuario intenta añadir un libro que ya está siendo seguido, el endpoint devuelve el registro existente (`200 OK`) en lugar de crear uno nuevo.

### 4.4 Cambiar de modo

Se puede cambiar entre `pages` y `percent` en cualquier momento con `PATCH`:
```json
{ "mode": "percent", "percent": 35 }
```

---

## 5. Reseñas de libros

### 5.1 Una reseña por usuario y libro

Cada usuario puede escribir una sola reseña por libro (restricción única en BD). Si escribe una segunda reseña, la primera se actualiza (patrón **upsert**).

### 5.2 Crear o actualizar una reseña

```
POST /api/books/{externalId}/reviews
{ "rating": 4, "content": "Una obra maestra de la ciencia ficción." }
```

- `rating`: obligatorio, entre 1 y 5.
- `content`: opcional (se puede puntuar sin escribir texto).
- Si el libro no existe en BD, se importa de Google Books automáticamente.

### 5.3 Estadísticas de un libro

`GET /api/books/{externalId}/reviews` devuelve tres bloques de información:

```json
{
  "stats": {
    "average": 4.3,
    "count": 27,
    "distribution": {
      "1": 1,
      "2": 2,
      "3": 3,
      "4": 8,
      "5": 13
    }
  },
  "myRating": {
    "id": 5,
    "rating": 4,
    "content": "Una obra maestra..."
  },
  "reviews": [
    {
      "id": 5,
      "rating": 4,
      "content": "Una obra maestra...",
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

- `stats`: estadísticas globales del libro (independientes del usuario).
- `myRating`: la reseña del usuario actual (o `null` si no ha reseñado).
- `reviews`: todas las reseñas con texto, de más reciente a más antigua.

Si el libro no tiene ninguna reseña aún en la BD, `stats.average` es `null` y `stats.count` es `0`.

### 5.4 Eliminar una reseña

```
DELETE /api/books/{externalId}/reviews
```

Elimina la reseña del usuario autenticado para ese libro. Devuelve las estadísticas actualizadas.

---

## 6. Relación entre los tres submódulos

| Entidad | ¿Comparten el mismo `Book`? | Restricción por usuario |
|---------|-----------------------------|------------------------|
| `ShelfBook` | Sí | Un libro puede estar en varias estanterías del mismo usuario |
| `ReadingProgress` | Sí | Un solo registro por usuario y libro |
| `BookReview` | Sí | Una sola reseña por usuario y libro |

Ejemplo: tres usuarios que añaden "Dune" a sus estanterías comparten la misma entidad `Book` (id=7), pero cada uno tiene su propio `ShelfBook`, `ReadingProgress` y `BookReview`.

---

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
| `502 Bad Gateway` | Error al contactar con Google Books API (solo en el endpoint de detalle `/books/{externalId}`; la búsqueda usa fallback local) |

---

# 17 — Patrones de código y convenciones

Este documento describe los patrones de programación recurrentes en el backend: cómo están estructurados los controladores, cómo se diseñan las entidades, cómo funciona la serialización y qué decisiones de diseño se repiten a lo largo del código.

---

## 1. Inicialización en el constructor de entidades

Varias entidades usan el constructor para establecer valores por defecto y reducir el riesgo de crear objetos en estado inválido. Hay dos estilos:

### Estilo 1: constructor con parámetros obligatorios

Usado en entidades que no tienen sentido sin sus datos fundamentales. Garantiza que el objeto nunca exista sin los datos requeridos:

```php
// Post.php
public function __construct(User $user, string $imagePath, ?string $description = null)
{
    $this->user        = $user;
    $this->imagePath   = $imagePath;
    $this->description = $description;
    $this->createdAt   = new \DateTimeImmutable();   // timestamp automático
    $this->likes       = new ArrayCollection();
    $this->comments    = new ArrayCollection();
}
```

```php
// Follow.php
public function __construct(User $follower, User $following, string $status = self::STATUS_ACCEPTED)
{
    $this->follower  = $follower;
    $this->following = $following;
    $this->status    = $status;
    $this->createdAt = new \DateTimeImmutable();
}
```

```php
// BookReview.php
public function __construct(User $user, Book $book, int $rating, ?string $content = null)
{
    $this->user      = $user;
    $this->book      = $book;
    $this->rating    = $rating;
    $this->content   = $content;
    $this->createdAt = new \DateTimeImmutable();
}
```

```php
// Notification.php
public function __construct(
    User $recipient, User $actor, string $type,
    ?Post $post = null, ?Club $club = null, ?int $refId = null
) {
    // todos los campos en un solo punto
}
```

**Ventaja:** crear estas entidades en el controlador es una sola línea expresiva:
```php
$this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW));
$this->em->persist(new PostLike($post, $me));
```

### Estilo 2: constructor vacío + setters

Usado en entidades más complejas (como `Club`, `Shelf`, `ClubChat`) donde los datos se asignan progresivamente:

```php
$club = new Club();
$club->setName($name);
$club->setVisibility($visibility);
$club->setOwner($this->getUser());
$club->setCreatedAt(new \DateTimeImmutable());
$em->persist($club);
```

---

## 2. Helpers de serialización en controladores

Cada controlador tiene uno o varios métodos privados `serialize*()` que transforman entidades en arrays para la respuesta JSON. Esto centraliza el formato de salida y evita repetir la misma estructura en múltiples acciones:

```php
// PostApiController.php
private function serialize(Post $post, mixed $me): array
{
    return [
        'id'          => $post->getId(),
        'imagePath'   => $post->getImagePath(),
        'description' => $post->getDescription(),
        'createdAt'   => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'likes'       => $this->likeRepo->countByPost($post),
        'liked'       => $me ? $this->likeRepo->findByPostAndUser($post, $me) !== null : false,
        'commentCount'=> $this->commentRepo->count(['post' => $post]),
        'user'        => [
            'id'          => $post->getUser()->getId(),
            'displayName' => $post->getUser()->getDisplayName() ?? $post->getUser()->getEmail(),
            'avatar'      => $post->getUser()->getAvatar(),
        ],
    ];
}
```

Este patrón aparece en todos los controladores:
- `serialize(Post $p)` en `PostApiController`
- `serializeBook(Book $b)` en `ShelfApiController`
- `serializeChat(ClubChat $c)` en `ClubChatApiController`
- `serializeMessage(ClubChatMessage $m)` en `ClubChatApiController`
- `serializeReview(BookReview $r)` en `BookReviewApiController`
- `serializeCurrentBook(Club $c)` en `ClubApiController`
- `serializeOwnProfile(User $u)` en `UserApiController`

---

## 3. Patrón de autorización a nivel de recurso

En Symfony se puede controlar el acceso a nivel de ruta (en `security.yaml`) o a nivel de recurso dentro del controlador. Este proyecto usa el segundo enfoque para tener control fino:

```php
// Paso 1: verificar autenticación
$this->denyAccessUnlessGranted('ROLE_USER');

// Paso 2: buscar el recurso
$shelf = $shelfRepo->find($id);

// Paso 3: verificar propiedad del recurso
if (!$shelf || $shelf->getUser() !== $this->getUser()) {
    return $this->json(['error' => 'Estantería no encontrada'], 404);
}
```

Se devuelve **404** en lugar de **403** intencionalmente. Si se devolviera 403, se estaría confirmando que el recurso existe pero no pertenece al usuario. Con 404, no se revela ninguna información sobre recursos ajenos.

---

## 4. Patrón de validación de entrada

La validación se hace manualmente al inicio de cada método, sin el componente Validator de Symfony. El flujo es:

```
1. Leer y limpiar el input
2. Validar campos obligatorios
3. Validar formato/rango
4. Verificar unicidad en BD si aplica
5. Ejecutar la lógica de negocio
```

Ejemplo completo de `POST /api/auth/register`:

```php
// 1. Leer y limpiar
$email       = trim((string) ($data['email'] ?? ''));
$password    = (string) ($data['password'] ?? '');
$displayName = trim((string) ($data['displayName'] ?? ''));

// 2. Campos obligatorios
if ($email === '' || $password === '') {
    return $this->json(['error' => 'email y password son obligatorios'], 400);
}

// 3. Formato
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return $this->json(['error' => 'El email no es válido'], 400);
}
if (strlen($password) < 6) {
    return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
}

// 4. Unicidad en BD
if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
    return $this->json(['error' => 'Ya existe una cuenta con ese email'], 409);
}

// 5. Lógica de negocio
$user = new User();
// ...
```

---

## 5. Patrón de helper privado `isAdmin()`

En `ClubApiController`, la verificación de si el usuario es admin del club se extrae a un método privado para no repetirla en cada acción:

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

El operador `?->` (nullsafe) hace que si `$membership` es `null`, el resultado sea `null` (falsy) en lugar de lanzar una excepción.

---

## 6. Patrón de helper `resolveChat()` para validación encadenada

Cuando una petición necesita resolver dos recursos relacionados, el patrón de helper devuelve una tupla `[recurso1, recurso2, error_o_null]`:

```php
private function resolveChat(int $clubId, int $chatId, ...): array
{
    $club = $clubRepo->find($clubId);
    if (!$club) {
        return [null, null, $this->json(['error' => 'Club no encontrado'], 404)];
    }

    $chat = $chatRepo->find($chatId);
    if (!$chat || $chat->getClub() !== $club) {
        return [null, null, $this->json(['error' => 'Hilo no encontrado'], 404)];
    }

    return [$club, $chat, null];
}
```

Uso en cada acción:
```php
[$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
if ($error) return $error;
// Aquí $club y $chat están garantizados como válidos
```

Esto elimina la duplicación de la doble validación (club existe + chat pertenece al club) en los 6 endpoints del `ClubChatApiController`.

---

## 7. Patrón de importación lazy de libros

La importación de libros desde Google Books sigue siempre el mismo patrón en los cuatro controladores que lo usan (`ShelfApiController`, `BookReviewApiController`, `ReadingProgressApiController`, `ClubApiController`):

```php
$book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

if (!$book) {
    $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
    if ($book === null) {
        return $this->json(['error' => 'No se encontró el libro en Google Books'], 404);
    }
}
```

El método `importBookFromGoogle()` está **duplicado** en los cuatro controladores. Esta es una deuda técnica conocida; idealmente estaría en un `BookImportService` inyectable. Sin embargo, para el alcance del TFG, la duplicación es aceptable.

---

## 8. Patrón de constantes en entidades

Las entidades con valores string fijos usan constantes de clase para evitar strings mágicos dispersos:

```php
// Follow.php
class Follow
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    // ...
}

// Uso en el controlador:
$status = $target->isPrivate() ? Follow::STATUS_PENDING : Follow::STATUS_ACCEPTED;
$follow = new Follow($me, $target, $status);
```

```php
// Notification.php
class Notification
{
    const TYPE_FOLLOW          = 'follow';
    const TYPE_FOLLOW_REQUEST  = 'follow_request';
    const TYPE_LIKE            = 'like';
    // ...
}

// Uso:
new Notification($target, $me, Notification::TYPE_FOLLOW);
```

Si en el futuro se quiere renombrar un tipo, basta con cambiar la constante.

---

## 9. Inyección de dependencias en controladores

Symfony soporta dos formas de inyectar dependencias en los controladores:

**Por constructor** (para dependencias usadas en múltiples métodos):
```php
class PostApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private PostLikeRepository $likeRepo,
        private PostCommentRepository $commentRepo,
    ) {}
```

**Por parámetro de acción** (para dependencias usadas solo en un método):
```php
#[Route('/api/shelves', methods: ['GET'])]
public function list(ShelfRepository $repo): JsonResponse
{
    // $repo solo se necesita aquí, no en otros métodos del controlador
}
```

Symfony inyecta automáticamente por tipo (autowiring), independientemente del lugar.

---

## 10. Método `getComputedPercent()` en entidades

La entidad `ReadingProgress` tiene un método de negocio que calcula el porcentaje de progreso de forma abstracta, ocultando la lógica de qué modo está activo:

```php
public function getComputedPercent(): int
{
    if ($this->mode === 'percent') {
        return $this->percent ?? 0;
    }
    // modo 'pages'
    $total = $this->totalPages ?? $this->book?->getPageCount();
    if (!$total || $total <= 0) return 0;
    return (int) round(($this->currentPage ?? 0) / $total * 100);
}
```

Cascada de fuentes para `$total`:
1. `totalPages` del progreso (override manual del usuario).
2. `pageCount` del libro (dato de Google Books).
3. Si ninguno está disponible, devuelve `0`.

El controlador simplemente llama `$p->getComputedPercent()` sin necesidad de conocer el modo.

---

## 11. Formato de fechas: `DateTimeImmutable` vs `DateTime`

Todo el proyecto usa exclusivamente `\DateTimeImmutable` en lugar de `\DateTime`. La diferencia es:

- `DateTime` es mutable: `$date->modify('+1 day')` cambia el objeto original.
- `DateTimeImmutable` es inmutable: `$date->modify('+1 day')` devuelve un objeto **nuevo**, el original no cambia.

Esto previene bugs sutiles donde una fecha se modifica accidentalmente al pasarla a una función.

---

## 12. Eliminación de archivos con `@unlink`

El operador `@` suprime errores en PHP. Se usa específicamente en `unlink()` al borrar imágenes:

```php
if (file_exists($imgPath)) {
    @unlink($imgPath);
}
```

La comprobación `file_exists()` reduce el riesgo, pero si el archivo desaparece entre la comprobación y el `unlink()` (condición de carrera), el `@` evita un error fatal. La entidad se borra de BD siempre, independientemente del resultado del `unlink()`.

---

# 18 — Optimización de consultas

Este documento explica los problemas de rendimiento más comunes en aplicaciones que usan un ORM y describe cómo se han resuelto en TFGdaw.

---

## 1. El problema N+1

El **problema N+1** aparece cuando se obtiene una lista de N entidades y luego se accede a una relación de cada una de ellas, provocando N consultas adicionales (una por entidad). En total: 1 consulta inicial + N consultas relacionadas = N+1 consultas.

### Ejemplo concreto: listado de clubes

Sin optimización, listar 20 clubes e incluir el número de miembros de cada uno lanzaría 21 consultas:

```
1.  SELECT * FROM club                          -- 1 consulta principal
2.  SELECT COUNT(*) FROM club_member WHERE club_id = 1
3.  SELECT COUNT(*) FROM club_member WHERE club_id = 2
...
21. SELECT COUNT(*) FROM club_member WHERE club_id = 20
```

Con **batch query**, esto se reduce a 2 consultas:

```sql
-- Consulta 1: todos los clubes
SELECT * FROM club;

-- Consulta 2: todos los conteos en una sola pasada
SELECT m.club_id, COUNT(m.id)
FROM club_member m
WHERE m.club_id IN (1, 2, 3, ..., 20)
GROUP BY m.club_id;
```

---

## 2. `getMemberCountsForClubs()` — batch de conteos

```php
// ClubMemberRepository.php
public function getMemberCountsForClubs(array $clubs): array
{
    if (empty($clubs)) {
        return [];
    }

    $rows = $this->createQueryBuilder('m')
        ->select('IDENTITY(m.club) AS clubId, COUNT(m.id) AS cnt')
        ->where('m.club IN (:clubs)')
        ->setParameter('clubs', $clubs)
        ->groupBy('m.club')
        ->getQuery()
        ->getResult();

    $map = [];
    foreach ($rows as $row) {
        $map[(int) $row['clubId']] = (int) $row['cnt'];
    }

    return $map;
}
```

**Puntos clave:**

- `IDENTITY(m.club)` extrae el ID de la relación `ManyToOne` sin hacer JOIN. Es la función DQL para acceder a la FK directamente.
- `IN (:clubs)` acepta un array de objetos `Club`; Doctrine los convierte automáticamente en sus IDs.
- El resultado es un `array<int, int>`: `[clubId => memberCount]`.
- La guarda de `empty($clubs)` evita un error de SQL si el array está vacío (la cláusula `IN ()` vacía no es SQL válido).

**Uso en `ClubApiController`:**

```php
$clubs        = $clubRepo->findAll();
$countMap     = $memberRepo->getMemberCountsForClubs($clubs);
$memberCount  = $countMap[$club->getId()] ?? 0;
```

---

## 3. `getMembershipsMapForUser()` — membresías en lote

Similar al anterior, pero en lugar de conteos devuelve los objetos `ClubMember` completos para poder consultar el rol del usuario en cada club:

```php
public function getMembershipsMapForUser(User $user, array $clubs): array
{
    $memberships = $this->createQueryBuilder('m')
        ->where('m.user = :user')
        ->andWhere('m.club IN (:clubs)')
        ->setParameter('user', $user)
        ->setParameter('clubs', $clubs)
        ->getQuery()
        ->getResult();

    $map = [];
    foreach ($memberships as $m) {
        $map[$m->getClub()->getId()] = $m;
    }

    return $map;
}
```

**Resultado:** `array<int, ClubMember>` — `[clubId => ClubMember]`.

Para un usuario con 5 clubes activos de una lista de 20, la consulta trae solo esas 5 membresías. El controlador luego indexa el mapa por `clubId` para acceso O(1):

```php
$membershipMap = $memberRepo->getMembershipsMapForUser($me, $clubs);
$membership    = $membershipMap[$club->getId()] ?? null;
$userRole      = $membership?->getRole();  // null si no es miembro
```

---

## 4. `countByClub()` — COUNT sin cargar la colección

Doctrine carga la colección completa si se hace `$club->getMembers()->count()`. Para una sola consulta puntual esto es aceptable, pero en un bucle sobre N clubes el impacto es grave.

```php
// MAL: carga todos los objetos ClubMember en memoria solo para contar
$count = $club->getMembers()->count();

// BIEN: COUNT directo en BD
public function countByClub(Club $club): int
{
    return (int) $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.club = :club')
        ->setParameter('club', $club)
        ->getQuery()
        ->getSingleScalarResult();
}
```

> **Nota:** `countByClub()` se usa cuando se necesita el conteo para un único club (por ejemplo, en `GET /api/clubs/{id}`). Para múltiples clubes en paralelo se usa `getMemberCountsForClubs()`.

---

## 5. Eager loading con `JOIN + addSelect`

Por defecto Doctrine usa **lazy loading**: las relaciones no se cargan hasta que se accede a ellas. Al iterar sobre una colección y acceder a la relación `user` de cada elemento, se lanza una consulta extra por cada elemento.

La solución es cargar la relación en la misma consulta usando `addSelect`:

```php
// findMembersWithUser — ClubMemberRepository
public function findMembersWithUser(Club $club): array
{
    return $this->createQueryBuilder('m')
        ->join('m.user', 'u')        // JOIN a la tabla user
        ->addSelect('u')             // incluye el objeto User en el resultado hidratado
        ->where('m.club = :club')
        ->setParameter('club', $club)
        ->orderBy('m.joinedAt', 'ASC')
        ->getQuery()
        ->getResult();
}
```

**SQL generado:**
```sql
SELECT m.*, u.*
FROM club_member m
INNER JOIN user u ON m.user_id = u.id
WHERE m.club_id = ?
ORDER BY m.joined_at ASC
```

El resultado es una lista de objetos `ClubMember` cuyos atributos `user` ya están hidratados — sin consultas adicionales al acceder a `$member->getUser()`.

### Otros métodos con eager loading

| Método | Repositorio | Relación precargada |
|--------|-------------|---------------------|
| `findMembersWithUser()` | `ClubMemberRepository` | `ClubMember → User` |
| `findByClubWithCreator()` | `ClubChatRepository` | `ClubChat → User (createdBy)` |
| `findPendingWithUser()` | `ClubJoinRequestRepository` | `ClubJoinRequest → User` |
| `findPaginated()` | `ClubChatMessageRepository` | `ClubChatMessage → User` |

---

## 6. `findPaginated()` — paginación con Doctrine

Los mensajes de un hilo de debate pueden ser miles. Cargarlos todos en memoria sería inviable. El repositorio implementa paginación offset/limit:

```php
// ClubChatMessageRepository.php
public function findPaginated(int $chatId, int $page, int $limit): array
{
    return $this->createQueryBuilder('m')
        ->join('m.user', 'u')
        ->addSelect('u')
        ->where('m.chat = :chatId')
        ->setParameter('chatId', $chatId)
        ->orderBy('m.createdAt', 'ASC')
        ->setFirstResult(($page - 1) * $limit)   // OFFSET
        ->setMaxResults($limit)                   // LIMIT
        ->getQuery()
        ->getResult();
}
```

**Equivalente SQL:**
```sql
SELECT m.*, u.*
FROM club_chat_message m
INNER JOIN user u ON m.user_id = u.id
WHERE m.chat_id = ?
ORDER BY m.created_at ASC
LIMIT ? OFFSET ?
```

**Parámetros en la petición HTTP:**
```
GET /api/clubs/{id}/chats/{chatId}/messages?page=2&limit=50
```

El controlador lee y valida estos parámetros:
```php
$page  = max(1, (int) $request->query->get('page', 1));
$limit = min(100, max(1, (int) $request->query->get('limit', 50)));
```

- `max(1, ...)` impide páginas negativas o cero.
- `min(100, ...)` impide que un cliente pida más de 100 mensajes por petición.

La respuesta incluye los metadatos necesarios para que el cliente sepa cuántas páginas existen:
```json
{
  "page": 2,
  "limit": 50,
  "total": 143,
  "messages": [...]
}
```

---

## 7. `countByChat()` — conteo sin cargar mensajes

Al igual que con los clubes, el conteo total de mensajes de un hilo se hace con `COUNT` en lugar de cargar la colección:

```php
public function countByChat(int $chatId): int
{
    return (int) $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.chat = :chatId')
        ->setParameter('chatId', $chatId)
        ->getQuery()
        ->getSingleScalarResult();
}
```

Se usa tanto al listar los hilos de un club (para mostrar cuántos mensajes tiene cada uno) como para calcular el total en la respuesta paginada.

---

## 8. `findFeed()` — LEFT JOIN en el feed

La consulta del feed necesita traer tanto los posts propios como los de usuarios seguidos sin hacer dos consultas separadas:

```php
// PostRepository.php
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

**SQL equivalente:**
```sql
SELECT p.*
FROM post p
LEFT JOIN follow f ON f.follower_id = :me
                  AND f.following_id = p.user_id
                  AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

**Por qué LEFT JOIN y no INNER JOIN:**  
Con INNER JOIN, si el usuario no sigue a nadie, no aparecerían sus propios posts (porque no habría ningún registro `follow` que coincidiera). LEFT JOIN preserva todos los posts y la condición `p.user_id = :me` recupera los propios.

---

## 9. `getStats()` — estadísticas en una sola consulta

Las estadísticas de reseñas de un libro (media, total, distribución) podrían calcularse cargando todos los objetos `BookReview` y haciendo los cálculos en PHP. En cambio, se delegan a la BD:

```php
// BookReviewRepository.php
public function getStats(Book $book): array
{
    $row = $this->createQueryBuilder('r')
        ->select('AVG(r.rating) AS avg, COUNT(r.id) AS total')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->getQuery()
        ->getOneOrNullResult();

    $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $rows = $this->createQueryBuilder('r')
        ->select('r.rating, COUNT(r.id) AS cnt')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->groupBy('r.rating')
        ->getQuery()
        ->getResult();

    foreach ($rows as $r) {
        $dist[(int) $r['rating']] = (int) $r['cnt'];
    }

    return [
        'average'      => $row['avg'] ? round((float) $row['avg'], 1) : null,
        'count'        => (int) ($row['total'] ?? 0),
        'distribution' => $dist,
    ];
}
```

Son 2 consultas en lugar de cargar N objetos en PHP: una para `AVG + COUNT` global y otra para la distribución por estrellas.

---

## 10. Resumen de estrategias aplicadas

| Problema | Consultas sin optimizar | Solución aplicada | Consultas optimizadas |
|----------|------------------------|-------------------|-----------------------|
| Conteo de miembros al listar N clubes | N+1 | `getMemberCountsForClubs()` batch | 2 |
| Rol del usuario en N clubes | N+1 | `getMembershipsMapForUser()` batch | 2 |
| Cargar usuarios de N miembros | N | `findMembersWithUser()` eager JOIN | 1 |
| Cargar autores de N mensajes | N | `findPaginated()` eager JOIN | 1 |
| Conteo de mensajes por hilo | 1 carga colección | `countByChat()` COUNT | 1 (ligera) |
| Feed con follows | 2 separadas + merge PHP | `findFeed()` LEFT JOIN | 1 |
| Estadísticas de reseñas | N cargas + PHP | `getStats()` AVG/COUNT en BD | 2 |

---

# 19 — Modelo de privacidad

TFGdaw tiene un sistema de privacidad con tres dimensiones independientes: la cuenta de usuario, las secciones del perfil y la visibilidad de los clubes. Este documento describe cada dimensión, los valores posibles y cómo afectan a las respuestas de la API.

---

## 1. Visión general

```
Usuario
├── isPrivate         → controla quién puede seguirte y ver tu contenido
├── shelvesPublic     → controla si tus estanterías son visibles en tu perfil
└── clubsPublic       → controla si tus clubes son visibles en tu perfil

Club
└── visibility        → controla si el club aparece en listados y cómo se une
```

Cada flag es independiente: un usuario puede tener cuenta pública pero estanterías privadas, o cuenta privada pero clubes públicos.

---

## 2. Flag `isPrivate` — privacidad de la cuenta

### 2.1 Definición en la entidad

```php
// User.php
#[ORM\Column]
private bool $isPrivate = false;
```

El valor por defecto es `false` (cuenta pública). El usuario puede cambiarlo desde el endpoint `PATCH /api/profile`.

### 2.2 Efecto en el sistema de follows

Este flag es el que más impacto tiene en la funcionalidad. Modifica el flujo completo del sistema de seguimiento:

| Situación | Cuenta pública (`isPrivate = false`) | Cuenta privada (`isPrivate = true`) |
|-----------|--------------------------------------|-------------------------------------|
| Alguien intenta seguirte | Follow directo con `status = accepted` | Follow con `status = pending` (solicitud) |
| Notificación generada | `TYPE_FOLLOW` | `TYPE_FOLLOW_REQUEST` |
| El nuevo seguidor ve tus posts | Inmediatamente | Solo tras aprobación |

**Código en `FollowApiController`:**

```php
$status = $target->isPrivate()
    ? Follow::STATUS_PENDING
    : Follow::STATUS_ACCEPTED;

$follow = new Follow($me, $target, $status);
$this->em->persist($follow);

if ($status === Follow::STATUS_ACCEPTED) {
    $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW));
} else {
    $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW_REQUEST, null, null, $follow->getId()));
}
```

### 2.3 Efecto en los posts

La visibilidad de los posts de cuentas privadas **la gestiona el frontend**, no el backend. El endpoint `GET /api/users/{id}/posts` devuelve siempre los posts independientemente de si el perfil es privado.

El frontend recibe el campo `followStatus` en la respuesta del perfil y aplica la lógica:

```
si perfil.isPrivate == true && followStatus != "accepted"
    → no mostrar posts
```

> Esta decisión de diseño simplifica el backend pero delega responsabilidad al cliente. Una implementación más robusta añadiría el filtro en el backend.

### 2.4 Efecto en el perfil público

`GET /api/users/{id}` siempre devuelve los datos básicos del perfil (nombre, bio, avatar, contadores de seguidores), independientemente de si la cuenta es privada. Lo que varía es el acceso al contenido.

---

## 3. Flag `shelvesPublic` — visibilidad de estanterías

### 3.1 Definición en la entidad

```php
// User.php
#[ORM\Column]
private bool $shelvesPublic = true;
```

Por defecto `true` (estanterías visibles). El usuario puede ocultarlas con `PATCH /api/profile`.

### 3.2 Efecto en el perfil público

En `GET /api/users/{id}`, el backend comprueba el flag antes de incluir las estanterías:

```php
// UserApiController.php
'shelves' => $user->isShelvesPublic()
    ? array_map(fn($shelf) => [
        'id'    => $shelf->getId(),
        'name'  => $shelf->getName(),
        'books' => array_map(fn($sb) => $this->serializeBook($sb->getBook()), $shelf->getBooks()->toArray()),
    ], $user->getShelves()->toArray())
    : null,
```

Si `shelvesPublic = false`, el campo `shelves` vale `null` en la respuesta. Esto permite al frontend distinguir entre "el usuario no tiene estanterías" (`[]`) y "el usuario tiene estanterías pero no son visibles" (`null`).

### 3.3 Efecto en el perfil propio

`GET /api/profile` devuelve el perfil completo del usuario autenticado, incluyendo siempre sus propias estanterías (independientemente del flag), ya que es su propio perfil.

---

## 4. Flag `clubsPublic` — visibilidad de clubes en el perfil

### 4.1 Definición en la entidad

```php
// User.php
#[ORM\Column]
private bool $clubsPublic = true;
```

Por defecto `true`. Controla si la lista de clubes a los que pertenece el usuario aparece en su perfil público.

### 4.2 Efecto en el perfil público

Mismo patrón que `shelvesPublic`:

```php
// UserApiController.php
'clubs' => $user->isClubsPublic()
    ? array_map(fn($m) => [
        'id'         => $m->getClub()->getId(),
        'name'       => $m->getClub()->getName(),
        'visibility' => $m->getClub()->getVisibility(),
        'role'       => $m->getRole(),
    ], $user->getClubMemberships()->toArray())
    : null,
```

Si `clubsPublic = false`, el campo `clubs` vale `null`. Si `clubsPublic = true`, devuelve solo los clubes donde el usuario es miembro activo.

---

## 5. `visibility` del club — acceso al club

Este campo pertenece a la entidad `Club`, no al usuario, y controla cómo se puede unir alguien al club y qué información se ve.

### 5.1 Definición en la entidad

```php
// Club.php
#[ORM\Column(length: 20)]
private string $visibility = 'public';
```

Valores posibles: `'public'` y `'private'`.

### 5.2 Diferencias entre public y private

| Aspecto | `public` | `private` |
|---------|----------|-----------|
| Aparece en `GET /api/clubs` | Sí | Solo si eres miembro |
| Unirse | Inmediato (`joined`) | Requiere aprobación del admin |
| Notificación al admin | No | `TYPE_CLUB_REQUEST` |
| Ver miembros (`GET /api/clubs/{id}/members`) | Sí (solo miembros) | Solo si eres miembro |
| Ver hilos de debate | Solo miembros | Solo miembros |

### 5.3 Flujo de unión a un club privado

```
POST /api/clubs/{id}/join (club privado)
         │
         ▼
ClubJoinRequest(status: pending) creado
         │
         ▼
Notificación TYPE_CLUB_REQUEST → admin del club
         │                           │
         │            (admin acepta) │
         ▼                           ▼
  ClubMember creado           ClubJoinRequest.status = approved
  role: 'member'              Notificación TYPE_CLUB_REQUEST_ACCEPTED → solicitante
```

**Código en `ClubApiController.join()`:**

```php
if ($club->getVisibility() === 'private') {
    // Comprobar si ya hay solicitud pendiente
    $existing = $joinRequestRepo->findOneBy(['club' => $club, 'user' => $me]);
    if ($existing) {
        return $this->json(['status' => 'already_requested'], 409);
    }
    $request = new ClubJoinRequest($me, $club);
    $this->em->persist($request);

    // Notificar al admin
    $admin = $memberRepo->findOneBy(['club' => $club, 'role' => 'admin']);
    if ($admin) {
        $this->em->persist(new Notification(
            $admin->getUser(), $me,
            Notification::TYPE_CLUB_REQUEST,
            null, $club, $request->getId()
        ));
    }
    $this->em->flush();
    return $this->json(['status' => 'requested']);
}
```

---

## 6. Combinaciones de privacidad y comportamientos resultantes

### 6.1 Usuario A visita el perfil del usuario B

| Condición | Datos devueltos |
|-----------|----------------|
| B tiene cuenta pública | Perfil completo + estanterías (si `shelvesPublic`) + clubes (si `clubsPublic`) |
| B tiene cuenta privada, A no le sigue | Datos básicos (nombre, bio, avatar, contadores). Posts no visibles en el frontend |
| B tiene cuenta privada, A le sigue (accepted) | Igual que cuenta pública |
| B oculta estanterías (`shelvesPublic = false`) | `shelves: null` en la respuesta |
| B oculta clubes (`clubsPublic = false`) | `clubs: null` en la respuesta |

### 6.2 Datos siempre visibles independientemente de la privacidad

Independientemente de la configuración del usuario, estos datos son siempre públicos:
- `displayName`
- `bio`
- `avatar`
- Número de seguidores (`followers`)
- Número de seguidos (`following`)

### 6.3 Campo `followStatus` en el perfil público

`GET /api/users/{id}` incluye el estado del follow entre el visitante y el propietario del perfil:

```json
{
  "followStatus": "none" | "pending" | "accepted",
  "isFollowing": true | false
}
```

| `followStatus` | Significado |
|----------------|-------------|
| `"none"` | No hay relación de seguimiento |
| `"pending"` | Solicitud enviada pero pendiente de aceptación |
| `"accepted"` | El usuario visitante sigue al propietario del perfil |

El frontend usa estos valores para mostrar el botón correcto: "Seguir", "Solicitud enviada", o "Siguiendo".

---

## 7. Modificar la configuración de privacidad

```
PATCH /api/profile
Content-Type: application/json

{
  "isPrivate": true,
  "shelvesPublic": false,
  "clubsPublic": true
}
```

Todos los campos son opcionales: se pueden cambiar uno o varios en la misma petición. El endpoint ignora los campos no enviados (no los resetea).

**Lógica del controlador:**

```php
if (isset($data['isPrivate'])) {
    $me->setIsPrivate((bool) $data['isPrivate']);
}
if (isset($data['shelvesPublic'])) {
    $me->setShelvesPublic((bool) $data['shelvesPublic']);
}
if (isset($data['clubsPublic'])) {
    $me->setClubsPublic((bool) $data['clubsPublic']);
}
$this->em->flush();
```

---

## 8. Tabla resumen de flags de privacidad

| Flag | Entidad | Valor por defecto | Controla |
|------|---------|-------------------|---------|
| `isPrivate` | `User` | `false` | Requiere aprobación para follows; posts no visibles sin seguimiento |
| `shelvesPublic` | `User` | `true` | Estanterías visibles en perfil público |
| `clubsPublic` | `User` | `true` | Clubes visibles en perfil público |
| `visibility` | `Club` | `'public'` | Aparece en listados; requiere solicitud para unirse si es `'private'` |

---

# 20 — Panel de administración

El panel de administración permite a usuarios con `ROLE_ADMIN` gestionar todos los recursos de la plataforma sin restricciones de propiedad. Este documento describe todos los endpoints disponibles, las reglas de negocio aplicadas y la respuesta devuelta.

---

## 1. Estructura del controlador

Todos los endpoints están en `AdminApiController` bajo el prefijo `/api/admin`:

```php
#[Route('/api/admin', name: 'api_admin_')]
class AdminApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private ClubRepository $clubRepo,
        private PostRepository $postRepo,
    ) {}
```

**Autorización:** Todos los métodos aplican `$this->denyAccessUnlessGranted('ROLE_ADMIN')` como primera instrucción, antes de cualquier lógica. Si el usuario no está autenticado o no tiene el rol, Symfony lanza `AccessDeniedException` → 401 o 403 automáticamente.

---

## 2. Estadísticas generales

### `GET /api/admin/stats`

Devuelve tres contadores globales de la plataforma:

```php
public function stats(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    return $this->json([
        'users' => $this->userRepo->count([]),
        'clubs' => $this->clubRepo->count([]),
        'posts' => $this->postRepo->count([]),
    ]);
}
```

**Respuesta:**
```json
{
  "users": 154,
  "clubs": 23,
  "posts": 891
}
```

`count([])` es el método heredado de `ServiceEntityRepository` que ejecuta `SELECT COUNT(*) FROM tabla` sin condiciones. Las tres consultas se ejecutan de forma secuencial pero son extremadamente ligeras.

---

## 3. Gestión de usuarios

### `GET /api/admin/users`

Lista todos los usuarios ordenados por `id DESC` (más recientes primero):

```php
$users = $this->userRepo->findBy([], ['id' => 'DESC']);

return $this->json(array_map(fn(User $u) => [
    'id'          => $u->getId(),
    'email'       => $u->getEmail(),
    'displayName' => $u->getDisplayName(),
    'avatar'      => $u->getAvatar(),
    'roles'       => $u->getRoles(),
    'isVerified'  => $u->isVerified(),
    'isAdmin'     => in_array('ROLE_ADMIN', $u->getRoles(), true),
    'isBanned'    => $u->isBanned(),
], $users));
```

El campo `isAdmin` es un campo calculado (no existe en BD) que facilita al frontend mostrar una insignia sin tener que parsear el array `roles`. Se calcula con `in_array('ROLE_ADMIN', ...)` con el tercer argumento `true` para comparación estricta de tipos.

**Respuesta:**
```json
[
  {
    "id": 1,
    "email": "admin@ejemplo.com",
    "displayName": "Admin",
    "avatar": null,
    "roles": ["ROLE_ADMIN", "ROLE_USER"],
    "isVerified": true,
    "isAdmin": true,
    "isBanned": false
  },
  {
    "id": 2,
    "email": "usuario@ejemplo.com",
    "displayName": "MariaG",
    "avatar": "avatar_2.jpg",
    "roles": ["ROLE_USER"],
    "isVerified": false,
    "isAdmin": false,
    "isBanned": false
  }
]
```

---

### `PATCH /api/admin/users/{id}/role`

Promueve o degrada el rol de administrador de un usuario. Body JSON:
```json
{ "isAdmin": true }
```
o
```json
{ "isAdmin": false }
```

**Regla de negocio crítica — no puedes cambiarte el rol a ti mismo:**

```php
$me = $this->getUser();
if ($user->getId() === $me->getId()) {
    return $this->json(['error' => 'No puedes cambiar tu propio rol'], 400);
}
```

Esta protección evita el escenario en que el único administrador se degrada a sí mismo accidentalmente, dejando la plataforma sin ningún administrador.

**Lógica de modificación de roles:**

```php
$roles = array_filter(
    $user->getRoles(),
    fn($r) => $r !== 'ROLE_ADMIN' && $r !== 'ROLE_USER'
);

if ($isAdmin) {
    $roles[] = 'ROLE_ADMIN';
}

$user->setRoles(array_values($roles));
$this->em->flush();
```

Pasos:
1. `array_filter` elimina `ROLE_ADMIN` y `ROLE_USER` del array de roles guardado en BD. (`ROLE_USER` lo añade automáticamente `getRoles()` en tiempo de ejecución; guardarlo en BD sería redundante.)
2. Si `isAdmin = true`, se añade `ROLE_ADMIN` al array filtrado.
3. `array_values` reindexa el array para que Doctrine lo serialice correctamente como array JSON.

**Respuesta:**
```json
{
  "id": 7,
  "isAdmin": true,
  "roles": ["ROLE_ADMIN", "ROLE_USER"]
}
```

---

### `PATCH /api/admin/users/{id}/ban`

Suspende o reactiva una cuenta de usuario. Body JSON:
```json
{ "isBanned": true }
```

**Regla de negocio:** no se puede banear al propio admin autenticado.

Un usuario baneado no puede iniciar sesión — el `UserChecker` rechaza la autenticación con el mensaje *"Tu cuenta ha sido suspendida por un administrador."* Su sesión activa queda invalidada en la siguiente petición autenticada.

**Respuesta:**
```json
{
  "id": 5,
  "isBanned": true
}
```

En el frontend (`/admin` → pestaña Usuarios), cada fila tiene un botón "Banear" / "Desbanear" en color naranja y una columna "Estado" que muestra la insignia "Baneado" o "Activo".

---

### `DELETE /api/admin/users/{id}`

Elimina permanentemente una cuenta de usuario y todo su contenido en cascada.

**Protección de auto-eliminación:**

```php
$me = $this->getUser();
if ($user->getId() === $me->getId()) {
    return $this->json(['error' => 'No puedes eliminar tu propia cuenta desde el panel'], 400);
}
```

Al igual que con el cambio de rol, se impide que el admin se elimine a sí mismo desde el panel. El admin podría borrar su cuenta desde los endpoints normales del perfil si lo deseara.

**Efecto en cascada:**

Al eliminar un `User`, Doctrine (y el motor de BD) eliminan en cascada todo lo que depende de él:
- Sus estanterías y los libros en ellas (`ShelfBook`)
- Su progreso de lectura (`ReadingProgress`)
- Sus reseñas (`BookReview`)
- Sus posts, likes y comentarios
- Sus follows (como follower y como following)
- Sus membresías en clubes (`ClubMember`)
- Sus solicitudes de ingreso (`ClubJoinRequest`)
- Sus mensajes en clubes (`ClubChatMessage`)
- Sus notificaciones recibidas y enviadas
- Los clubes de los que es propietario (con todos sus miembros, hilos y mensajes)

**Respuesta:** `204 No Content`

---

## 4. Gestión de clubes

### `GET /api/admin/clubs`

Lista todos los clubes ordenados por `id DESC`:

```php
return $this->json(array_map(fn($club) => [
    'id'          => $club->getId(),
    'name'        => $club->getName(),
    'description' => $club->getDescription(),
    'visibility'  => $club->getVisibility(),
    'memberCount' => $club->getMembers()->count(),
    'owner'       => $club->getOwner() ? [
        'id'          => $club->getOwner()->getId(),
        'displayName' => $club->getOwner()->getDisplayName(),
        'email'       => $club->getOwner()->getEmail(),
    ] : null,
    'createdAt'   => $club->getCreatedAt()?->format(\DateTimeInterface::ATOM),
], $clubs));
```

> **Nota técnica:** `$club->getMembers()->count()` carga la colección completa para cada club. En un panel de administración con cientos de clubes esto podría optimizarse con `getMemberCountsForClubs()` (ver [18-optimizacion-consultas.md](18-optimizacion-consultas.md)). Para el alcance del TFG, donde el panel lo usa un único administrador en sesiones ocasionales, la carga adicional es aceptable.

**Respuesta:**
```json
[
  {
    "id": 5,
    "name": "Club de Fantasía",
    "description": "Lectores de fantasía épica",
    "visibility": "public",
    "memberCount": 12,
    "owner": {
      "id": 1,
      "displayName": "Creador",
      "email": "creador@ejemplo.com"
    },
    "createdAt": "2026-02-01T10:00:00+00:00"
  }
]
```

---

### `DELETE /api/admin/clubs/{id}`

Elimina un club sin importar quién sea el propietario:

```php
$club = $this->clubRepo->find($id);
if (!$club) {
    return $this->json(['error' => 'Club no encontrado'], 404);
}

$this->em->remove($club);
$this->em->flush();
```

A diferencia del endpoint de usuario normal (`DELETE /api/clubs/{id}`), aquí no se verifica que el administrador sea el propietario del club. La verificación de `ROLE_ADMIN` al inicio del método es suficiente.

**Efecto en cascada:** Al eliminar el club se eliminan en cascada sus `ClubMember`, `ClubJoinRequest`, `ClubChat` (y sus `ClubChatMessage`), y las notificaciones relacionadas.

**Respuesta:** `204 No Content`

---

## 5. Gestión de posts

### `GET /api/admin/posts`

Lista los 100 posts más recientes con información del autor:

```php
$posts = $this->postRepo->findBy([], ['id' => 'DESC'], 100);

return $this->json(array_map(fn($post) => [
    'id'          => $post->getId(),
    'description' => $post->getDescription(),
    'imagePath'   => $post->getImagePath(),
    'createdAt'   => $post->getCreatedAt()?->format(\DateTimeInterface::ATOM),
    'user'        => [
        'id'          => $post->getUser()->getId(),
        'displayName' => $post->getUser()->getDisplayName(),
        'email'       => $post->getUser()->getEmail(),
    ],
], $posts));
```

El límite de 100 posts es un hardcode deliberado. El panel de administración no está diseñado como una herramienta de auditoría histórica exhaustiva, sino para revisar publicaciones recientes y eliminar contenido inapropiado.

**Respuesta:**
```json
[
  {
    "id": 891,
    "description": "Terminando Dune...",
    "imagePath": "post_6716a3b4e5f12.jpg",
    "createdAt": "2026-04-19T10:00:00+00:00",
    "user": {
      "id": 7,
      "displayName": "MariaG",
      "email": "maria@ejemplo.com"
    }
  }
]
```

---

### `DELETE /api/admin/posts/{id}`

Elimina cualquier post de cualquier usuario, incluyendo el archivo de imagen:

```php
$imgPath = $this->getParameter('kernel.project_dir')
    . '/public/uploads/posts/'
    . $post->getImagePath();

if (file_exists($imgPath)) {
    @unlink($imgPath);
}

$this->em->remove($post);
$this->em->flush();
```

El proceso es el mismo que la eliminación de un post por su autor (ver [14-modulo-social.md](14-modulo-social.md)), pero sin verificar la propiedad. La ruta de la imagen se construye concatenando el directorio del proyecto (obtenido de `kernel.project_dir`) con la ruta relativa.

**Respuesta:** `204 No Content`

---

## 6. Diferencias entre admin y usuario normal

| Acción | Usuario normal | Administrador |
|--------|---------------|---------------|
| Ver todos los usuarios | No | `GET /api/admin/users` |
| Cambiar roles | No | `PATCH /api/admin/users/{id}/role` |
| Banear / desbanear usuarios | No | `PATCH /api/admin/users/{id}/ban` |
| Eliminar cualquier usuario | No | `DELETE /api/admin/users/{id}` |
| Eliminar su propio usuario | Sí (`DELETE /api/profile`) | Solo desde el perfil, no desde el panel |
| Ver todos los clubes | Solo los públicos | `GET /api/admin/clubs` (todos) |
| Eliminar cualquier club | Solo los suyos | `DELETE /api/admin/clubs/{id}` |
| Actuar como admin en cualquier club | Solo en sus clubs | Sí — `isAdmin()` incluye `ROLE_ADMIN` |
| Eliminar cualquier post | Solo los suyos | Desde el panel y desde la UI |
| Eliminar cualquier comentario | Solo propios o en posts propios | Sí (`deleteComment` incluye `ROLE_ADMIN`) |
| Ver estadísticas globales | No | `GET /api/admin/stats` |

---

## 7. Seguridad del panel

### 7.1 Roles en Symfony

`ROLE_ADMIN` es un rol adicional que se almacena explícitamente en BD. `ROLE_USER` es el rol base que todos los usuarios tienen automáticamente (añadido por `getRoles()` en la entidad `User`):

```php
public function getRoles(): array
{
    $roles   = $this->roles;
    $roles[] = 'ROLE_USER';        // añadido siempre en tiempo de ejecución
    return array_unique($roles);
}
```

En BD, el campo `roles` de un administrador contiene `["ROLE_ADMIN"]`; el de un usuario normal contiene `[]`.

### 7.2 `denyAccessUnlessGranted` vs `security.yaml`

El acceso admin se verifica **dentro del método** con `denyAccessUnlessGranted`, no con restricciones en `security.yaml`. Esto permite que todos los endpoints del panel compartan el mismo prefijo `/api/admin` sin configurar rutas adicionales, y facilita añadir excepciones individuales si fuera necesario.

### 7.3 Protecciones de integridad

Tres operaciones tienen protección adicional para evitar estados irreparables:

1. **No puedes degradarte a ti mismo:** `PATCH /api/admin/users/{id}/role` devuelve `400` si `id` coincide con el usuario autenticado.
2. **No puedes banearte a ti mismo:** `PATCH /api/admin/users/{id}/ban` devuelve `400` si `id` coincide con el usuario autenticado.
3. **No puedes eliminarte desde el panel:** `DELETE /api/admin/users/{id}` devuelve `400` si `id` coincide con el usuario autenticado.

Las tres verificaciones comparan `$user->getId() === $me->getId()` con igualdad estricta sobre enteros.

### 7.4 Permisos heredados en controladores normales

El `ROLE_ADMIN` no solo actúa en el panel — también extiende los permisos en los controladores regulares:

- **`ClubApiController`:** el helper `isAdmin(Club, repo)` devuelve `true` si el usuario tiene `ROLE_ADMIN`, sin importar si es miembro del club. Esto permite expulsar miembros, aprobar/rechazar solicitudes, establecer el libro del mes y eliminar hilos en cualquier club.
- **`PostApiController`:** `deleteComment` permite al admin eliminar cualquier comentario, no solo los propios o los de sus posts.
- **Frontend:** `PostCard` acepta la prop `isAdmin` para mostrar el botón de eliminar en posts y comentarios de otros usuarios cuando el admin navega la aplicación normalmente.

---

# 21 — Módulo de perfil de usuario

El módulo de perfil cubre todo lo relacionado con la identidad de un usuario: ver y editar sus datos, cambiar contraseña, subir avatar, configurar privacidad y buscar otros usuarios. Está implementado en `UserApiController`.

---

## 1. Estructura del controlador

```php
#[Route('/api', name: 'api_user_')]
class UserApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private FollowRepository $followRepo,
    ) {}
```

Las tres dependencias inyectadas por constructor cubren todos los métodos: `$em` para persistir cambios, `$hasher` para verificar y cifrar contraseñas, `$followRepo` para calcular contadores de seguidores en las respuestas.

---

## 2. `GET /api/profile` — perfil propio completo

Devuelve todos los datos del usuario autenticado, incluyendo flags de privacidad, estanterías y clubes. A diferencia del perfil público, aquí se incluye el email y los flags de configuración:

```php
#[Route('/profile', name: 'profile_get', methods: ['GET'])]
public function getProfile(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');
    return $this->json($this->serializeOwnProfile($this->getUser()));
}
```

El método privado `serializeOwnProfile()` centraliza el formato para que todos los endpoints que modifican el perfil devuelvan exactamente la misma estructura:

```php
private function serializeOwnProfile(User $user): array
{
    return [
        'id'            => $user->getId(),
        'email'         => $user->getEmail(),
        'displayName'   => $user->getDisplayName(),
        'bio'           => $user->getBio(),
        'avatar'        => $user->getAvatar(),
        'shelvesPublic' => $user->isShelvesPublic(),
        'clubsPublic'   => $user->isClubsPublic(),
        'isPrivate'     => $user->isPrivate(),
        'followers'     => $this->followRepo->countFollowers($user),
        'following'     => $this->followRepo->countFollowing($user),
        'shelves'       => array_map(
            fn($s) => ['id' => $s->getId(), 'name' => $s->getName()],
            $user->getShelves()->toArray()
        ),
        'clubs'         => array_map(
            fn($m) => [
                'id'         => $m->getClub()->getId(),
                'name'       => $m->getClub()->getName(),
                'visibility' => $m->getClub()->getVisibility(),
                'role'       => $m->getRole(),
            ],
            $user->getClubMemberships()->toArray()
        ),
    ];
}
```

Contiene más información que el perfil público (`GET /api/users/{id}`): incluye `email`, los tres flags de privacidad, y la lista de estanterías/clubes **siempre** (sin importar la configuración de privacidad, ya que es el propio usuario).

---

## 3. `PUT /api/profile` — editar displayName y bio

```php
#[Route('/profile', name: 'profile_update', methods: ['PUT'])]
public function updateProfile(Request $request, UserRepository $userRepository): JsonResponse
```

### 3.1 Validación del displayName

La validación tiene cuatro capas:

```php
// 1. No vacío
if ($displayName === '') {
    return $this->json(['error' => 'El nombre de usuario no puede estar vacío'], 400);
}

// 2. Longitud mínima
if (strlen($displayName) < 3) {
    return $this->json(['error' => 'El nombre de usuario debe tener al menos 3 caracteres'], 400);
}

// 3. Caracteres permitidos: letras, números, puntos, guiones y guiones bajos
if (!preg_match('/^[\w.\-]+$/u', $displayName)) {
    return $this->json(['error' => 'Solo letras, números, puntos, guiones y guiones bajos'], 400);
}

// 4. Unicidad en BD, excluyendo el propio usuario
$existing = $userRepository->findOneBy(['displayName' => $displayName]);
if ($existing && $existing->getId() !== $user->getId()) {
    return $this->json(['error' => 'Este nombre de usuario ya está en uso'], 409);
}
```

La exclusión `$existing->getId() !== $user->getId()` permite que el usuario "guarde" sin cambios sin recibir error de conflicto.

La expresión regular `^[\w.\-]+$` con el flag `u` (Unicode) permite:
- `\w` → letras, dígitos y `_`
- `.` → punto literal
- `\-` → guión

### 3.2 Actualización de la bio

```php
if (array_key_exists('bio', $data)) {
    $bio = trim((string) $data['bio']);
    $user->setBio($bio !== '' ? $bio : null);
}
```

Se usa `array_key_exists` en lugar de `isset` porque `isset` retornaría `false` si el valor es explícitamente `null`. Esto permite enviar `"bio": null` o `"bio": ""` para eliminar la bio.

### 3.3 Respuesta

Devuelve el perfil completo serializado con `serializeOwnProfile()`, de modo que el cliente siempre recibe el estado actualizado en la misma petición.

---

## 4. `POST /api/profile/avatar` — subir avatar

```php
#[Route('/profile/avatar', name: 'profile_avatar', methods: ['POST'])]
public function uploadAvatar(Request $request): JsonResponse
{
    $file = $request->files->get('avatar');

    if (!$file) {
        return $this->json(['error' => 'No se envió ningún archivo'], 400);
    }

    $filename = uniqid() . '.' . $file->guessExtension();
    $file->move(
        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
        $filename
    );

    $user->setAvatar($filename);
    $this->em->flush();

    return $this->json(['avatar' => $filename]);
}
```

**Proceso:**
1. `$request->files->get('avatar')` extrae el archivo del campo `avatar` del formulario multipart.
2. `guessExtension()` detecta la extensión por el contenido MIME real del archivo, no por su nombre (seguridad contra extensiones falsas).
3. `uniqid()` genera un identificador único basado en timestamp con microsegundos — suficientemente único para evitar colisiones.
4. Solo se guarda el nombre del archivo (`avatar_66f2a3c1.jpg`), no la ruta completa. La ruta base se añade en el frontend al construir la URL.

> **Diferencia con posts:** Los avatares usan `uniqid()` simple, mientras que los posts usan `uniqid('post_', true)` (con prefijo y más entropía). La razón es que los posts se crean con más frecuencia y necesitan mayor unicidad.

**Respuesta:**
```json
{ "avatar": "66f2a3c1.jpg" }
```

---

## 5. `PUT /api/profile/password` — cambiar contraseña

```php
#[Route('/profile/password', name: 'profile_password', methods: ['PUT'])]
public function changePassword(Request $request): JsonResponse
```

**Flujo de validación:**

```php
// 1. Campos obligatorios
if ($currentPassword === '' || $newPassword === '') {
    return $this->json(['error' => 'Se requieren currentPassword y newPassword'], 400);
}

// 2. Verificar la contraseña actual (comparación con el hash en BD)
if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
    return $this->json(['error' => 'La contraseña actual es incorrecta'], 400);
}

// 3. Longitud mínima de la nueva
if (strlen($newPassword) < 6) {
    return $this->json(['error' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
}

// 4. Hashear y guardar
$user->setPassword($this->hasher->hashPassword($user, $newPassword));
$this->em->flush();
```

`isPasswordValid()` usa el `UserPasswordHasherInterface` de Symfony, que internamente aplica el mismo algoritmo que se usó al crear la contraseña (bcrypt por defecto en Symfony 7). No se compara el texto plano contra el hash directamente.

**Respuesta:**
```json
{ "status": "password_updated" }
```

---

## 6. `PUT /api/profile/privacy` — configurar privacidad

```php
#[Route('/profile/privacy', name: 'profile_privacy', methods: ['PUT'])]
public function updatePrivacy(Request $request): JsonResponse
```

Actualiza cualquier combinación de los tres flags de privacidad en una sola petición:

```php
if (isset($data['shelvesPublic'])) {
    $user->setShelvesPublic((bool) $data['shelvesPublic']);
}
if (isset($data['clubsPublic'])) {
    $user->setClubsPublic((bool) $data['clubsPublic']);
}
if (isset($data['isPrivate'])) {
    $user->setIsPrivate((bool) $data['isPrivate']);
}
$this->em->flush();
return $this->json($this->serializeOwnProfile($user));
```

El uso de `isset` (en lugar de `array_key_exists`) aquí es correcto: los flags de privacidad son booleanos y nunca deberían ser `null`, por lo que `isset` es suficiente.

Los flags no enviados no se modifican — el endpoint es no destructivo.

---

## 7. `GET /api/users/{id}` — perfil público de otro usuario

```php
#[Route('/users/{id}', name: 'user_public', requirements: ['id' => '\d+'], methods: ['GET'])]
public function getUserProfile(int $id, UserRepository $userRepository): JsonResponse
```

Este endpoint es **público** (no requiere `denyAccessUnlessGranted`). Cualquiera puede ver el perfil básico de cualquier usuario. Lo que varía es el contenido según los flags de privacidad.

### 7.1 Cálculo del followStatus

```php
$me           = $this->getUser();   // null si no está autenticado
$followStatus = 'none';

if ($me && $me->getId() !== $user->getId()) {
    $follow = $this->followRepo->findFollow($me, $user);
    if ($follow) {
        $followStatus = $follow->getStatus();  // 'pending' | 'accepted'
    }
}
```

Si el visitante no está autenticado, `$me` es `null` y `followStatus` permanece `'none'`. Si el visitante es el propietario del perfil, tampoco se busca el follow.

### 7.2 Respeto a los flags de privacidad

```php
'shelves' => $user->isShelvesPublic()
    ? array_map(fn($s) => [
        'id'    => $s->getId(),
        'name'  => $s->getName(),
        'books' => array_map(fn($sb) => [
            'id'        => $sb->getBook()->getId(),
            'title'     => $sb->getBook()->getTitle(),
            'authors'   => $sb->getBook()->getAuthors() ?? [],
            'coverUrl'  => $sb->getBook()->getCoverUrl(),
            'thumbnail' => $sb->getBook()->getCoverUrl(),
        ], $s->getShelfBooks()->toArray()),
    ], $user->getShelves()->toArray())
    : null,

'clubs' => $user->isClubsPublic()
    ? array_map(fn($m) => [
        'id'         => $m->getClub()->getId(),
        'name'       => $m->getClub()->getName(),
        'visibility' => $m->getClub()->getVisibility(),
        'role'       => $m->getRole(),
    ], $user->getClubMemberships()->toArray())
    : null,
```

Cuando el flag está desactivado, el campo vale `null`. El frontend puede distinguir entre "lista vacía" y "lista oculta".

---

## 8. `GET /api/users/search?q=...` — buscar usuarios

```php
#[Route('/users/search', name: 'user_search', methods: ['GET'])]
public function search(Request $request, UserRepository $userRepository): JsonResponse
{
    $q = trim((string) $request->query->get('q', ''));

    if (strlen($q) < 2) {
        return $this->json([]);
    }

    $me    = $this->getUser();
    $users = $userRepository->search($q);

    return $this->json(array_map(function (User $u) use ($me) {
        $followStatus = 'none';
        if ($me && $me->getId() !== $u->getId()) {
            $follow = $this->followRepo->findFollow($me, $u);
            if ($follow) {
                $followStatus = $follow->getStatus();
            }
        }
        return [
            'id'           => $u->getId(),
            'displayName'  => $u->getDisplayName(),
            'avatar'       => $u->getAvatar(),
            'bio'          => $u->getBio(),
            'followers'    => $this->followRepo->countFollowers($u),
            'followStatus' => $followStatus,
            'isMe'         => $me && $me->getId() === $u->getId(),
        ];
    }, $users));
}
```

**Detalles:**
- Mínimo de 2 caracteres para la búsqueda (evita consultas demasiado amplias).
- La búsqueda es **pública** — no requiere autenticación. Un visitante anónimo puede buscar usuarios.
- `followStatus` se calcula por cada resultado, lo que puede generar N consultas adicionales para N resultados. Es una deuda técnica aceptable dado que el límite es 20 resultados.
- `isMe: true` marca el propio usuario en los resultados, permitiendo al frontend ocultar el botón "Seguir" en ese caso.

**Query SQL generada por `UserRepository::search()`:**
```sql
SELECT u.*
FROM user u
WHERE LOWER(u.display_name) LIKE LOWER(:q)
ORDER BY u.display_name ASC
LIMIT 20
```

`LOWER()` en ambos lados garantiza búsqueda case-insensitive. El parámetro `q` se envuelve con `%...%` para búsqueda por subcadena: buscar `"mar"` encuentra `"MariaG"`, `"Tamara"`, etc.

---

## 9. `GET /api/my-requests` — solicitudes enviadas por el usuario

```php
#[Route('/my-requests', name: 'my_requests', methods: ['GET'])]
public function myRequests(ClubJoinRequestRepository $repo): JsonResponse
{
    $requests = $repo->findBy(['user' => $this->getUser()], ['requestedAt' => 'DESC']);

    return $this->json(array_map(fn(ClubJoinRequest $r) => [
        'id'          => $r->getId(),
        'status'      => $r->getStatus(),
        'requestedAt' => $r->getRequestedAt()?->format(\DateTimeInterface::ATOM),
        'club'        => [
            'id'         => $r->getClub()->getId(),
            'name'       => $r->getClub()->getName(),
            'visibility' => $r->getClub()->getVisibility(),
        ],
    ], $requests));
}
```

Lista todas las solicitudes de ingreso que el usuario ha enviado a clubes privados, con su estado actual (`pending`, `approved`, `rejected`). Permite al usuario ver en qué clubes tiene solicitudes pendientes.

**Respuesta:**
```json
[
  {
    "id": 12,
    "status": "pending",
    "requestedAt": "2026-04-15T10:00:00+00:00",
    "club": {
      "id": 3,
      "name": "Club Secreto",
      "visibility": "private"
    }
  }
]
```

---

## 10. `GET /api/admin-requests` — solicitudes pendientes en mis clubes (como admin)

```php
#[Route('/admin-requests', name: 'admin_requests', methods: ['GET'])]
public function adminRequests(ClubMemberRepository $memberRepo, ClubJoinRequestRepository $requestRepo): JsonResponse
{
    // Clubs donde el usuario es admin
    $memberships = $memberRepo->findBy(['user' => $this->getUser(), 'role' => 'admin']);

    $result = [];
    foreach ($memberships as $membership) {
        $club    = $membership->getClub();
        $pending = $requestRepo->findBy(['club' => $club, 'status' => 'pending']);

        foreach ($pending as $req) {
            $result[] = [
                'id'          => $req->getId(),
                'status'      => $req->getStatus(),
                'requestedAt' => $req->getRequestedAt()?->format(\DateTimeInterface::ATOM),
                'club'        => ['id' => $club->getId(), 'name' => $club->getName()],
                'user'        => [
                    'id'          => $req->getUser()->getId(),
                    'displayName' => $req->getUser()->getDisplayName() ?? $req->getUser()->getEmail(),
                ],
            ];
        }
    }

    return $this->json($result);
}
```

Este endpoint resuelve un problema de UX: un usuario puede ser admin de varios clubes privados. Para revisar todas las solicitudes pendientes en todos sus clubes, haría falta visitar cada club individualmente. Este endpoint consolida todas las solicitudes en una sola petición.

**Flujo:**
1. Busca todos los `ClubMember` donde el usuario tiene `role = 'admin'`.
2. Para cada club, busca las `ClubJoinRequest` con `status = 'pending'`.
3. Consolida los resultados en un único array plano.

**Respuesta:**
```json
[
  {
    "id": 88,
    "status": "pending",
    "requestedAt": "2026-04-19T09:00:00+00:00",
    "club": { "id": 3, "name": "Club Secreto" },
    "user": { "id": 15, "displayName": "PedroM" }
  }
]
```

---

## 11. Resumen de endpoints del perfil

| Método | Ruta | Autenticación | Descripción |
|--------|------|---------------|-------------|
| `GET` | `/api/profile` | Requerida | Perfil completo propio con privacidad |
| `PUT` | `/api/profile` | Requerida | Editar displayName y bio |
| `POST` | `/api/profile/avatar` | Requerida | Subir foto de perfil |
| `PUT` | `/api/profile/password` | Requerida | Cambiar contraseña con verificación |
| `PUT` | `/api/profile/privacy` | Requerida | Configurar flags de privacidad |
| `GET` | `/api/my-requests` | Requerida | Solicitudes de clubs enviadas |
| `GET` | `/api/admin-requests` | Requerida | Solicitudes pendientes en mis clubs |
| `GET` | `/api/users/search?q=` | Pública | Buscar usuarios por displayName |
| `GET` | `/api/users/{id}` | Pública | Perfil público con respeto a privacidad |

---

# 22 — Registro, sesión y SPA

Este documento cubre el ciclo completo de autenticación desde el registro de una cuenta nueva hasta el cierre de sesión, incluyendo la lógica de generación de `displayName`, el manejo de login/logout con handlers personalizados, y cómo el backend sirve la React SPA.

---

## 1. Registro — `POST /api/auth/register`

El registro está en `AuthApiController` y no requiere autenticación previa.

### 1.1 Validación de entrada

```php
$email       = trim((string) ($data['email'] ?? ''));
$password    = (string) ($data['password'] ?? '');
$displayName = trim((string) ($data['displayName'] ?? ''));

// 1. Campos obligatorios
if ($email === '' || $password === '') {
    return $this->json(['error' => 'email y password son obligatorios'], 400);
}

// 2. Formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return $this->json(['error' => 'El email no es válido'], 400);
}

// 3. Longitud de contraseña
if (strlen($password) < 6) {
    return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
}

// 4. Email único en BD
if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
    return $this->json(['error' => 'Ya existe una cuenta con ese email'], 409);
}
```

El email se valida con `filter_var($email, FILTER_VALIDATE_EMAIL)`, la función nativa de PHP para validación de emails según RFC 822. No se usan expresiones regulares manuales.

### 1.2 Generación automática de `displayName` único

El campo `displayName` es obligatorio en la BD (restricción `NOT NULL`) y debe ser único. Sin embargo, el registro no lo exige al cliente — se genera automáticamente si no se proporciona:

```php
// Usar displayName del cliente o derivarlo del email
$base = $displayName !== ''
    ? preg_replace('/[^a-zA-Z0-9_]/', '', $displayName) ?: strstr($email, '@', true)
    : strstr($email, '@', true);

// Fallback final si la base queda vacía
$base = preg_replace('/[^a-zA-Z0-9_]/', '', $base) ?: 'usuario';

// Iterar hasta encontrar un nombre libre
$candidate = $base;
$suffix    = 1;
while ($em->getRepository(User::class)->findOneBy(['displayName' => $candidate])) {
    $candidate = $base . $suffix;
    $suffix++;
}

$user->setDisplayName($candidate);
```

**Algoritmo paso a paso:**

1. Si el cliente envió `displayName`, se sanitiza eliminando caracteres no permitidos con `preg_replace('/[^a-zA-Z0-9_]/', '', ...)`.
2. Si el cliente no envió `displayName` (o quedó vacío tras sanitizar), se usa la parte local del email: `strstr('maria@gmail.com', '@', true)` → `'maria'`.
3. El fallback final es `'usuario'` si todo lo anterior resultara vacío (email con caracteres inválidos en la parte local, caso extremadamente raro).
4. Se busca en BD si el candidato ya existe. Si existe, se añade un sufijo numérico incremental: `maria` → `maria1` → `maria2`, etc.

**Ejemplos:**

| Email | displayName enviado | displayName resultante |
|-------|--------------------|-----------------------|
| `maria@gmail.com` | (vacío) | `maria` (o `maria1` si ya existe) |
| `user@test.com` | `"María López"` | `MaraLpez` (sin tildes ni espacios) |
| `juan@test.com` | (vacío) | `juan` (o `juan1`, `juan2`, ...) |

### 1.3 Creación del usuario

```php
$user = new User();
$user->setEmail($email);
$user->setPassword($hasher->hashPassword($user, $password));
$user->setIsVerified(true);
$user->setDisplayName($candidate);

$em->persist($user);
$em->flush();
```

`setIsVerified(true)` marca la cuenta como verificada directamente. El sistema tiene soporte técnico para verificación por email (`EmailVerifier.php` existe en el código), pero para el TFG se omite ese paso para simplificar el flujo de onboarding.

**Respuesta `201 Created`:**
```json
{
  "id": 42,
  "email": "maria@gmail.com"
}
```

Solo se devuelve `id` y `email`. El cliente debe hacer `POST /api/login` a continuación para iniciar sesión.

---

## 2. Login — `POST /api/login`

El login no está implementado en `AuthApiController` sino que lo gestiona **Symfony directamente** a través del mecanismo `json_login` configurado en `security.yaml`:

```yaml
# security.yaml
firewalls:
  main:
    json_login:
      check_path: /api/login
      username_path: email
      password_path: password
      success_handler: App\Security\JsonLoginSuccessHandler
      failure_handler: App\Security\JsonLoginFailureHandler
```

Symfony intercepta `POST /api/login` antes de que llegue a ningún controlador. El flujo es:

```
POST /api/login
{ "email": "...", "password": "..." }
         │
         ▼
Symfony Security: extrae email + password del JSON
         │
         ▼
Busca User por email en BD (via UserRepository)
         │
         ├── No encontrado → JsonLoginFailureHandler → 401
         │
         ▼
Verifica password con UserPasswordHasher
         │
         ├── Incorrecto → JsonLoginFailureHandler → 401
         │
         ▼
Crea sesión PHP + cookie de sesión
         │
         ▼
JsonLoginSuccessHandler → 200 con datos del usuario
```

### 2.1 `JsonLoginSuccessHandler`

```php
class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        return new JsonResponse([
            'id'          => $user->getId(),
            'email'       => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'avatar'      => $user->getAvatar(),
            'roles'       => $user->getRoles(),
        ]);
    }
}
```

Implementa `AuthenticationSuccessHandlerInterface`. Cuando Symfony verifica las credenciales correctamente, llama a `onAuthenticationSuccess()`. La sesión ya fue creada por Symfony antes de llamar al handler.

El handler extrae el usuario del `TokenInterface` (que ya contiene el objeto `User` autenticado) y devuelve los datos básicos necesarios para que el frontend inicialice su estado.

### 2.2 `JsonLoginFailureHandler`

```php
class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Credenciales incorrectas'],
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }
}
```

Devuelve siempre el mismo mensaje genérico `'Credenciales incorrectas'` independientemente de si el email no existe o la contraseña es incorrecta. Esto es una práctica de seguridad: no revelar si el email está registrado o no.

---

## 3. Consulta de sesión — `GET /api/auth/me`

```php
#[Route('/me', name: 'me', methods: ['GET'])]
public function me(): JsonResponse
{
    $user = $this->getUser();

    if (!$user) {
        return $this->json(['error' => 'No autenticado'], 401);
    }

    return $this->json([
        'id'          => $user->getId(),
        'email'       => $user->getEmail(),
        'displayName' => $user->getDisplayName(),
        'avatar'      => $user->getAvatar(),
        'roles'       => $user->getRoles(),
    ]);
}
```

El frontend llama a este endpoint al cargar la aplicación para saber si hay una sesión activa. `getUser()` devuelve `null` si no hay sesión, o el objeto `User` si la hay.

La sesión se identifica por una **cookie de sesión** (PHP session) que el navegador envía automáticamente. No hay tokens JWT.

---

## 4. Logout — `POST /api/auth/logout`

```php
#[Route('/logout', name: 'logout', methods: ['POST'])]
public function logout(Request $request): JsonResponse
{
    $request->getSession()->invalidate();
    return $this->json(['status' => 'logged_out']);
}
```

`$request->getSession()->invalidate()` destruye la sesión PHP del servidor y genera un nuevo ID de sesión vacío. La cookie de sesión del cliente apuntará a una sesión inválida en el siguiente request.

**Respuesta:**
```json
{ "status": "logged_out" }
```

---

## 5. Sesiones PHP vs JWT

TFGdaw usa sesiones PHP en lugar de JWT. La diferencia principal:

| Aspecto | Sesiones PHP | JWT |
|---------|-------------|-----|
| Estado | **Stateful** — el servidor guarda la sesión | **Stateless** — toda la info en el token |
| Almacenamiento | Fichero o BD en el servidor | Solo en el cliente |
| Invalidación | Instantánea (`invalidate()`) | Requiere lista negra |
| CSRF | Protección necesaria | No aplica si se envía por header |
| Escalabilidad | Necesita sesión compartida si hay múltiples servidores | Sin problema |
| Complejidad | Simple con Symfony | Requiere librería y gestión de refresh tokens |

Para un TFG con un solo servidor, las sesiones PHP son la opción más sencilla y segura.

---

## 6. `SpaController` — servir la React SPA

```php
#[Route('/{any}', name: 'spa_fallback', requirements: ['any' => '^(?!api/).*'], priority: -10)]
public function index(): Response
{
    $indexPath = $this->getParameter('kernel.project_dir') . '/public/app/index.html';

    if (!file_exists($indexPath)) {
        return new Response(
            '<!-- página de error HTML indicando que hay que ejecutar npm run build -->',
            200,
            ['Content-Type' => 'text/html']
        );
    }

    return new Response(
        file_get_contents($indexPath),
        200,
        ['Content-Type' => 'text/html; charset=UTF-8']
    );
}
```

### 6.1 Cómo funciona el enrutamiento SPA

El patrón `/{any}` con `requirements: ['any' => '^(?!api/).*']` captura todas las rutas que **no empiecen por `api/`**:

- `GET /` → devuelve `index.html` ✓
- `GET /feed` → devuelve `index.html` ✓
- `GET /clubs/5` → devuelve `index.html` ✓
- `GET /api/posts` → no capturado (lo gestiona `PostApiController`) ✗

El atributo `priority: -10` garantiza que esta ruta tiene la prioridad más baja. Symfony evalúa las rutas en orden de prioridad; si ningún controlador API coincide, el SpaController captura la petición.

### 6.2 Por qué es necesario este patrón

En una SPA de React con React Router, las rutas como `/clubs/5` o `/feed` son **rutas del cliente**, no del servidor. Cuando el usuario navega directamente a `localhost:8000/clubs/5` o recarga la página en esa URL, el servidor recibe la petición y debe responder con el mismo `index.html` para que React Router tome el control.

Sin `SpaController`, el servidor devolvería un 404 al no encontrar ningún recurso en `/clubs/5`.

### 6.3 Comportamiento si el frontend no está compilado

Si `public/app/index.html` no existe (frontend no compilado), el controlador devuelve una página HTML de error con instrucciones:

```html
<div class="box">
  <h1>📚 TFGdaw</h1>
  <p>El frontend React no está compilado todavía.</p>
  <p>Ejecuta en la carpeta <code>frontend/</code>:</p>
  <p><code>npm install && npm run build</code></p>
  <p>O durante desarrollo: <code>npm run dev</code> → localhost:5173</p>
</div>
```

Esta página tiene estilos inline mínimos con el color morado del design system de la aplicación. Se devuelve con código `200` (no `500`) porque no es un error del servidor sino una advertencia de configuración.

### 6.4 Entornos de desarrollo vs producción

| Modo | Cómo funciona el frontend |
|------|--------------------------|
| **Desarrollo** | `npm run dev` en `/frontend` → servidor Vite en `localhost:5173`. El SpaController no se usa, el frontend hace proxy de las peticiones API a `localhost:8000` |
| **Producción / demo** | `npm run build` copia la build a `public/app/`. El SpaController sirve `index.html` para todas las rutas |

---

## 7. Resumen del flujo completo de autenticación

```
Registro
    POST /api/auth/register
    { email, password, displayName? }
         │
         ▼
    Validar → Generar displayName único → Hash password → User en BD
         │
         ▼
    { id, email }  ← 201 Created

Login
    POST /api/login
    { email, password }
         │
         ▼
    Symfony verifica credenciales
         │
    ┌────┴────┐
    │         │
  ERROR    ÉXITO
    │         │
    ▼         ▼
   401    Crea sesión PHP
         cookie PHPSESSID
         JsonLoginSuccessHandler
         { id, email, displayName, avatar, roles }

Requests autenticados
    GET /api/perfil  Cookie: PHPSESSID=abc123
         │
         ▼
    Symfony valida cookie → Carga User de sesión
         │
         ▼
    Respuesta normal

Logout
    POST /api/auth/logout
         │
         ▼
    session->invalidate()
    { status: 'logged_out' }
```

---

# 23 — Repositorios: detalle de consultas personalizadas

Este documento cubre los métodos personalizados de los repositorios que no fueron explicados en profundidad en documentos anteriores: `NotificationRepository`, `FollowRepository`, `UserRepository` y el comportamiento base de `ServiceEntityRepository`.

---

## 1. Base común: `ServiceEntityRepository`

Todos los repositorios de la aplicación extienden `ServiceEntityRepository<T>`, que a su vez extiende `EntityRepository`. Esto proporciona de forma gratuita:

| Método | Descripción |
|--------|-------------|
| `find($id)` | Busca por clave primaria. Devuelve `?T`. |
| `findAll()` | Devuelve todos los registros. |
| `findBy(criteria, orderBy, limit, offset)` | Búsqueda por criterios simples. |
| `findOneBy(criteria)` | Igual que `findBy` pero devuelve solo el primero. |
| `count(criteria)` | `SELECT COUNT(*) WHERE criteria` sin cargar objetos. |
| `createQueryBuilder(alias)` | Inicia un `QueryBuilder` para consultas DQL complejas. |

Los repositorios personalizados solo añaden métodos cuando la lógica no se puede expresar con los métodos base.

---

## 2. `NotificationRepository`

### 2.1 `findForUser()` — ventana de 72 horas

```php
public function findForUser(User $user, int $limit = 30): array
{
    $since = new \DateTimeImmutable('-72 hours');

    return $this->createQueryBuilder('n')
        ->where('n.recipient = :user')
        ->andWhere('n.createdAt >= :since')
        ->setParameter('user', $user)
        ->setParameter('since', $since)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

Devuelve las notificaciones de las últimas 72 horas, limitadas a 30. Esta ventana temporal es la consulta principal que usa el badge de notificaciones del frontend.

`new \DateTimeImmutable('-72 hours')` crea un objeto de fecha relativo al momento de ejecución. La sintaxis de modificador de PHP acepta strings como `'-72 hours'`, `'-3 days'`, `'+1 week'`, etc.

### 2.2 `findAllForUser()` — historial completo

```php
public function findAllForUser(User $user, int $limit = 100): array
{
    return $this->createQueryBuilder('n')
        ->where('n.recipient = :user')
        ->setParameter('user', $user)
        ->orderBy('n.createdAt', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

Sin límite temporal, hasta 100 notificaciones. Se usa para el historial completo (`GET /api/notifications/history`).

### 2.3 `countUnread()` — conteo de no leídas

```php
public function countUnread(User $user): int
{
    return (int) $this->createQueryBuilder('n')
        ->select('COUNT(n.id)')
        ->where('n.recipient = :user AND n.isRead = false')
        ->setParameter('user', $user)
        ->getQuery()
        ->getSingleScalarResult();
}
```

`getSingleScalarResult()` devuelve un único valor escalar (el COUNT) sin crear objetos. Es la forma más eficiente de obtener un número de la BD.

El resultado es `string` en PHP (Doctrine lo recibe del driver como string), por lo que se castea a `int`.

### 2.4 `markAllRead()` — actualización masiva sin cargar objetos

```php
public function markAllRead(User $user): void
{
    $this->createQueryBuilder('n')
        ->update()
        ->set('n.isRead', 'true')
        ->where('n.recipient = :user AND n.isRead = false')
        ->setParameter('user', $user)
        ->getQuery()
        ->execute();
}
```

`->update()` genera un `UPDATE` directo en BD sin cargar los objetos en memoria. Actualiza potencialmente decenas de registros en una sola query:

```sql
UPDATE notification
SET is_read = true
WHERE recipient_id = ? AND is_read = false
```

Comparado con la alternativa naive:
```php
// MAL: carga todos los objetos, hace N flush
foreach ($notifications as $n) {
    $n->setIsRead(true);
}
$em->flush();
```

La versión con `->update()` es O(1) en consultas independientemente del número de notificaciones.

### 2.5 `deleteByRefIdAndType()` — limpieza tras procesar solicitudes

```php
public function deleteByRefIdAndType(User $recipient, string $type, int $refId): void
{
    $this->createQueryBuilder('n')
        ->delete()
        ->where('n.recipient = :recipient AND n.type = :type AND n.refId = :refId')
        ->setParameter('recipient', $recipient)
        ->setParameter('type', $type)
        ->setParameter('refId', $refId)
        ->getQuery()
        ->execute();
}
```

`->delete()` genera un `DELETE` directo. Se usa tras aceptar o rechazar una solicitud de seguimiento:

```php
// Tras aceptar el follow:
$this->repo->deleteByRefIdAndType($me, Notification::TYPE_FOLLOW_REQUEST, $followId);
```

Esto elimina la notificación `follow_request` con ese `refId` específico (el ID del `Follow`). Si el usuario tiene múltiples solicitudes pendientes, solo se elimina la procesada.

---

## 3. `FollowRepository`

### 3.1 `findFollow()` — estado del follow entre dos usuarios

```php
public function findFollow(User $follower, User $following): ?Follow
{
    return $this->findOneBy(['follower' => $follower, 'following' => $following]);
}
```

Devuelve el registro `Follow` independientemente de su estado (`pending` o `accepted`). Se usa en múltiples lugares:

- En `GET /api/users/{id}` para calcular `followStatus`
- En `GET /api/users/search` para calcular `followStatus` por cada resultado
- En `POST /api/users/{id}/follow` para comprobar si ya existe un follow

### 3.2 `countFollowers()` y `countFollowing()`

```php
public function countFollowers(User $user): int
{
    return $this->count(['following' => $user, 'status' => Follow::STATUS_ACCEPTED]);
}

public function countFollowing(User $user): int
{
    return $this->count(['follower' => $user, 'status' => Follow::STATUS_ACCEPTED]);
}
```

Ambos usan el método `count()` heredado de `ServiceEntityRepository`, que internamente genera `SELECT COUNT(*) WHERE criteria`. Solo se cuentan los follows con `status = 'accepted'` — los pendientes no se incluyen en los contadores públicos.

### 3.3 `findFollowers()` y `findFollowing()`

```php
public function findFollowers(User $user): array
{
    return $this->findBy(
        ['following' => $user, 'status' => Follow::STATUS_ACCEPTED],
        ['createdAt' => 'DESC']
    );
}

public function findFollowing(User $user): array
{
    return $this->findBy(
        ['follower' => $user, 'status' => Follow::STATUS_ACCEPTED],
        ['createdAt' => 'DESC']
    );
}
```

Ambas devuelven objetos `Follow` ordenados por fecha de seguimiento, de más reciente a más antiguo. El controlador itera sobre ellos para construir la respuesta con datos del usuario.

### 3.4 `findIncomingRequests()` y `countIncomingRequests()`

```php
public function findIncomingRequests(User $user): array
{
    return $this->findBy(
        ['following' => $user, 'status' => Follow::STATUS_PENDING],
        ['createdAt' => 'DESC']
    );
}

public function countIncomingRequests(User $user): int
{
    return $this->count(['following' => $user, 'status' => Follow::STATUS_PENDING]);
}
```

Solo relevantes para cuentas privadas. Devuelven/cuentan las solicitudes de seguimiento pendientes de aprobación.

---

## 4. `UserRepository`

### 4.1 `search()` — búsqueda case-insensitive por subcadena

```php
public function search(string $q, int $limit = 20): array
{
    return $this->createQueryBuilder('u')
        ->where('LOWER(u.displayName) LIKE LOWER(:q)')
        ->setParameter('q', '%' . $q . '%')
        ->orderBy('u.displayName', 'ASC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

`LOWER()` en ambos lados de la comparación garantiza que buscar `"mar"` encuentre `"MariaG"`, `"MARIO"` o `"tamara"`. El parámetro se envuelve con `%...%` para búsqueda de subcadena.

**SQL generado:**
```sql
SELECT u.*
FROM user u
WHERE LOWER(u.display_name) LIKE LOWER('%mar%')
ORDER BY u.display_name ASC
LIMIT 20
```

### 4.2 `upgradePassword()` — rehashing automático

```php
public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
{
    $user->setPassword($newHashedPassword);
    $this->getEntityManager()->persist($user);
    $this->getEntityManager()->flush();
}
```

`UserRepository` implementa `PasswordUpgraderInterface`. Symfony llama automáticamente a este método cuando detecta que el hash de la contraseña de un usuario fue generado con un algoritmo más antiguo. Actualiza el hash al algoritmo actual sin requerir que el usuario cambie su contraseña.

---

## 5. `BookReviewRepository`

### 5.1 `findOneByUserAndBook()`

```php
public function findOneByUserAndBook(User $user, Book $book): ?BookReview
{
    return $this->findOneBy(['user' => $user, 'book' => $book]);
}
```

Método de conveniencia que encapsula la restricción única `(user, book)`. Se usa tanto en la lectura (para obtener `myRating`) como en el upsert (para decidir si crear o actualizar).

### 5.2 `findByBook()`

```php
public function findByBook(Book $book): array
{
    return $this->createQueryBuilder('r')
        ->join('r.user', 'u')
        ->addSelect('u')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->orderBy('r.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

Eager loading del usuario de cada reseña (JOIN + addSelect). Devuelve las reseñas de más reciente a más antigua. Solo incluye reseñas con `content` no nulo (reseñas con texto) — las puramente numéricas no aparecen en el listado público.

### 5.3 `getStats()` — media, total y distribución

```php
public function getStats(Book $book): array
{
    // Media y total global
    $row = $this->createQueryBuilder('r')
        ->select('AVG(r.rating) AS avg, COUNT(r.id) AS total')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->getQuery()
        ->getOneOrNullResult();

    // Distribución por estrella
    $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $rows = $this->createQueryBuilder('r')
        ->select('r.rating, COUNT(r.id) AS cnt')
        ->where('r.book = :book')
        ->setParameter('book', $book)
        ->groupBy('r.rating')
        ->getQuery()
        ->getResult();

    foreach ($rows as $r) {
        $dist[(int) $r['rating']] = (int) $r['cnt'];
    }

    return [
        'average'      => $row['avg'] ? round((float) $row['avg'], 1) : null,
        'count'        => (int) ($row['total'] ?? 0),
        'distribution' => $dist,
    ];
}
```

Dos consultas en lugar de cargar todos los objetos:

1. **Primera consulta:** `AVG + COUNT` para la media y el total.
2. **Segunda consulta:** `GROUP BY rating` para la distribución por estrellas.

`$dist` se inicializa con `[1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]` para garantizar que todas las estrellas aparezcan en la respuesta aunque tengan cero reseñas.

---

## 6. Otros repositorios

### 6.1 `ShelfBookRepository`

Solo usa `findOneBy()` heredado para buscar si un libro ya está en una estantería:

```php
// En ShelfApiController
$existing = $shelfBookRepo->findOneBy(['shelf' => $shelf, 'book' => $book]);
```

### 6.2 `ReadingProgressRepository`

Solo usa `findOneBy()` para la restricción única `(user, book)`:

```php
$existing = $this->repo->findOneBy(['user' => $this->getUser(), 'book' => $book]);
```

### 6.3 `ClubRepository`

`findBy([], ['id' => 'DESC'])` para el panel de admin. Para el listado público:

```php
// En ClubApiController - filtra por visibilidad o membresía
$clubs = $clubRepo->findBy(['visibility' => 'public']);
```

### 6.4 `ClubJoinRequestRepository`

```php
// findPendingWithUser — eager loading del usuario solicitante
public function findPendingWithUser(Club $club): array
{
    return $this->createQueryBuilder('r')
        ->join('r.user', 'u')
        ->addSelect('u')
        ->where('r.club = :club AND r.status = :pending')
        ->setParameter('club', $club)
        ->setParameter('pending', 'pending')
        ->getQuery()
        ->getResult();
}
```

Eager loading para evitar N+1 al listar solicitudes pendientes de un club.

### 6.5 `ClubChatRepository`

```php
public function findByClubWithCreator(Club $club): array
{
    return $this->createQueryBuilder('c')
        ->join('c.createdBy', 'u')
        ->addSelect('u')
        ->where('c.club = :club')
        ->setParameter('club', $club)
        ->orderBy('c.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

Precarga el `createdBy` (User) de cada hilo para evitar una consulta extra por hilo al serializar la respuesta.

---

## 7. Tabla resumen de métodos DQL avanzados

| Patrón | Método Doctrine | Cuándo usarlo |
|--------|----------------|---------------|
| Conteo sin cargar objetos | `count(criteria)` / `COUNT(m.id)` + `getSingleScalarResult()` | Cuando solo necesitas el número |
| Actualización masiva | `createQueryBuilder()->update()->set()->where()` | Para UPDATE de múltiples filas |
| Eliminación masiva | `createQueryBuilder()->delete()->where()` | Para DELETE de múltiples filas sin cargar |
| Eager loading | `->join('r.user', 'u')->addSelect('u')` | Prevenir N+1 al acceder a relaciones |
| Paginación | `->setFirstResult(offset)->setMaxResults(limit)` | Listas largas con páginas |
| Agrupación | `->groupBy('campo')->select('campo, COUNT(id)')` | Estadísticas y distribuciones |
| FK sin JOIN | `IDENTITY(m.club)` en DQL | Obtener el ID de una FK sin hacer JOIN |
| Búsqueda case-insensitive | `LOWER(campo) LIKE LOWER(:q)` | Búsquedas de texto |

---

# 24 — Controlador de Posts: análisis completo

`PostApiController` gestiona todo el contenido publicado en la plataforma: publicaciones con imagen, el feed personalizado, el sistema de likes y los comentarios. Es el controlador central del módulo social.

---

## 1. Estructura del controlador

```php
#[Route('/api', name: 'api_posts_')]
class PostApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private PostLikeRepository $likeRepo,
        private PostCommentRepository $commentRepo,
    ) {}
```

Las cuatro dependencias cubren todas las operaciones:
- `$em` para persistir y eliminar entidades.
- `$postRepo` para buscar posts (feed, por usuario, por ID).
- `$likeRepo` para verificar si un like existe y contar likes.
- `$commentRepo` para listar y contar comentarios.

---

## 2. `GET /api/posts` — feed personalizado

```php
#[Route('/posts', name: 'feed', methods: ['GET'])]
public function feed(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me    = $this->getUser();
    $posts = $this->postRepo->findFeed($me, 40);

    return $this->json(array_map(fn(Post $p) => $this->serialize($p, $me), $posts));
}
```

**Qué devuelve:** hasta 40 posts ordenados de más reciente a más antiguo, incluyendo los propios y los de usuarios a los que sigue con `status = 'accepted'`.

La consulta está en `PostRepository::findFeed()` (ver [23-repositorios-detalle.md](23-repositorios-detalle.md)):

```sql
SELECT p.*
FROM post p
LEFT JOIN follow f ON f.follower_id = :me
                  AND f.following_id = p.user_id
                  AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

El LEFT JOIN garantiza que los posts propios aparezcan incluso si el usuario no sigue a nadie. Si el resultado del join es `NULL` (`f.id IS NOT NULL` falla), solo se incluye el post si el autor es el propio usuario.

---

## 3. `GET /api/users/{id}/posts` — posts de un usuario concreto

```php
#[Route('/users/{id}/posts', name: 'by_user', requirements: ['id' => '\d+'], methods: ['GET'])]
public function byUser(int $id, UserRepository $userRepo): JsonResponse
{
    $user = $userRepo->find($id);
    if (!$user) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }

    $me    = $this->getUser();
    $posts = $this->postRepo->findByUser($user);

    return $this->json(array_map(fn(Post $p) => $this->serialize($p, $me), $posts));
}
```

Este endpoint es **público** — no llama a `denyAccessUnlessGranted`. Cualquier visitante puede ver los posts de cualquier usuario. La privacidad (ocultar posts de cuentas privadas a no seguidores) la gestiona el **frontend** comprobando `followStatus` e `isPrivate` del perfil.

`$me = $this->getUser()` puede ser `null` si no hay sesión activa. El helper `serialize()` lo acepta y en ese caso `liked` siempre es `false`.

---

## 4. `POST /api/posts` — crear publicación

```php
#[Route('/posts', name: 'create', methods: ['POST'])]
public function create(Request $request): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $file = $request->files->get('image');
    if (!$file) {
        return $this->json(['error' => 'Se requiere una imagen'], 400);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower($file->guessExtension() ?? '');
    if (!in_array($ext, $allowed, true)) {
        return $this->json(['error' => 'Formato de imagen no permitido'], 400);
    }

    $filename  = uniqid('post_', true) . '.' . $ext;
    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
    $file->move($uploadDir, $filename);

    $description = trim((string) ($request->request->get('description', ''))) ?: null;

    $post = new Post($me, $filename, $description);
    $this->em->persist($post);
    $this->em->flush();

    return $this->json($this->serialize($post, $me), 201);
}
```

**Paso a paso:**

**Paso 1 — Obtener el archivo:**
`$request->files->get('image')` extrae el archivo del campo `image` del formulario `multipart/form-data`. Si no se envió ningún archivo, el objeto es `null` y se devuelve 400.

**Paso 2 — Validar la extensión por contenido real:**
`$file->guessExtension()` usa `finfo_file()` internamente para detectar el tipo MIME real del archivo analizando su cabecera binaria (magic bytes), **no** por la extensión del nombre. Esto impide que alguien suba un archivo malicioso renombrándolo con extensión `.jpg`.

| Tipo MIME real | `guessExtension()` devuelve |
|----------------|----------------------------|
| `image/jpeg` | `jpg` |
| `image/png` | `png` |
| `image/gif` | `gif` |
| `image/webp` | `webp` |
| `application/pdf` | `pdf` → rechazado |

**Paso 3 — Generar nombre único:**
`uniqid('post_', true)` genera un identificador único con:
- Prefijo `post_` para identificar el tipo de recurso.
- `true` activa la entropía adicional con microsegundos decimales, lo que hace colisiones prácticamente imposibles incluso en servidores de alta concurrencia.

Ejemplo de nombre resultante: `post_66f2a3c1d4e9f.123456.jpg`

**Paso 4 — Mover el archivo:**
`$file->move($uploadDir, $filename)` mueve el archivo del directorio temporal de PHP (`/tmp`) al directorio permanente de uploads. Si el directorio no existe, lanza una excepción.

**Paso 5 — Leer la descripción:**
`$request->request->get('description', '')` lee el campo de texto del formulario multipart (no del body JSON, porque la petición es `multipart/form-data`). El operador `?: null` convierte string vacío a `null` para almacenarlo como `NULL` en BD.

**Paso 6 — Crear la entidad:**
`new Post($me, $filename, $description)` usa el constructor con parámetros (Estilo 1 de los patrones, ver [17-patrones-codigo.md](17-patrones-codigo.md)). El constructor inicializa `createdAt`, `likes` y `comments` automáticamente.

---

## 5. `DELETE /api/posts/{id}` — eliminar post

```php
#[Route('/posts/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me   = $this->getUser();
    $post = $this->postRepo->find($id);

    $isGlobalAdmin = $this->isGranted('ROLE_ADMIN');
    if (!$post || ($post->getUser()->getId() !== $me->getId() && !$isGlobalAdmin)) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    $imgPath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImagePath();
    if (file_exists($imgPath)) {
        @unlink($imgPath);
    }

    $this->em->remove($post);
    $this->em->flush();

    return $this->json(null, 204);
}
```

**Quién puede eliminar un post:**
1. El **autor** del post (`$post->getUser()->getId() !== $me->getId()` es `false`).
2. Un usuario con **`ROLE_ADMIN`** (`$isGlobalAdmin` es `true`).

La condición `!$post || (... && !$isGlobalAdmin)` devuelve 404 en todos los casos de fallo, incluido el caso donde el post existe pero no pertenece al usuario. Esto sigue el patrón de no revelar información sobre recursos ajenos.

**Eliminación del archivo en disco:**
Antes de llamar a `$em->remove()`, se borra el archivo de imagen:
1. `file_exists($imgPath)` comprueba que el archivo existe para evitar un error si ya fue eliminado manualmente.
2. `@unlink($imgPath)` borra el archivo. El `@` suprime el error si ocurre una condición de carrera entre la comprobación y el borrado.

La entidad se elimina de BD independientemente del resultado del `unlink`. Es mejor tener un registro BD huérfano (sin imagen) que una imagen huérfana (sin registro BD), ya que los archivos sin registro no se pueden gestionar desde la aplicación.

**Efecto en cascada:**
La entidad `Post` tiene `orphanRemoval: true` en sus colecciones `likes` y `comments`. Al eliminar el `Post`, Doctrine elimina automáticamente todos sus `PostLike` y `PostComment`.

---

## 6. `POST /api/posts/{id}/like` — toggle de like

```php
#[Route('/posts/{id}/like', name: 'like', requirements: ['id' => '\d+'], methods: ['POST'])]
public function like(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me   = $this->getUser();
    $post = $this->postRepo->find($id);

    if (!$post) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    $existing = $this->likeRepo->findByPostAndUser($post, $me);
    if ($existing) {
        // Ya tiene like → quitar
        $this->em->remove($existing);
        $this->em->flush();
        return $this->json(['liked' => false, 'likes' => $this->likeRepo->countByPost($post)]);
    }

    // No tiene like → añadir
    $this->em->persist(new PostLike($post, $me));
    $this->em->flush();

    return $this->json(['liked' => true, 'likes' => $this->likeRepo->countByPost($post)]);
}
```

**Patrón toggle:** Un único endpoint `POST` actúa como interruptor. Si ya existe un like del usuario para ese post, lo elimina; si no existe, lo crea. No hay endpoint separado para "quitar like".

**`countByPost()` después del flush:**
El conteo se hace **después** del `flush()`, ya que hasta entonces la operación no está confirmada en BD. Usando `countByPost()` después del flush se obtiene el recuento correcto que ya incluye o excluye el like recién modificado.

> **Nota sobre concurrencia:** La tabla `post_like` tiene una restricción `UNIQUE (post_id, user_id)`. Si dos peticiones simultáneas del mismo usuario intentan crear el mismo like, una fallará con error de unicidad. Esto protege contra doble click en el frontend aunque haya latencia de red.

**Respuesta en ambos casos:**
```json
// Al añadir
{ "liked": true,  "likes": 9 }

// Al quitar
{ "liked": false, "likes": 8 }
```

El frontend actualiza el contador y el estado del botón con este único dato.

---

## 7. `GET /api/posts/{id}/comments` — listar comentarios

```php
#[Route('/posts/{id}/comments', name: 'comments_list', requirements: ['id' => '\d+'], methods: ['GET'])]
public function listComments(int $id): JsonResponse
{
    $post = $this->postRepo->find($id);
    if (!$post) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    return $this->json(array_map(
        fn(PostComment $c) => $this->serializeComment($c),
        $this->commentRepo->findByPost($post)
    ));
}
```

Endpoint **público** (sin `denyAccessUnlessGranted`). Los comentarios se ordenan cronológicamente de más antiguo a más reciente (orden natural de conversación), a diferencia del feed que va de más reciente a más antiguo.

---

## 8. `POST /api/posts/{id}/comments` — añadir comentario

```php
#[Route('/posts/{id}/comments', name: 'comments_create', requirements: ['id' => '\d+'], methods: ['POST'])]
public function addComment(int $id, Request $request): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $post = $this->postRepo->find($id);
    if (!$post) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    $data    = json_decode($request->getContent(), true) ?? [];
    $content = trim((string) ($data['content'] ?? ''));
    if ($content === '') {
        return $this->json(['error' => 'El comentario no puede estar vacío'], 400);
    }

    $comment = new PostComment($post, $me, $content);
    $this->em->persist($comment);
    $this->em->flush();

    return $this->json($this->serializeComment($comment), 201);
}
```

`trim()` elimina espacios en blanco al inicio y al final. Un comentario con solo espacios (`"   "`) queda vacío tras el trim y se rechaza con 400. Esta validación evita comentarios que aparecen en blanco en la interfaz.

`new PostComment($post, $me, $content)` usa el constructor parametrizado. El `createdAt` se inicializa automáticamente dentro del constructor.

---

## 9. `DELETE /api/posts/{id}/comments/{commentId}` — eliminar comentario

```php
#[Route('/posts/{id}/comments/{commentId}', ...)]
public function deleteComment(int $id, int $commentId): JsonResponse
{
    $post    = $this->postRepo->find($id);
    $comment = $this->commentRepo->find($commentId);

    if (!$post || !$comment || $comment->getPost()->getId() !== $post->getId()) {
        return $this->json(['error' => 'Comentario no encontrado'], 404);
    }

    $isOwner       = $comment->getUser()->getId() === $me->getId();
    $isPostOwner   = $post->getUser()->getId() === $me->getId();
    $isGlobalAdmin = $this->isGranted('ROLE_ADMIN');

    if (!$isOwner && !$isPostOwner && !$isGlobalAdmin) {
        return $this->json(['error' => 'Sin permisos para eliminar este comentario'], 403);
    }

    $this->em->remove($comment);
    $this->em->flush();

    return $this->json(null, 204);
}
```

**Quién puede eliminar un comentario:**
- El **autor del comentario** (`$isOwner`).
- El **autor del post** (`$isPostOwner`) — puede moderar los comentarios en su propia publicación.
- Un usuario con **`ROLE_ADMIN`** (`$isGlobalAdmin`) — puede eliminar cualquier comentario en cualquier post.

La validación triple `!$post || !$comment || $comment->getPost()->getId() !== $post->getId()` garantiza tres cosas:
1. El post existe.
2. El comentario existe.
3. El comentario pertenece a ese post (evita borrar un comentario de otro post con el mismo ID).

Este es uno de los pocos endpoints del proyecto que devuelve **403 en lugar de 404** para el caso de permisos denegados, porque aquí es razonable que el usuario sepa que el comentario existe pero no tiene permiso para borrarlo.

---

## 10. Helper `serialize()` — formato de un post

```php
private function serialize(Post $post, mixed $me): array
{
    $u     = $post->getUser();
    $liked = $me ? $this->likeRepo->findByPostAndUser($post, $me) !== null : false;

    return [
        'id'           => $post->getId(),
        'imagePath'    => $post->getImagePath(),
        'description'  => $post->getDescription(),
        'createdAt'    => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'likes'        => $this->likeRepo->countByPost($post),
        'liked'        => $liked,
        'commentCount' => $this->commentRepo->count(['post' => $post]),
        'user'         => [
            'id'          => $u->getId(),
            'displayName' => $u->getDisplayName() ?? $u->getEmail(),
            'avatar'      => $u->getAvatar(),
        ],
    ];
}
```

**Dos consultas por post serializado:**
1. `$this->likeRepo->countByPost($post)` — `SELECT COUNT(*) WHERE post_id = ?`
2. `$this->commentRepo->count(['post' => $post])` — `SELECT COUNT(*) WHERE post_id = ?`

Si `$me` no es null, hay una tercera: `findByPostAndUser()` para saber si el usuario actual dio like.

Para un feed de 40 posts, esto supone hasta 120 consultas adicionales. Es una deuda técnica conocida; la solución óptima sería una consulta con subconsultas o JOINes para obtener todos los datos en una sola pasada. Para el alcance del TFG es aceptable.

**`$u->getDisplayName() ?? $u->getEmail()`:** Fallback al email si el `displayName` es null. En la práctica nunca debería ser null (el registro siempre genera uno), pero este fallback previene un error si hubiera datos legacy o inconsistentes.

---

## 11. Resumen de endpoints

| Método | Ruta | Autenticación | Descripción |
|--------|------|---------------|-------------|
| `GET` | `/api/posts` | Requerida | Feed: posts propios + seguidos |
| `GET` | `/api/users/{id}/posts` | Pública | Posts de un usuario concreto |
| `POST` | `/api/posts` | Requerida | Crear post (multipart/form-data) |
| `DELETE` | `/api/posts/{id}` | Requerida | Eliminar (propio o admin global) |
| `POST` | `/api/posts/{id}/like` | Requerida | Toggle like/unlike |
| `GET` | `/api/posts/{id}/comments` | Pública | Listar comentarios |
| `POST` | `/api/posts/{id}/comments` | Requerida | Añadir comentario |
| `DELETE` | `/api/posts/{id}/comments/{cId}` | Requerida | Borrar (autor, dueño del post o admin global) |

---

# 25 — Controlador de Follows: análisis completo

`FollowApiController` gestiona el sistema de seguimiento entre usuarios: seguir, dejar de seguir, ver listas de seguidores/seguidos, expulsar seguidores y el flujo completo de solicitudes de seguimiento para cuentas privadas.

---

## 1. Estructura del controlador

```php
class FollowApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FollowRepository $followRepo,
        private UserRepository $userRepo,
    ) {}
```

A diferencia de otros controladores, no usa el atributo `#[Route]` a nivel de clase porque las rutas no comparten un prefijo común: algunas son `/api/users/{id}/follow` y otras son `/api/follow-requests`.

---

## 2. `POST /api/users/{id}/follow` — seguir a un usuario

```php
#[Route('/api/users/{id}/follow', name: 'api_follow', methods: ['POST'], requirements: ['id' => '\d+'])]
public function follow(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me     = $this->getUser();
    $target = $this->userRepo->find($id);

    if (!$target) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }
    if ($me->getId() === $target->getId()) {
        return $this->json(['error' => 'No puedes seguirte a ti mismo'], 400);
    }
    if ($this->followRepo->findFollow($me, $target)) {
        return $this->json(['error' => 'Ya enviaste una solicitud o sigues a este usuario'], 409);
    }

    $status = $target->isPrivate() ? Follow::STATUS_PENDING : Follow::STATUS_ACCEPTED;
    $follow = new Follow($me, $target, $status);
    $this->em->persist($follow);
    $this->em->flush();

    if ($status === Follow::STATUS_ACCEPTED) {
        $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW));
    } else {
        $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW_REQUEST, null, null, $follow->getId()));
    }
    $this->em->flush();

    return $this->json([
        'status'      => $status,
        'isFollowing' => $status === Follow::STATUS_ACCEPTED,
        'followers'   => $this->followRepo->countFollowers($target),
    ]);
}
```

**Validaciones en orden:**

1. **Usuario existe:** `userRepo->find($id)` — si no existe, 404.
2. **No seguirse a uno mismo:** comparación de IDs — 400.
3. **Ya existe el follow:** `findFollow($me, $target)` busca cualquier fila `Follow` entre los dos usuarios, independientemente del estado. Si ya hay una fila (pending o accepted), devuelve 409. El mensaje unifica ambos casos porque el comportamiento esperado es el mismo: el usuario ya inició alguna relación de seguimiento.

**Ramificación por tipo de cuenta:**

```
target.isPrivate == false  →  Follow(status: 'accepted') + Notification TYPE_FOLLOW
target.isPrivate == true   →  Follow(status: 'pending')  + Notification TYPE_FOLLOW_REQUEST
```

Para la notificación `TYPE_FOLLOW_REQUEST`, se pasa el `$follow->getId()` como `refId`. Esto es crucial: cuando el destinatario acepte o rechace la solicitud desde el panel de notificaciones, el frontend envía este `refId` para identificar qué `Follow` concreto aprobar/rechazar.

**Dos `flush()` separados:**
El primer `flush()` persiste el `Follow` y le asigna un ID. Es necesario obtener ese ID antes de crear la notificación, porque el `refId` de la notificación es `$follow->getId()`. Si se hiciera todo en un único `flush()`, `getId()` sería `null` en el momento de crear la notificación.

**Respuesta:**
```json
// Cuenta pública
{ "status": "accepted", "isFollowing": true,  "followers": 43 }

// Cuenta privada
{ "status": "pending",  "isFollowing": false, "followers": 43 }
```

`followers` es el contador actualizado después del follow, incluyendo el nuevo seguidor si fue aceptado directamente.

---

## 3. `DELETE /api/users/{id}/follow` — dejar de seguir

```php
#[Route('/api/users/{id}/follow', name: 'api_unfollow', methods: ['DELETE'], requirements: ['id' => '\d+'])]
public function unfollow(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me     = $this->getUser();
    $target = $this->userRepo->find($id);

    if (!$target) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }

    $follow = $this->followRepo->findFollow($me, $target);
    if (!$follow) {
        return $this->json(['error' => 'No sigues a este usuario'], 404);
    }

    $this->em->remove($follow);
    $this->em->flush();

    return $this->json([
        'status'      => null,
        'isFollowing' => false,
        'followers'   => $this->followRepo->countFollowers($target),
    ]);
}
```

Este endpoint elimina el registro `Follow` independientemente de su estado actual. Sirve para dos casos de uso:
- **Cancelar una solicitud pendiente:** el usuario envió una solicitud a una cuenta privada y quiere retirarla antes de que sea procesada.
- **Dejar de seguir:** el usuario ya sigue a alguien y quiere parar.

Ambos casos usan `findFollow()` que busca por `(follower, following)` sin filtrar por estado, por lo que con una sola implementación se cubren ambos escenarios.

---

## 4. `GET /api/users/{id}/followers` — lista de seguidores

```php
#[Route('/api/users/{id}/followers', name: 'api_followers', methods: ['GET'], requirements: ['id' => '\d+'])]
public function followers(int $id): JsonResponse
{
    $target = $this->userRepo->find($id);
    if (!$target) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }

    return $this->json(array_map(
        fn(Follow $f) => $this->serializeUser($f->getFollower()),
        $this->followRepo->findFollowers($target)
    ));
}
```

Endpoint **público** (sin autenticación). Devuelve solo los follows con `status = 'accepted'` (via `findFollowers()`), ordenados de más reciente a más antiguo.

El método serializa el **follower** de cada `Follow` — el usuario que sigue a `$target`.

---

## 5. `GET /api/users/{id}/following` — lista de seguidos

```php
#[Route('/api/users/{id}/following', name: 'api_following_list', methods: ['GET'], requirements: ['id' => '\d+'])]
public function following(int $id): JsonResponse
{
    $target = $this->userRepo->find($id);
    if (!$target) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }

    return $this->json(array_map(
        fn(Follow $f) => $this->serializeUser($f->getFollowing()),
        $this->followRepo->findFollowing($target)
    ));
}
```

Simétrico al anterior pero serializa el **following** de cada `Follow` — el usuario al que sigue `$target`.

La asimetría entre las dos rutas es sutil pero importante:
- `findFollowers($target)` → busca `WHERE following = $target` → retorna los objetos `Follow` donde `$target` es el seguido
- `findFollowing($target)` → busca `WHERE follower = $target` → retorna los objetos `Follow` donde `$target` es el seguidor

En ambos casos el helper `serializeUser()` extrae el lado correcto del `Follow`.

---

## 6. `DELETE /api/users/{id}/followers` — expulsar un seguidor

```php
#[Route('/api/users/{id}/followers', name: 'api_remove_follower', methods: ['DELETE'], requirements: ['id' => '\d+'])]
public function removeFollower(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me       = $this->getUser();
    $follower = $this->userRepo->find($id);

    if (!$follower) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }

    // El follow a eliminar es: follower=$follower, following=$me
    $follow = $this->followRepo->findFollow($follower, $me);
    if (!$follow) {
        return $this->json(['error' => 'Este usuario no te sigue'], 404);
    }

    $this->em->remove($follow);
    $this->em->flush();

    return $this->json([
        'followers' => $this->followRepo->countFollowers($me),
    ]);
}
```

El parámetro `{id}` aquí es el ID del **seguidor que se quiere expulsar**, no el ID de la persona a la que se sigue. Esta es la diferencia semántica con `DELETE /api/users/{id}/follow`:

- `DELETE /api/users/5/follow` → "Yo dejo de seguir al usuario 5"
- `DELETE /api/users/5/followers` → "Expulso al usuario 5 de mis seguidores"

El argumento de `findFollow()` está invertido respecto al endpoint de unfollow:
```php
// Unfollow: yo dejo de seguir al target
$follow = $this->followRepo->findFollow($me, $target);

// Remove follower: el follower deja de seguirme a mí
$follow = $this->followRepo->findFollow($follower, $me);
```

Esta funcionalidad es especialmente útil para cuentas privadas que quieren revocar el acceso de alguien que previamente aprobaron.

**Respuesta:**
```json
{ "followers": 41 }
```

Solo devuelve el nuevo conteo de seguidores del usuario autenticado.

---

## 7. `GET /api/follow-requests` — solicitudes entrantes pendientes

```php
#[Route('/api/follow-requests', name: 'api_follow_requests', methods: ['GET'])]
public function incomingRequests(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me = $this->getUser();

    return $this->json(array_map(function (Follow $f) {
        return [
            'id'        => $f->getId(),
            'createdAt' => $f->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'user'      => $this->serializeUser($f->getFollower()),
        ];
    }, $this->followRepo->findIncomingRequests($me)));
}
```

Lista todas las solicitudes de seguimiento con `status = 'pending'` donde el usuario autenticado es el destinatario (`following = $me`). Solo es relevante para cuentas privadas.

El campo `id` en la respuesta es el ID del `Follow` (no del usuario). Este ID es el que se usa en los endpoints de aceptar/rechazar:

```json
[
  {
    "id": 88,
    "createdAt": "2026-04-19T09:00:00+00:00",
    "user": {
      "id": 12,
      "displayName": "PedroM",
      "avatar": null,
      "email": "pedro@test.com"
    }
  }
]
```

---

## 8. `POST /api/follow-requests/{id}/accept` — aceptar solicitud

```php
#[Route('/api/follow-requests/{id}/accept', name: 'api_follow_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
public function accept(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me     = $this->getUser();
    $follow = $this->followRepo->find($id);

    if (!$follow || $follow->getFollowing()->getId() !== $me->getId()) {
        return $this->json(['error' => 'Solicitud no encontrada'], 404);
    }
    if ($follow->isAccepted()) {
        return $this->json(['error' => 'Ya aceptada'], 409);
    }

    $requester = $follow->getFollower();
    $follow->accept();
    $this->em->flush();

    $this->em->persist(new Notification($requester, $me, Notification::TYPE_FOLLOW_ACCEPTED));
    $this->em->flush();

    return $this->json(['status' => 'accepted']);
}
```

**Validación de propiedad:**
`$follow->getFollowing()->getId() !== $me->getId()` verifica que el usuario autenticado es el destinatario de la solicitud. Esto impide que un usuario acepte solicitudes ajenas aunque conozca el ID del `Follow`.

**`$follow->accept()`:**
El método `accept()` de la entidad `Follow` encapsula el cambio de estado:

```php
// Follow.php
public function accept(): void
{
    $this->status = self::STATUS_ACCEPTED;
}
```

**Notificación al solicitante:**
Tras aceptar, se crea una notificación `TYPE_FOLLOW_ACCEPTED` para el usuario que envió la solicitud, informándole de que fue aceptado. Los argumentos del constructor de `Notification` son `(recipient, actor, type)`, por lo que:
- `recipient = $requester` (el que envió la solicitud recibe la notificación)
- `actor = $me` (el que aceptó es el actor)

---

## 9. `DELETE /api/follow-requests/{id}` — rechazar solicitud

```php
#[Route('/api/follow-requests/{id}', name: 'api_follow_decline', methods: ['DELETE'], requirements: ['id' => '\d+'])]
public function decline(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me     = $this->getUser();
    $follow = $this->followRepo->find($id);

    if (!$follow || $follow->getFollowing()->getId() !== $me->getId()) {
        return $this->json(['error' => 'Solicitud no encontrada'], 404);
    }

    $this->em->remove($follow);
    $this->em->flush();

    return $this->json(['status' => 'declined']);
}
```

Rechazar una solicitud elimina directamente el registro `Follow`. No se crea notificación al solicitante — el rechazo es silencioso. El solicitante simplemente ve que su solicitud desaparece sin explicación.

**Diferencia entre rechazar y aceptar:**
- **Aceptar:** cambia el `status` de `pending` a `accepted` + notificación al solicitante.
- **Rechazar:** elimina la fila `Follow` + sin notificación.

---

## 10. Rutas duplicadas: `/api/follow-requests` vs `/api/notifications/follow-requests`

El sistema tiene dos formas de gestionar las solicitudes de seguimiento:

| Ruta | Controlador | Descripción |
|------|-------------|-------------|
| `POST /api/follow-requests/{id}/accept` | `FollowApiController` | Aceptar desde la lista de solicitudes |
| `DELETE /api/follow-requests/{id}` | `FollowApiController` | Rechazar desde la lista de solicitudes |
| `POST /api/notifications/follow-requests/{followId}/accept` | `NotificationApiController` | Aceptar desde el panel de notificaciones |
| `DELETE /api/notifications/follow-requests/{followId}` | `NotificationApiController` | Rechazar desde el panel de notificaciones |

La diferencia es que las rutas de `NotificationApiController` además eliminan la notificación pendiente después de procesar la solicitud. Las rutas de `FollowApiController` son más simples y no interactúan con notificaciones.

---

## 11. Helper `serializeUser()`

```php
private function serializeUser(\App\Entity\User $u): array
{
    return [
        'id'          => $u->getId(),
        'displayName' => $u->getDisplayName() ?? $u->getEmail(),
        'avatar'      => $u->getAvatar(),
        'email'       => $u->getEmail(),
    ];
}
```

Incluye el `email` además del `displayName` y `avatar`, a diferencia del `serializeUser()` de otros controladores. Esto facilita la identificación de usuarios en el panel de solicitudes, donde el admin necesita saber con certeza quién solicita el acceso.

---

## 12. Resumen de endpoints

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| `POST` | `/api/users/{id}/follow` | Requerida | Seguir (o solicitar si cuenta privada) |
| `DELETE` | `/api/users/{id}/follow` | Requerida | Dejar de seguir o cancelar solicitud |
| `GET` | `/api/users/{id}/followers` | Pública | Lista de seguidores aceptados |
| `GET` | `/api/users/{id}/following` | Pública | Lista de usuarios seguidos |
| `DELETE` | `/api/users/{id}/followers` | Requerida | Expulsar un seguidor propio |
| `GET` | `/api/follow-requests` | Requerida | Solicitudes entrantes pendientes |
| `POST` | `/api/follow-requests/{id}/accept` | Requerida | Aceptar solicitud de seguimiento |
| `DELETE` | `/api/follow-requests/{id}` | Requerida | Rechazar solicitud de seguimiento |

---

# 26 — Controlador de Clubes: análisis completo

`ClubApiController` es el controlador más extenso del proyecto. Gestiona el ciclo de vida completo de los clubes: creación, edición, eliminación, membresía (unirse, salir, expulsar), solicitudes de ingreso a clubes privados, y el libro del mes con su rango de fechas.

---

## 1. Estructura del controlador

```php
#[Route('/api/clubs', name: 'api_clubs_')]
class ClubApiController extends AbstractController
```

Todas las rutas tienen el prefijo `/api/clubs`. Las dependencias se inyectan mayoritariamente por parámetro de acción porque muchos métodos necesitan combinaciones distintas de repositorios.

Dos métodos privados centralizan lógica repetida:
- `isAdmin(Club, ClubMemberRepository): bool` — verifica si el usuario actual es admin del club.
- `serializeCurrentBook(Club): ?array` — serializa el libro del mes.
- `importBookFromGoogle(string, HttpClientInterface, EntityManagerInterface): ?Book` — importa un libro de Google Books.

---

## 2. `GET /api/clubs` — listado de todos los clubes

```php
#[Route('', name: 'list', methods: ['GET'])]
public function list(
    ClubRepository $clubRepository,
    ClubMemberRepository $clubMemberRepository,
    ClubJoinRequestRepository $requestRepo
): JsonResponse {
    $clubs = $clubRepository->findBy([], ['id' => 'DESC']);
    $user  = $this->getUser();

    $memberCounts   = $clubMemberRepository->getMemberCountsForClubs($clubs);
    $membershipsMap = $user ? $clubMemberRepository->getMembershipsMapForUser($user, $clubs) : [];

    $pendingMap = [];
    if ($user) {
        $pendingRequests = $requestRepo->findBy(['user' => $user, 'status' => 'pending']);
        foreach ($pendingRequests as $req) {
            $pendingMap[$req->getClub()->getId()] = true;
        }
    }

    return $this->json(array_map(function (Club $club) use ($memberCounts, $membershipsMap, $pendingMap, $user) {
        $membership = $membershipsMap[$club->getId()] ?? null;
        return [
            'id'                => $club->getId(),
            'name'              => $club->getName(),
            'description'       => $club->getDescription(),
            'visibility'        => $club->getVisibility(),
            'memberCount'       => $memberCounts[$club->getId()] ?? 0,
            'userRole'          => $membership?->getRole(),
            'hasPendingRequest' => $user ? ($pendingMap[$club->getId()] ?? false) : false,
            'currentBook'       => $this->serializeCurrentBook($club),
        ];
    }, $clubs));
}
```

**Optimización anti-N+1 en tres consultas:**

```
Consulta 1: SELECT * FROM club ORDER BY id DESC
Consulta 2: SELECT club_id, COUNT(*) FROM club_member WHERE club_id IN (...) GROUP BY club_id
Consulta 3: SELECT * FROM club_member WHERE user_id = ? AND club_id IN (...)
Consulta 4: SELECT * FROM club_join_request WHERE user_id = ? AND status = 'pending'
```

Sin esta optimización, para N clubes habrían N+N+N consultas adicionales (conteo de miembros, membresía del usuario, solicitud pendiente). Con los mapas precalculados, el bucle de serialización es O(1) por club.

**`pendingMap`:** Array `[clubId => true]` construido una sola vez. En el bucle, `$pendingMap[$club->getId()] ?? false` es una búsqueda de array O(1).

**`$membership?->getRole()`:** Si el usuario no es miembro del club, `$membershipsMap[$club->getId()]` devuelve `null` y el operador `?->` hace que `getRole()` no se llame, retornando `null`. `userRole: null` indica en el frontend que el usuario no es miembro.

**Endpoint público:** No llama a `denyAccessUnlessGranted`. Un visitante anónimo ve todos los clubes; `userRole` y `hasPendingRequest` siempre son `false/null` en ese caso.

---

## 3. `POST /api/clubs` — crear club

```php
#[Route('', name: 'create', methods: ['POST'])]
public function create(Request $request, EntityManagerInterface $em): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $payload     = json_decode($request->getContent(), true) ?? [];
    $name        = trim((string) ($payload['name'] ?? ''));
    $description = $payload['description'] ?? null;
    $visibility  = (string) ($payload['visibility'] ?? 'public');

    if ($name === '') {
        return $this->json(['error' => 'name es obligatorio'], 400);
    }
    if (!in_array($visibility, ['public', 'private'], true)) {
        return $this->json(['error' => 'visibility debe ser public o private'], 400);
    }

    $club = new Club();
    $club->setName($name);
    $club->setDescription($description);
    $club->setVisibility($visibility);
    $club->setOwner($this->getUser());
    $club->setCreatedAt(new \DateTimeImmutable());
    $club->setUpdatedAt(new \DateTimeImmutable());
    $em->persist($club);

    $member = new ClubMember();
    $member->setClub($club);
    $member->setUser($this->getUser());
    $member->setRole('admin');
    $member->setJoinedAt(new \DateTimeImmutable());
    $em->persist($member);

    $em->flush();

    return $this->json(['id' => $club->getId(), 'name' => $club->getName(), 'visibility' => $club->getVisibility()], 201);
}
```

**Creación atómica de club + membresía admin:**

Al crear el club, el creador se añade automáticamente como miembro con `role = 'admin'`. Esto garantiza que todo club siempre tiene al menos un administrador desde su creación.

Las dos entidades (`Club` y `ClubMember`) se persisten antes del `flush()`, que las guarda en BD en una única transacción. Si por cualquier razón falla la creación del miembro, el club tampoco se crea (rollback automático).

**`description` puede ser null:** No hay validación de `description` porque es un campo opcional. Se acepta cualquier valor incluyendo `null`.

---

## 4. `GET /api/clubs/{id}` — detalle de un club

```php
#[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
public function detail(
    int $id,
    ClubRepository $clubRepository,
    ClubMemberRepository $clubMemberRepository,
    ClubJoinRequestRepository $requestRepo
): JsonResponse {
    $club = $clubRepository->find($id);
    if (!$club) {
        return $this->json(['error' => 'Club no encontrado'], 404);
    }

    $user              = $this->getUser();
    $userRole          = null;
    $hasPendingRequest = false;

    if ($user) {
        $membership = $clubMemberRepository->findOneBy(['club' => $club, 'user' => $user]);
        $userRole   = $membership?->getRole();
        if (!$userRole) {
            $pendingReq        = $requestRepo->findOneBy(['club' => $club, 'user' => $user, 'status' => 'pending']);
            $hasPendingRequest = $pendingReq !== null;
        }
    }

    $owner = $club->getOwner();
    return $this->json([
        'id'                => $club->getId(),
        'name'              => $club->getName(),
        'description'       => $club->getDescription(),
        'visibility'        => $club->getVisibility(),
        'memberCount'       => $clubMemberRepository->countByClub($club),
        'userRole'          => $userRole,
        'hasPendingRequest' => $hasPendingRequest,
        'currentBook'       => $this->serializeCurrentBook($club),
        'owner'             => $owner ? ['id' => $owner->getId(), 'email' => $owner->getEmail(), 'displayName' => $owner->getDisplayName()] : null,
    ]);
}
```

**Optimización condicional de solicitud pendiente:**

La búsqueda de `ClubJoinRequest` solo se ejecuta si el usuario **no es miembro**. Si ya es miembro, no puede tener una solicitud pendiente, así que la consulta sería innecesaria.

```
if ($user) {
    $membership = ...;
    $userRole = $membership?->getRole();
    if (!$userRole) {          ← solo busca solicitud si NO es miembro
        $hasPendingRequest = ...
    }
}
```

A diferencia del listado, aquí se usa `countByClub()` en lugar del mapa precalculado, porque solo se consulta un club.

**Incluye `owner`:** A diferencia del listado de clubs, el detalle incluye los datos completos del propietario (id, email, displayName). El listado solo muestra `memberCount` y `userRole` para minimizar la respuesta.

---

## 5. `PATCH /api/clubs/{id}` — editar club

```php
#[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
public function update(...): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $club = $clubRepository->find($id);
    if (!$club) {
        return $this->json(['error' => 'Club no encontrado'], 404);
    }

    if (!$this->isAdmin($club, $clubMemberRepository)) {
        return $this->json(['error' => 'Solo los administradores pueden editar el club'], 403);
    }

    $data = json_decode($request->getContent(), true) ?? [];

    if (isset($data['name'])) {
        $name = trim((string) $data['name']);
        if ($name === '') {
            return $this->json(['error' => 'name no puede estar vacío'], 400);
        }
        $club->setName($name);
    }

    if (array_key_exists('description', $data)) {
        $club->setDescription($data['description']);
    }

    if (isset($data['visibility'])) {
        if (!in_array($data['visibility'], ['public', 'private'], true)) {
            return $this->json(['error' => 'visibility debe ser public o private'], 400);
        }
        $club->setVisibility($data['visibility']);
    }

    $club->setUpdatedAt(new \DateTimeImmutable());
    $em->flush();

    return $this->json([...]);
}
```

**Patrón no destructivo:** Solo se actualizan los campos enviados. Si no se envía `name`, no se modifica. Esto permite actualizar solo la descripción sin tocar el nombre.

**`array_key_exists` para `description`:** Permite enviar `"description": null` para borrar la descripción. `isset` sería incorrecto aquí porque retornaría `false` para `null`.

**`isset` para `name` y `visibility`:** Estos campos no deberían ser `null` (el club debe tener nombre y visibilidad), por lo que `isset` es suficiente.

**Actualización de `updatedAt`:** Se actualiza siempre, incluso si no cambia nada. Podría mejorarse comparando valores antes y después, pero para el TFG es aceptable.

**Solo admins pueden editar:** El endpoint devuelve 403 si el usuario no es admin del club. Aquí sí se usa 403 (no 404), porque no tiene sentido ocultar que el club existe cuando el usuario ya lo encontró en el listado.

---

## 6. `DELETE /api/clubs/{id}` — eliminar club

```php
if (!$this->isAdmin($club, $clubMemberRepository) && !$this->isGranted('ROLE_ADMIN')) {
    return $this->json(['error' => 'Solo los administradores pueden eliminar el club'], 403);
}

$em->remove($club);
$em->flush();
```

Pueden eliminar el club:
1. El admin del club (`isAdmin()` retorna `true`).
2. Un administrador global de la plataforma (`ROLE_ADMIN`).

El `remove($club)` con `flush()` desencadena la eliminación en cascada de todos los `ClubMember`, `ClubJoinRequest`, `ClubChat` y `ClubChatMessage` del club, gracias a las configuraciones `cascade` y `orphanRemoval` en la entidad `Club`.

---

## 7. `POST /api/clubs/{id}/join` — unirse a un club

```php
#[Route('/{id}/join', name: 'join', requirements: ['id' => '\d+'], methods: ['POST'])]
public function join(
    int $id,
    ClubRepository $clubRepository,
    ClubMemberRepository $clubMemberRepository,
    EntityManagerInterface $em
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $club     = $clubRepository->find($id);
    $existing = $clubMemberRepository->findOneBy(['club' => $club, 'user' => $this->getUser()]);

    if ($existing) {
        return $this->json(['status' => 'already_member', 'role' => $existing->getRole()]);
    }

    // Club público
    if ($club->getVisibility() === 'public') {
        $member = new ClubMember();
        $member->setClub($club);
        $member->setUser($this->getUser());
        $member->setRole('member');
        $member->setJoinedAt(new \DateTimeImmutable());
        $em->persist($member);
        $em->flush();
        return $this->json(['status' => 'joined', 'role' => 'member']);
    }

    // Club privado: crear solicitud
    $existingRequest = $em->getRepository(ClubJoinRequest::class)->findOneBy([
        'club' => $club,
        'user' => $this->getUser(),
    ]);
    if ($existingRequest) {
        return $this->json(['status' => 'already_requested', 'requestStatus' => $existingRequest->getStatus()]);
    }

    $req = new ClubJoinRequest();
    $req->setClub($club);
    $req->setUser($this->getUser());
    $req->setStatus('pending');
    $req->setRequestedAt(new \DateTimeImmutable());
    $em->persist($req);
    $em->flush();

    // Notificar al propietario del club
    $admin = $club->getOwner();
    if ($admin && $admin->getId() !== $this->getUser()->getId()) {
        $em->persist(new Notification($admin, $this->getUser(), Notification::TYPE_CLUB_REQUEST, null, $club, $req->getId()));
        $em->flush();
    }

    return $this->json(['status' => 'requested']);
}
```

**Cinco posibles resultados:**

| Condición | Respuesta |
|-----------|-----------|
| Ya es miembro | `{ "status": "already_member", "role": "admin"|"member" }` |
| Club público, no era miembro | `{ "status": "joined", "role": "member" }` |
| Club privado, ya tiene solicitud | `{ "status": "already_requested", "requestStatus": "pending"|"approved"|"rejected" }` |
| Club privado, nueva solicitud | `{ "status": "requested" }` |
| Club no encontrado | `{ "error": "Club no encontrado" }` 404 |

**Notificación al propietario del club:**
A diferencia del sistema de follows, la notificación se envía al **owner** del club (no necesariamente al admin de la membresía). `$req->getId()` se pasa como `refId` para que el admin pueda aprobar/rechazar directamente desde la notificación usando ese ID.

La guarda `$admin->getId() !== $this->getUser()->getId()` evita que el propietario se notifique a sí mismo si, por alguna razón, intenta unirse a su propio club.

---

## 8. `DELETE /api/clubs/{id}/leave` — abandonar club

```php
#[Route('/{id}/leave', name: 'leave', requirements: ['id' => '\d+'], methods: ['DELETE'])]
public function leave(...): JsonResponse
{
    $membership = $clubMemberRepository->findOneBy(['club' => $club, 'user' => $this->getUser()]);
    if (!$membership) {
        return $this->json(['error' => 'No eres miembro de este club'], 404);
    }

    if ($membership->getRole() === 'admin' && $clubMemberRepository->countByClub($club) > 1) {
        return $this->json(['error' => 'El administrador no puede abandonar el club si hay otros miembros. Transfiere el rol primero.'], 400);
    }

    $em->remove($membership);
    $em->flush();
    return $this->json(null, 204);
}
```

**Protección del último admin:**

Un admin no puede abandonar el club si hay más miembros. Esto garantiza que el club siempre tiene al menos un administrador mientras existan miembros.

La condición `countByClub($club) > 1` verifica que haya más de un miembro. Si el admin es el único miembro, puede abandonar (lo que en la práctica equivale a disolver el club sin eliminarlo explícitamente).

Si el admin quiere irse con otros miembros presentes, debe primero promover a otro miembro como admin (funcionalidad no implementada en el TFG, pero la arquitectura lo soporta).

---

## 9. `GET /api/clubs/{id}/members` — lista de miembros

```php
if ($club->getVisibility() === 'private') {
    $user = $this->getUser();
    if (!$user || !$clubMemberRepository->findOneBy(['club' => $club, 'user' => $user])) {
        return $this->json(['error' => 'Acceso denegado'], 403);
    }
}

$members = array_map(fn(ClubMember $m) => [
    'id'       => $m->getId(),
    'role'     => $m->getRole(),
    'joinedAt' => $m->getJoinedAt()?->format(\DateTimeInterface::ATOM),
    'user'     => [
        'id'          => $m->getUser()->getId(),
        'displayName' => $m->getUser()->getDisplayName(),
        'avatar'      => $m->getUser()->getAvatar(),
    ],
], $clubMemberRepository->findMembersWithUser($club));
```

**Clubs privados** solo muestran los miembros a otros miembros o a un administrador global (`ROLE_ADMIN`). Un visitante externo (autenticado o no) recibe 403.

**En el frontend**, cuando el club es privado y el usuario no es miembro, la pestaña "Miembros" muestra un estado vacío con el mensaje *"Club privado — Los miembros solo son visibles para los integrantes del club"*, en lugar de mostrar una lista vacía que podría confundir al usuario con un club sin miembros.

`findMembersWithUser($club)` hace un JOIN con la tabla `user` para traer el objeto `User` junto al `ClubMember` en una sola consulta (eager loading), evitando N consultas al acceder a `$m->getUser()`.

---

## 10. `DELETE /api/clubs/{id}/members/{memberId}` — expulsar miembro

```php
if ($target->getUser() === $this->getUser()) {
    return $this->json(['error' => 'No puedes expulsarte a ti mismo. Usa /leave.'], 400);
}
```

El admin no puede expulsarse a sí mismo usando este endpoint. Debe usar `DELETE /api/clubs/{id}/leave`. Esta separación hace la API más expresiva: expulsar a alguien y salir voluntariamente son semánticamente diferentes.

La verificación `$target->getClub() !== $club` garantiza que el `ClubMember` con ese ID pertenece al club correcto, evitando que un admin de un club expulse miembros de otro club.

---

## 11. `GET /api/clubs/{id}/requests` — solicitudes pendientes

```php
if (!$this->isAdmin($club, $clubMemberRepository)) {
    return $this->json(['error' => 'Solo los administradores pueden ver las solicitudes'], 403);
}

$requests = $joinRequestRepo->findPendingWithUser($club);

return $this->json(array_map(fn(ClubJoinRequest $r) => [
    'id'          => $r->getId(),
    'status'      => $r->getStatus(),
    'requestedAt' => $r->getRequestedAt()?->format(\DateTimeInterface::ATOM),
    'user'        => [
        'id'          => $r->getUser()->getId(),
        'displayName' => $r->getUser()->getDisplayName(),
        'avatar'      => $r->getUser()->getAvatar(),
    ],
], $requests));
```

`findPendingWithUser($club)` hace eager loading del usuario de cada solicitud (JOIN + addSelect). Solo devuelve solicitudes con `status = 'pending'`.

---

## 12. `POST /api/clubs/{id}/requests/{requestId}/approve` — aprobar solicitud

```php
$req = $joinRequestRepo->find($requestId);
if (!$req || $req->getClub() !== $club || $req->getStatus() !== 'pending') {
    return $this->json(['error' => 'Solicitud no encontrada'], 404);
}

$requester = $req->getUser();
$req->setStatus('approved');
$req->setResolvedBy($this->getUser());
$req->setResolvedAt(new \DateTimeImmutable());

$member = new ClubMember();
$member->setClub($club);
$member->setUser($requester);
$member->setRole('member');
$member->setJoinedAt(new \DateTimeImmutable());
$em->persist($member);
$em->flush();

$em->persist(new Notification($requester, $this->getUser(), Notification::TYPE_CLUB_APPROVED, null, $club));
$em->flush();

$notifRepo->deleteByRefIdAndType($this->getUser(), Notification::TYPE_CLUB_REQUEST, $requestId);
```

**Transacción completa en este orden:**

1. Validar que la solicitud existe, pertenece al club, y está `pending`.
2. Actualizar el estado de la solicitud a `approved` + registrar quién resolvió y cuándo.
3. Crear el `ClubMember` para el solicitante.
4. Primer `flush()` — guarda la solicitud actualizada y el nuevo miembro.
5. Crear notificación `TYPE_CLUB_APPROVED` para el solicitante.
6. Segundo `flush()` — guarda la notificación.
7. Eliminar la notificación `TYPE_CLUB_REQUEST` del admin (ya procesada).

**Auditoría de la solicitud:**
`setResolvedBy()` y `setResolvedAt()` guardan quién aprobó la solicitud y cuándo. Aunque no se expone en la API pública, permite auditoría futura.

**Limpieza de notificaciones:**
`deleteByRefIdAndType($this->getUser(), TYPE_CLUB_REQUEST, $requestId)` elimina la notificación de solicitud que el admin tenía pendiente. El `refId` de esa notificación es el ID de la `ClubJoinRequest`, que coincide con `$requestId`.

---

## 13. `POST /api/clubs/{id}/requests/{requestId}/reject` — rechazar solicitud

Flujo idéntico a aprobar, pero:
- El estado de la solicitud se pone a `'rejected'` (no se crea `ClubMember`).
- La notificación al solicitante es `TYPE_CLUB_REJECTED` en lugar de `TYPE_CLUB_APPROVED`.
- La notificación del admin se elimina igualmente.

El solicitante recibe la notificación de rechazo para saber que su solicitud fue procesada.

---

## 14. `PUT /api/clubs/{id}/current-book` — establecer libro del mes

```php
// Parsear fechas
$dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d', $data['dateFrom']);
$dateFrom = $dateFrom->setTime(0, 0, 0);

$dateUntil = \DateTimeImmutable::createFromFormat('Y-m-d', $data['dateUntil']);
$dateUntil = $dateUntil->setTime(23, 59, 59);

if ($dateUntil <= $dateFrom) {
    return $this->json(['error' => 'La fecha de fin debe ser posterior a la de inicio'], 400);
}

// Importar libro si no existe
$book = $bookRepository->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);
if (!$book) {
    $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
}

$club->setCurrentBook($book);
$club->setCurrentBookSince($dateFrom);
$club->setCurrentBookUntil($dateUntil);
$club->setUpdatedAt(new \DateTimeImmutable());
$em->flush();
```

**Normalización de las fechas:**
- `dateFrom` se normaliza a las `00:00:00` del día.
- `dateUntil` se normaliza a las `23:59:59` del día.

Esto garantiza que el rango es inclusivo en ambos extremos: si el libro es para "abril", `dateFrom = 2026-04-01 00:00:00` y `dateUntil = 2026-04-30 23:59:59`.

**`dateFrom` por defecto:** Si no se envía `dateFrom`, se usa `new \DateTimeImmutable('today')`. El admin puede establecer el libro sin especificar fecha de inicio.

**Validación del orden de fechas:** `$dateUntil <= $dateFrom` rechaza rangos donde la fecha fin es igual o anterior a la inicio.

**Serialización de la respuesta:**
```php
private function serializeCurrentBook(Club $club): ?array
{
    $book = $club->getCurrentBook();
    if (!$book) return null;

    return [
        'id'            => $book->getId(),
        'externalId'    => $book->getExternalId(),
        'title'         => $book->getTitle(),
        'authors'       => $book->getAuthors() ?? [],
        'coverUrl'      => $book->getCoverUrl(),
        'publishedDate' => $book->getPublishedDate(),
        'since'         => $club->getCurrentBookSince()?->format('Y-m-d'),
        'until'         => $club->getCurrentBookUntil()?->format('Y-m-d'),
    ];
}
```

Las fechas se formatean como `'Y-m-d'` (solo la parte de fecha, sin hora), porque en la interfaz se muestran como "Del 1 de abril al 30 de abril".

---

## 15. Helper `isAdmin()`

```php
private function isAdmin(Club $club, ClubMemberRepository $clubMemberRepository): bool
{
    if ($this->isGranted('ROLE_ADMIN')) {
        return true;
    }

    $membership = $clubMemberRepository->findOneBy([
        'club' => $club,
        'user' => $this->getUser(),
    ]);
    return $membership?->getRole() === 'admin';
}
```

Se llama en 6 endpoints: `update`, `delete`, `kickMember`, `joinRequests`, `approveRequest`, `rejectRequest`. Centraliza la verificación y garantiza que la lógica de "¿es admin?" es consistente en todo el controlador.

**`ROLE_ADMIN` tiene prioridad:** un administrador global puede expulsar miembros, aprobar/rechazar solicitudes y gestionar el libro del mes en cualquier club, aunque no sea miembro de él.

El operador `?->` hace que si `$membership` es `null` (no es miembro), la comparación sea `null === 'admin'` → `false`. Sin el operador nullsafe, habría que escribir:
```php
return $membership !== null && $membership->getRole() === 'admin';
```

---

## 16. Resumen de endpoints

| Método | Ruta | Admin | Descripción |
|--------|------|-------|-------------|
| `GET` | `/api/clubs` | No | Lista todos los clubes |
| `POST` | `/api/clubs` | No | Crear club |
| `GET` | `/api/clubs/{id}` | No | Detalle del club |
| `PATCH` | `/api/clubs/{id}` | Sí | Editar nombre/desc/visibilidad |
| `DELETE` | `/api/clubs/{id}` | Sí | Eliminar club |
| `POST` | `/api/clubs/{id}/join` | No | Unirse (o solicitar si privado) |
| `DELETE` | `/api/clubs/{id}/leave` | No | Abandonar club |
| `GET` | `/api/clubs/{id}/members` | No | Listar miembros |
| `DELETE` | `/api/clubs/{id}/members/{mId}` | Sí | Expulsar miembro |
| `GET` | `/api/clubs/{id}/requests` | Sí | Ver solicitudes pendientes |
| `POST` | `/api/clubs/{id}/requests/{rId}/approve` | Sí | Aprobar solicitud |
| `POST` | `/api/clubs/{id}/requests/{rId}/reject` | Sí | Rechazar solicitud |
| `PUT` | `/api/clubs/{id}/current-book` | Sí | Establecer libro del mes |
| `DELETE` | `/api/clubs/{id}/current-book` | Sí | Quitar libro del mes |

---

# 27 — Controlador de Chat de Clubes: análisis completo

`ClubChatApiController` gestiona los hilos de debate dentro de los clubes y los mensajes de cada hilo. Todos los endpoints tienen una doble clave de ruta (`clubId` + `chatId`) que requiere validación encadenada, resuelta mediante el helper `resolveChat()`.

---

## 1. Estructura del controlador

```php
#[Route('/api/clubs/{clubId}/chats', name: 'api_club_chats_', requirements: ['clubId' => '\d+'])]
class ClubChatApiController extends AbstractController
{
    public function __construct(
        private ClubChatMessageRepository $msgRepo,
    ) {}
```

La única dependencia inyectada por constructor es `$msgRepo`, usada en `serializeChat()` para contar mensajes. El resto de repositorios se inyectan por parámetro de acción porque no todos los métodos los necesitan.

**Prefijo de ruta:** `/api/clubs/{clubId}/chats` — todas las rutas incluyen el `clubId` en el path. Esto es REST semántico: los chats son subrecursos del club.

---

## 2. Helper `resolveChat()` — validación encadenada

```php
private function resolveChat(
    int $clubId,
    int $chatId,
    ClubRepository $clubRepo,
    ClubChatRepository $chatRepo
): array {
    $club = $clubRepo->find($clubId);
    if (!$club) {
        return [null, null, $this->json(['error' => 'Club no encontrado'], 404)];
    }

    $chat = $chatRepo->find($chatId);
    if (!$chat || $chat->getClub() !== $club) {
        return [null, null, $this->json(['error' => 'Hilo no encontrado'], 404)];
    }

    return [$club, $chat, null];
}
```

Este helper resuelve dos problemas simultáneamente:

**Problema 1 — Evitar duplicación:** Seis endpoints necesitan verificar "el club existe" Y "el chat existe y pertenece al club". Sin el helper, esas 8 líneas de validación se repetirían 6 veces (48 líneas de código duplicado).

**Problema 2 — Seguridad entre clubs:** La comprobación `$chat->getClub() !== $club` garantiza que el chat pertenece exactamente al club indicado en la URL. Sin esta verificación, una URL como `/api/clubs/5/chats/99` podría acceder al chat 99 aunque perteneciera al club 8.

**Patrón de uso en cada acción:**
```php
[$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
if ($error) return $error;
// A partir de aquí, $club y $chat están garantizados como válidos
```

La desestructuración de array con `[$club, $chat, $error]` es una sintaxis de PHP 7.1+. Si hay error, los dos primeros valores son `null` y `$error` es un `JsonResponse`. Si todo es correcto, `$error` es `null`.

---

## 3. `GET /api/clubs/{clubId}/chats` — listar hilos

```php
#[Route('', name: 'list', methods: ['GET'])]
public function list(
    int $clubId,
    ClubRepository $clubRepo,
    ClubMemberRepository $memberRepo,
    ClubChatRepository $chatRepo
): JsonResponse {
    $club = $clubRepo->find($clubId);
    if (!$club) {
        return $this->json(['error' => 'Club no encontrado'], 404);
    }

    // Clubs privados: solo miembros pueden ver los hilos
    if ($club->getVisibility() === 'private') {
        $user = $this->getUser();
        if (!$user || !$memberRepo->findOneBy(['club' => $club, 'user' => $user])) {
            return $this->json(['error' => 'Acceso denegado'], 403);
        }
    }

    $chats = $chatRepo->findByClubWithCreator($club);
    return $this->json(array_map(fn(ClubChat $c) => $this->serializeChat($c), $chats));
}
```

**No usa `resolveChat()`** porque este endpoint solo tiene `clubId` (no hay `chatId`). La verificación se hace manualmente.

**Clubs privados:** Solo miembros pueden ver los hilos. Un usuario no miembro recibe 403. Un visitante anónimo también recibe 403 (porque `$this->getUser()` devuelve `null` y la condición `!$user` es `true`).

**`findByClubWithCreator($club)`:** Trae los hilos con el usuario `createdBy` precargado (JOIN + addSelect), evitando N consultas al serializar el campo `createdBy` de cada hilo.

---

## 4. `POST /api/clubs/{clubId}/chats` — crear hilo

```php
#[Route('', name: 'create', methods: ['POST'])]
public function create(
    int $clubId,
    Request $request,
    ClubRepository $clubRepo,
    ClubMemberRepository $memberRepo,
    EntityManagerInterface $em
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $club        = $clubRepo->find($clubId);
    $membership  = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
    $isClubAdmin = $membership?->getRole() === 'admin';
    $isWebAdmin  = $this->isGranted('ROLE_ADMIN');

    if (!$isClubAdmin && !$isWebAdmin) {
        return $this->json(['error' => 'Solo los administradores del club pueden crear hilos de chat'], 403);
    }

    $data  = json_decode($request->getContent(), true) ?? [];
    $title = trim((string) ($data['title'] ?? ''));

    if ($title === '') {
        return $this->json(['error' => 'title es obligatorio'], 400);
    }

    $chat = new ClubChat();
    $chat->setClub($club);
    $chat->setCreatedBy($this->getUser());
    $chat->setTitle($title);
    $chat->setIsOpen(true);
    $chat->setCreatedAt(new \DateTimeImmutable());

    $em->persist($chat);
    $em->flush();

    return $this->json($this->serializeChat($chat), 201);
}
```

**Quién puede crear hilos:**
- Admins del club (`$isClubAdmin`).
- Administradores globales de la plataforma (`$isWebAdmin`).

Los miembros normales no pueden crear hilos. Esta decisión editorial garantiza que el contenido del foro está curado por los responsables del club.

**Estado inicial:** Todo hilo se crea con `isOpen = true`. El admin puede cerrarlo posteriormente con `PATCH`.

---

## 5. `PATCH /api/clubs/{clubId}/chats/{chatId}` — editar hilo

```php
$membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
$isAdmin    = $membership?->getRole() === 'admin';
$isCreator  = $chat->getCreatedBy() === $this->getUser();

if (!$isAdmin && !$isCreator) {
    return $this->json(['error' => 'Solo el creador o un administrador pueden editar el hilo'], 403);
}

$data = json_decode($request->getContent(), true) ?? [];

if (isset($data['title'])) {
    $title = trim((string) $data['title']);
    if ($title === '') {
        return $this->json(['error' => 'title no puede estar vacío'], 400);
    }
    $chat->setTitle($title);
}

if (isset($data['isOpen'])) {
    $wasOpen = $chat->isOpen();
    $chat->setIsOpen((bool) $data['isOpen']);

    if ($wasOpen && !$chat->isOpen()) {
        $chat->setClosedAt(new \DateTimeImmutable());
    } elseif (!$wasOpen && $chat->isOpen()) {
        $chat->setClosedAt(null);
    }
}

$em->flush();
```

**Permisos para editar:**
- El **creador** del hilo puede editar su título.
- Un **admin** del club puede editar cualquier hilo.

**Gestión del estado `isOpen` y `closedAt`:**

```
wasOpen=true  + isOpen=false  →  closedAt = ahora   (cerrar hilo)
wasOpen=false + isOpen=true   →  closedAt = null     (reabrir hilo)
wasOpen=true  + isOpen=true   →  sin cambio          (ya estaba abierto)
wasOpen=false + isOpen=false  →  sin cambio          (ya estaba cerrado)
```

`closedAt` guarda exactamente cuándo se cerró el hilo. Al reabrirlo se limpia a `null`. Esto permite al frontend mostrar "Cerrado el 19 de abril a las 15:30" en la interfaz.

---

## 6. `DELETE /api/clubs/{clubId}/chats/{chatId}` — eliminar hilo

```php
$membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
if ($membership?->getRole() !== 'admin' && !$this->isGranted('ROLE_ADMIN')) {
    return $this->json(['error' => 'Solo los administradores pueden eliminar hilos'], 403);
}

$em->remove($chat);
$em->flush();
```

Solo admins del club o admins globales pueden eliminar hilos. A diferencia de editar (donde el creador también puede), eliminar es una acción más drástica reservada a los administradores.

Al eliminar el `ClubChat`, el `orphanRemoval: true` en la entidad elimina en cascada todos sus `ClubChatMessage`.

---

## 7. `GET /api/clubs/{clubId}/chats/{chatId}/messages` — listar mensajes paginados

```php
#[Route('/{chatId}/messages', name: 'messages_list', requirements: ['chatId' => '\d+'], methods: ['GET'])]
public function listMessages(
    int $clubId, int $chatId,
    Request $request,
    ClubRepository $clubRepo,
    ClubMemberRepository $memberRepo,
    ClubChatRepository $chatRepo,
    ClubChatMessageRepository $messageRepo
): JsonResponse {
    [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
    if ($error) return $error;

    if ($club->getVisibility() === 'private') {
        $user = $this->getUser();
        if (!$user || !$memberRepo->findOneBy(['club' => $club, 'user' => $user])) {
            return $this->json(['error' => 'Acceso denegado'], 403);
        }
    }

    $page  = max(1, (int) $request->query->get('page', 1));
    $limit = min(max((int) $request->query->get('limit', 50), 1), 100);

    $messages = $messageRepo->findPaginated($chat->getId(), $page, $limit);
    $total    = $messageRepo->countByChat($chat->getId());

    return $this->json([
        'page'     => $page,
        'limit'    => $limit,
        'total'    => $total,
        'messages' => array_map(fn(ClubChatMessage $m) => $this->serializeMessage($m), $messages),
    ]);
}
```

**Paginación con sanitización de parámetros:**

```
page  = max(1, param)           → mínimo página 1, nunca 0 ni negativo
limit = min(100, max(1, param)) → entre 1 y 100, nunca fuera de rango
```

`min(max(...), 100)` es el idioma PHP para clampear un valor en un rango `[1, 100]`.

**Dos consultas para los mensajes:**
1. `findPaginated()` → `SELECT m.*, u.* ... LIMIT ? OFFSET ?` — trae los mensajes con el usuario precargado.
2. `countByChat()` → `SELECT COUNT(*) ...` — calcula el total para los metadatos de paginación.

El cliente usa `total`, `page` y `limit` para calcular el número de páginas y si hay más mensajes:
```
totalPages = Math.ceil(total / limit)
hasMore    = page < totalPages
```

**Mensajes ordenados de más antiguo a más reciente (ASC):**
Este orden es el natural de una conversación: los mensajes más viejos primero, los más recientes al final. Al paginar, la "página 1" tiene los primeros mensajes del hilo, no los últimos.

---

## 8. `POST /api/clubs/{clubId}/chats/{chatId}/messages` — enviar mensaje

```php
#[Route('/{chatId}/messages', name: 'messages_create', requirements: ['chatId' => '\d+'], methods: ['POST'])]
public function sendMessage(
    int $clubId, int $chatId,
    Request $request,
    ClubRepository $clubRepo,
    ClubMemberRepository $memberRepo,
    ClubChatRepository $chatRepo,
    EntityManagerInterface $em
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
    if ($error) return $error;

    if (!$memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()])) {
        return $this->json(['error' => 'Solo los miembros pueden enviar mensajes'], 403);
    }

    if (!$chat->isOpen()) {
        return $this->json(['error' => 'El hilo está cerrado'], 400);
    }

    $data    = json_decode($request->getContent(), true) ?? [];
    $content = trim((string) ($data['content'] ?? ''));

    if ($content === '') {
        return $this->json(['error' => 'content es obligatorio'], 400);
    }

    $message = new ClubChatMessage();
    $message->setChat($chat);
    $message->setUser($this->getUser());
    $message->setContent($content);
    $message->setCreatedAt(new \DateTimeImmutable());

    $em->persist($message);
    $em->flush();

    return $this->json($this->serializeMessage($message), 201);
}
```

**Tres validaciones de acceso en cadena:**

1. **Autenticado:** `denyAccessUnlessGranted('ROLE_USER')` — sin sesión, 401.
2. **Miembro del club:** `findOneBy(['club' => $club, 'user' => $user])` — si no es miembro, 403.
3. **Hilo abierto:** `$chat->isOpen()` — si está cerrado, 400.

El código 400 para "hilo cerrado" (en lugar de 403) es una decisión semántica: no es un problema de permisos sino de estado del recurso. El usuario tiene permiso (es miembro), pero el hilo no acepta nuevos mensajes.

---

## 9. `DELETE /api/clubs/{clubId}/chats/{chatId}/messages/{messageId}` — borrar mensaje

```php
$message = $messageRepo->find($messageId);
if (!$message || $message->getChat() !== $chat) {
    return $this->json(['error' => 'Mensaje no encontrado'], 404);
}

$membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
$isAdmin    = $membership?->getRole() === 'admin' || $this->isGranted('ROLE_ADMIN');
$isOwner    = $message->getUser() === $this->getUser();

if (!$isAdmin && !$isOwner) {
    return $this->json(['error' => 'Solo puedes borrar tus propios mensajes'], 403);
}

$em->remove($message);
$em->flush();
```

**Quién puede borrar un mensaje:**
- El **autor del mensaje** (`$isOwner`).
- Un **admin del club** (`$membership?->getRole() === 'admin'`).
- Un **admin global** (`$this->isGranted('ROLE_ADMIN')`).

Esto permite a los administradores del club moderar el contenido eliminando mensajes inapropiados de cualquier miembro.

**Validación encadenada de 4 niveles:**
1. `resolveChat()` — el club y el hilo existen y el hilo pertenece al club.
2. `$message->getChat() !== $chat` — el mensaje pertenece al hilo correcto.
3. Permisos del usuario autenticado sobre el mensaje.
4. El mensaje existe y se puede eliminar.

---

## 10. Helpers de serialización

### `serializeChat()`

```php
private function serializeChat(ClubChat $chat): array
{
    return [
        'id'           => $chat->getId(),
        'title'        => $chat->getTitle(),
        'isOpen'       => $chat->isOpen(),
        'messageCount' => $this->msgRepo->countByChat($chat->getId()),
        'createdAt'    => $chat->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        'closedAt'     => $chat->getClosedAt()?->format(\DateTimeInterface::ATOM),
        'createdBy'    => [
            'id'          => $chat->getCreatedBy()->getId(),
            'displayName' => $chat->getCreatedBy()->getDisplayName(),
            'avatar'      => $chat->getCreatedBy()->getAvatar(),
        ],
    ];
}
```

`$this->msgRepo->countByChat($chat->getId())` ejecuta `SELECT COUNT(*) WHERE chat_id = ?` por cada hilo serializado. Para una lista de 20 hilos, esto supone 20 consultas. Podría optimizarse con un batch similar a `getMemberCountsForClubs()`, pero para el TFG es aceptable.

### `serializeMessage()`

```php
private function serializeMessage(ClubChatMessage $message): array
{
    return [
        'id'        => $message->getId(),
        'content'   => $message->getContent(),
        'createdAt' => $message->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        'user'      => [
            'id'          => $message->getUser()->getId(),
            'displayName' => $message->getUser()->getDisplayName(),
            'avatar'      => $message->getUser()->getAvatar(),
        ],
    ];
}
```

El acceso a `$message->getUser()` no lanza una consulta adicional porque `findPaginated()` ya hizo eager loading del usuario con JOIN. Si se llamara `serializeMessage()` con un mensaje cargado sin JOIN, sí lanzaría una consulta extra.

---

## 11. Tabla de permisos completa

| Acción | No miembro | Miembro | Admin club | Admin global |
|--------|-----------|---------|------------|--------------|
| Ver hilos (club público) | ✓ | ✓ | ✓ | ✓ |
| Ver hilos (club privado) | ✗ | ✓ | ✓ | ✓ |
| Crear hilo | ✗ | ✗ | ✓ | ✓ |
| Editar hilo propio | ✗ | ✓ (solo el creador) | ✓ | ✓ |
| Editar hilo ajeno | ✗ | ✗ | ✓ | ✓ |
| Eliminar hilo | ✗ | ✗ | ✓ | ✓ |
| Ver mensajes (club público) | ✓ | ✓ | ✓ | ✓ |
| Ver mensajes (club privado) | ✗ | ✓ | ✓ | ✓ |
| Enviar mensaje | ✗ | ✓ (hilo abierto) | ✓ (hilo abierto) | ✓ (hilo abierto) |
| Borrar mensaje propio | ✗ | ✓ | ✓ | ✓ |
| Borrar mensaje ajeno | ✗ | ✗ | ✓ | ✓ |
| Abrir/cerrar hilo | ✗ | ✗ | ✓ | ✓ |

---

## 12. Resumen de endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/clubs/{cId}/chats` | Listar hilos del club |
| `POST` | `/api/clubs/{cId}/chats` | Crear hilo (solo admin) |
| `GET` | `/api/clubs/{cId}/chats/{id}` | Detalle de un hilo |
| `PATCH` | `/api/clubs/{cId}/chats/{id}` | Editar título o estado |
| `DELETE` | `/api/clubs/{cId}/chats/{id}` | Eliminar hilo (solo admin) |
| `GET` | `/api/clubs/{cId}/chats/{id}/messages` | Mensajes paginados |
| `POST` | `/api/clubs/{cId}/chats/{id}/messages` | Enviar mensaje (solo miembros) |
| `DELETE` | `/api/clubs/{cId}/chats/{id}/messages/{mId}` | Borrar mensaje (propio o admin) |

---

# 28 — Controlador de Reseñas: análisis completo

`BookReviewApiController` gestiona las reseñas de libros. Cada usuario puede tener como máximo una reseña por libro (restricción `UNIQUE(user_id, book_id)`). El controlador implementa el patrón **upsert**: crear y actualizar se realizan con el mismo endpoint `POST`.

---

## 1. Estructura del controlador

```php
#[Route('/api/books/{externalId}/reviews', name: 'api_book_reviews_',
    requirements: ['externalId' => '[^/]+'])]
class BookReviewApiController extends AbstractController
```

**Prefijo de ruta:** `/api/books/{externalId}/reviews` — las reseñas son subrecursos del libro identificado por su `externalId` de Google Books.

**Requisito `[^/]+`:** El `externalId` de Google Books puede contener guiones y letras mayúsculas (ej. `zyTCAlFPjgYC`). El regex `[^/]+` acepta cualquier carácter excepto `/`, evitando conflictos de enrutamiento.

**Sin inyección por constructor:** Todos los repositorios y servicios se inyectan por parámetro de acción. Esta es la variante opuesta a `ReadingProgressApiController`, válida cuando los métodos tienen dependencias diferentes entre sí.

---

## 2. `GET /api/books/{externalId}/reviews` — listar reseñas

```php
public function list(
    string $externalId,
    BookRepository $bookRepo,
    BookReviewRepository $reviewRepo
): JsonResponse {
    $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

    if (!$book) {
        return $this->json(['stats' => ['average' => null, 'count' => 0], 'myRating' => null, 'reviews' => []]);
    }

    $me    = $this->getUser();
    $stats = $reviewRepo->getStats($book);

    $myRating = null;
    if ($me) {
        $myReview = $reviewRepo->findOneByUserAndBook($me, $book);
        if ($myReview) {
            $myRating = ['id' => $myReview->getId(), 'rating' => $myReview->getRating(), 'content' => $myReview->getContent()];
        }
    }

    $reviews = array_map(fn(BookReview $r) => $this->serializeReview($r), $reviewRepo->findByBook($book));

    return $this->json(['stats' => $stats, 'myRating' => $myRating, 'reviews' => $reviews]);
}
```

**Respuesta vacía en lugar de 404:** Si el libro no existe en la base de datos local (nunca nadie lo añadió a una estantería ni lo reseñó), se devuelve una respuesta válida con estadísticas vacías en lugar de un 404. El frontend puede renderizar "Sin reseñas aún" sin necesidad de manejo especial de errores.

**Endpoint público (sin autenticación obligatoria):** Cualquier visitante puede ver las reseñas de un libro. El usuario autenticado recibe adicionalmente su propia reseña (`myRating`) para saber si ya valoró el libro.

**Estructura de la respuesta:**

```json
{
  "stats": { "average": 4.2, "count": 15 },
  "myRating": { "id": 42, "rating": 5, "content": "Excelente libro" },
  "reviews": [
    {
      "id": 42,
      "rating": 5,
      "content": "Excelente libro",
      "createdAt": "2024-03-15T10:30:00+00:00",
      "user": { "id": 1, "displayName": "Rita", "avatar": "/uploads/..." }
    }
  ]
}
```

`myRating` es `null` si el usuario no está autenticado o no ha reseñado el libro. El frontend usa este campo para decidir si mostrar el formulario de nueva reseña o el de edición.

---

## 3. `POST /api/books/{externalId}/reviews` — upsert (crear o actualizar)

```php
public function upsert(
    string $externalId,
    Request $request,
    BookRepository $bookRepo,
    BookReviewRepository $reviewRepo,
    EntityManagerInterface $em,
    HttpClientInterface $httpClient
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $data    = json_decode($request->getContent(), true) ?? [];
    $rating  = (int) ($data['rating'] ?? 0);
    $content = trim((string) ($data['content'] ?? '')) ?: null;

    if ($rating < 1 || $rating > 5) {
        return $this->json(['error' => 'rating debe ser entre 1 y 5'], 400);
    }
    ...
}
```

**Patrón upsert:** En lugar de tener `POST` para crear y `PATCH` para actualizar, este controlador unifica las dos operaciones:

```php
$review = $reviewRepo->findOneByUserAndBook($me, $book);

if ($review) {
    // Actualizar reseña existente
    $review->setRating($rating);
    $review->setContent($content);
} else {
    // Crear nueva reseña
    $review = new BookReview($me, $book, $rating, $content);
    $em->persist($review);
}

$em->flush();
```

Esta decisión simplifica el cliente: el frontend siempre hace `POST`, sin necesidad de conocer si el usuario ya tiene reseña. El servidor resuelve internamente si es creación o actualización.

**`content` es opcional:** La expresión `trim(...) ?: null` convierte una cadena vacía en `null`. El usuario puede valorar con 1-5 estrellas sin escribir texto.

**Auto-importación del libro:** Si el libro no existe localmente, se importa automáticamente desde Google Books antes de crear la reseña. Este es el mismo mecanismo usado en `ShelfApiController` y `ReadingProgressApiController`.

**Respuesta incluye estadísticas actualizadas:**

```json
{
  "review": { "id": 42, "rating": 5, "content": "...", "createdAt": "...", "user": {...} },
  "stats": { "average": 4.3, "count": 16 }
}
```

El frontend puede actualizar la media mostrada en pantalla inmediatamente sin hacer una segunda petición.

---

## 4. `DELETE /api/books/{externalId}/reviews` — eliminar mi reseña

```php
public function delete(
    string $externalId,
    BookRepository $bookRepo,
    BookReviewRepository $reviewRepo,
    EntityManagerInterface $em
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me   = $this->getUser();
    $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

    if (!$book) {
        return $this->json(['error' => 'Libro no encontrado'], 404);
    }

    $review = $reviewRepo->findOneByUserAndBook($me, $book);
    if (!$review) {
        return $this->json(['error' => 'No tienes una reseña para este libro'], 404);
    }

    $em->remove($review);
    $em->flush();

    $stats = $reviewRepo->getStats($book);

    return $this->json(['stats' => $stats]);
}
```

**Sin parámetro `{id}` en la ruta:** El usuario solo puede borrar su propia reseña (una por libro), por lo que no hace falta el ID de la reseña. El endpoint deduce la reseña a partir del usuario autenticado y el libro en la URL.

**Responde con estadísticas actualizadas** tras el borrado, igual que `upsert`. El frontend puede actualizar la media sin petición adicional.

---

## 5. Helper `serializeReview()`

```php
private function serializeReview(BookReview $r): array
{
    $u = $r->getUser();
    return [
        'id'        => $r->getId(),
        'rating'    => $r->getRating(),
        'content'   => $r->getContent(),
        'createdAt' => $r->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'user'      => [
            'id'          => $u->getId(),
            'displayName' => $u->getDisplayName() ?? $u->getEmail(),
            'avatar'      => $u->getAvatar(),
        ],
    ];
}
```

**Fallback `displayName ?? email`:** Si el usuario no tiene `displayName` configurado, se usa el email. Esto garantiza que siempre hay algo visible en la UI aunque el perfil esté incompleto.

---

## 6. Helper `importBookFromGoogle()`

```php
private function importBookFromGoogle(string $externalId, HttpClientInterface $httpClient, EntityManagerInterface $em): ?Book
```

Idéntico en lógica al de `ShelfApiController` y `ReadingProgressApiController`. Los tres controladores duplican este método porque no existe un servicio compartido para la importación. Para el TFG es aceptable, aunque en producción se extraería a un `BookImporter` service.

**Flujo:**
1. Llama a `https://www.googleapis.com/books/v1/volumes/{externalId}`.
2. Si la respuesta es 200, construye una entidad `Book` con los campos del `volumeInfo`.
3. Extrae `ISBN_10` e `ISBN_13` del array `industryIdentifiers`.
4. Prefiere `thumbnail` sobre `smallThumbnail` para la portada.
5. Persiste y devuelve el libro, o `null` si falla la petición.

---

## 7. Tabla de permisos

| Acción | Anónimo | Autenticado |
|--------|---------|-------------|
| Ver reseñas | ✓ | ✓ (+ ve su propia reseña) |
| Crear/editar reseña | ✗ | ✓ (solo la propia) |
| Eliminar reseña | ✗ | ✓ (solo la propia) |

---

## 8. Resumen de endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/books/{externalId}/reviews` | Listar reseñas + estadísticas |
| `POST` | `/api/books/{externalId}/reviews` | Crear o actualizar mi reseña |
| `DELETE` | `/api/books/{externalId}/reviews` | Eliminar mi reseña |

---

# 29 — Controlador de Progreso de Lectura: análisis completo

`ReadingProgressApiController` permite a los usuarios hacer seguimiento de su lectura activa. A diferencia de las estanterías (que organizan libros) o las reseñas (que valoran libros ya leídos), el progreso de lectura registra el avance en tiempo real: páginas leídas o porcentaje completado.

---

## 1. Estructura del controlador

```php
#[Route('/api/reading-progress', name: 'api_reading_progress_')]
class ReadingProgressApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReadingProgressRepository $repo,
        private BookRepository $bookRepo,
    ) {}
```

**Inyección por constructor:** Las tres dependencias se usan en la mayoría de métodos, por lo que se inyectan por constructor en lugar de por parámetro de acción. Esta elección reduce la firma de cada método público.

**Restricción `UNIQUE(user_id, book_id)`:** Un usuario solo puede tener un registro de progreso por libro. Si intenta crear uno duplicado, el servidor devuelve el registro existente (código 200) en lugar de un error.

---

## 2. `GET /api/reading-progress` — libros en seguimiento activo

```php
#[Route('', name: 'list', methods: ['GET'])]
public function list(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $items = $this->repo->findBy(['user' => $this->getUser()], ['updatedAt' => 'DESC']);

    return $this->json(array_map(fn($p) => $this->serialize($p), $items));
}
```

**Ordenación por `updatedAt DESC`:** Los libros en los que el usuario actualizó su progreso más recientemente aparecen primero. Esto es más útil que ordenar por `startedAt`, ya que los libros activamente leídos suben a la cima de la lista.

**Sin paginación:** Se asume que un usuario no tiene decenas de libros en lectura simultánea. Si la lista fuera muy larga, habría que agregar `limit`/`offset`.

---

## 3. `POST /api/reading-progress` — iniciar seguimiento

```php
#[Route('', name: 'add', methods: ['POST'])]
public function add(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $data       = json_decode($request->getContent(), true) ?? [];
    $externalId = trim((string) ($data['externalId'] ?? ''));
    $mode       = in_array($data['mode'] ?? '', ['pages', 'percent']) ? $data['mode'] : 'percent';
    ...
}
```

**Body de la petición:**

```json
{
  "externalId": "zyTCAlFPjgYC",
  "mode": "pages",
  "totalPages": 320
}
```

**Validación del `mode` con fallback:** Si el cliente envía un modo desconocido, se usa `'percent'` por defecto en lugar de devolver un error. Es más permisivo que la validación estricta de otros controladores.

**Idempotencia al crear:**

```php
$existing = $this->repo->findOneBy(['user' => $this->getUser(), 'book' => $book]);
if ($existing) {
    return $this->json($this->serialize($existing), 200);
}
```

Si el usuario ya está rastreando el libro, se devuelve el registro existente con código 200. El cliente no necesita comprobar si ya existe antes de llamar: el servidor lo gestiona.

**`totalPages` opcional:** Si se envía, se almacena en el registro de progreso. Si no se envía, el sistema usa `$book->getPageCount()` al serializar (fallback al dato de Google Books).

---

## 4. `PATCH /api/reading-progress/{id}` — actualizar progreso

```php
#[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
public function update(int $id, Request $request): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $progress = $this->repo->find($id);
    if (!$progress || $progress->getUser() !== $this->getUser()) {
        return $this->json(['error' => 'No encontrado'], 404);
    }
    ...
}
```

**Verificación de propiedad:** `$progress->getUser() !== $this->getUser()` garantiza que el usuario solo puede modificar su propio progreso. Si otro usuario conociera el ID de un registro ajeno, recibiría 404 (en lugar de 403), ocultando la existencia del recurso.

**Campos actualizables:**

```php
if (isset($data['mode']) && in_array($data['mode'], ['pages', 'percent'])) {
    $progress->setMode($data['mode']);
}
if (array_key_exists('currentPage', $data)) {
    $progress->setCurrentPage($data['currentPage'] !== null ? max(0, (int)$data['currentPage']) : null);
}
if (array_key_exists('totalPages', $data)) {
    $progress->setTotalPages($data['totalPages'] !== null && (int)$data['totalPages'] > 0 ? (int)$data['totalPages'] : null);
}
if (array_key_exists('percent', $data)) {
    $progress->setPercent($data['percent'] !== null ? max(0, min(100, (int)$data['percent'])) : null);
}
$progress->setUpdatedAt(new \DateTimeImmutable());
```

**`isset` vs `array_key_exists`:** Se usa `array_key_exists` (en lugar de `isset`) para los campos numéricos. La diferencia es crucial:

```
isset($data['currentPage'])           → false cuando el valor es null
array_key_exists('currentPage', $data) → true incluso cuando el valor es null
```

Si el cliente envía `{ "currentPage": null }` para borrar el progreso de páginas, `isset` lo ignoraría. Con `array_key_exists` el null se propaga correctamente.

**Sanitización de rangos:**
- `currentPage`: `max(0, ...)` — nunca negativo.
- `percent`: `max(0, min(100, ...))` — clampeado entre 0 y 100.
- `totalPages`: solo se actualiza si es un entero positivo (`> 0`).

**`updatedAt` siempre se actualiza** al hacer PATCH, independientemente de qué campos cambian.

---

## 5. Dos modos de seguimiento

El campo `mode` determina cómo el usuario introduce su progreso:

| `mode` | Campos relevantes | Caso de uso |
|--------|-------------------|-------------|
| `pages` | `currentPage`, `totalPages` | El usuario prefiere introducir "estoy en la página 150 de 320" |
| `percent` | `percent` | El usuario prefiere "llevo el 47%" (ebook, sin páginas físicas) |

**`getComputedPercent()`:** Método de la entidad que calcula el porcentaje a partir del modo activo:

```php
// En la entidad ReadingProgress
public function getComputedPercent(): ?int
{
    if ($this->mode === 'percent') {
        return $this->percent;
    }
    if ($this->currentPage !== null && $this->totalPages !== null && $this->totalPages > 0) {
        return (int) round(($this->currentPage / $this->totalPages) * 100);
    }
    return null;
}
```

Esto expone siempre `computed` en la serialización, independientemente del modo. El frontend puede usar `computed` para barras de progreso sin preocuparse por el modo.

---

## 6. `DELETE /api/reading-progress/{id}` — dejar de rastrear

```php
#[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $progress = $this->repo->find($id);
    if (!$progress || $progress->getUser() !== $this->getUser()) {
        return $this->json(['error' => 'No encontrado'], 404);
    }

    $this->em->remove($progress);
    $this->em->flush();

    return $this->json(null, 204);
}
```

**204 No Content:** La respuesta de eliminación exitosa es un cuerpo vacío con código 204. Es la convención REST para operaciones de borrado.

---

## 7. Helper `serialize()`

```php
private function serialize(ReadingProgress $p): array
{
    $book = $p->getBook();
    return [
        'id'          => $p->getId(),
        'mode'        => $p->getMode(),
        'currentPage' => $p->getCurrentPage(),
        'totalPages'  => $p->getTotalPages() ?? $book->getPageCount(),
        'percent'     => $p->getPercent(),
        'computed'    => $p->getComputedPercent(),
        'startedAt'   => $p->getStartedAt()?->format(\DateTimeInterface::ATOM),
        'updatedAt'   => $p->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        'book'        => [
            'id'         => $book->getId(),
            'externalId' => $book->getExternalId(),
            'title'      => $book->getTitle(),
            'authors'    => $book->getAuthors() ?? [],
            'coverUrl'   => $book->getCoverUrl(),
            'pageCount'  => $book->getPageCount(),
        ],
    ];
}
```

**`totalPages ?? $book->getPageCount()`:** Si el usuario no especificó el total de páginas al crear el registro, se usa el número de páginas del libro según Google Books. Esto hace que `computed` funcione aunque el usuario no haya configurado `totalPages` explícitamente.

**Ejemplo de respuesta serializada:**

```json
{
  "id": 7,
  "mode": "pages",
  "currentPage": 150,
  "totalPages": 320,
  "percent": null,
  "computed": 47,
  "startedAt": "2024-03-10T09:00:00+00:00",
  "updatedAt": "2024-03-15T20:30:00+00:00",
  "book": {
    "id": 3,
    "externalId": "zyTCAlFPjgYC",
    "title": "El Quijote",
    "authors": ["Miguel de Cervantes"],
    "coverUrl": "https://books.google.com/...",
    "pageCount": 320
  }
}
```

---

## 8. Resumen de endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/reading-progress` | Libros en seguimiento activo |
| `POST` | `/api/reading-progress` | Iniciar seguimiento (idempotente) |
| `PATCH` | `/api/reading-progress/{id}` | Actualizar progreso |
| `DELETE` | `/api/reading-progress/{id}` | Dejar de rastrear |

---

# 30 — Controlador de Estanterías: análisis completo

`ShelfApiController` es el controlador más amplio del proyecto. Gestiona dos niveles de recursos: las estanterías como contenedores (`Shelf`) y los libros dentro de cada estantería (`ShelfBook`). Incluye operaciones de organización como mover libros entre estanterías y cambiar el estado de lectura.

---

## 1. Estructura del controlador

```php
#[Route('/api/shelves')]
class ShelfApiController extends AbstractController
```

**Sin nombre de ruta explícito:** A diferencia de otros controladores (que usan `name: 'api_...'`), este controlador no define un prefijo de nombre. Los nombres de ruta los genera Symfony automáticamente.

**Sin inyección por constructor:** Todas las dependencias se inyectan por parámetro de acción. Cada método declara solo lo que necesita, lo cual es especialmente útil aquí porque los endpoints de estanterías y los de libros requieren combinaciones distintas de repositorios.

---

## 2. Endpoints de estanterías (nivel contenedor)

### `GET /api/shelves` — listar estanterías

```php
#[Route('', methods: ['GET'])]
public function list(ShelfRepository $repo): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $shelves = $repo->findBy(['user' => $this->getUser()]);

    return $this->json(array_map(fn($s) => [
        'id' => $s->getId(),
        'name' => $s->getName()
    ], $shelves));
}
```

Respuesta mínima: solo `id` y `name`. Los libros no se incluyen aquí para mantener la respuesta ligera. Para obtener libros, usar `GET /api/shelves/{id}/books` o `GET /api/shelves/full`.

### `POST /api/shelves` — crear estantería

```php
$shelf->setOrderIndex(0);
$shelf->setCreatedAt(new \DateTimeImmutable());
$shelf->setUpdatedAt(new \DateTimeImmutable());
```

`orderIndex` se inicializa a `0`. Actualmente no hay un endpoint para reordenar estanterías, por lo que este campo está reservado para uso futuro.

### `PATCH /api/shelves/{id}` — renombrar

```php
$shelf = $shelfRepo->find($id);
if (!$shelf || $shelf->getUser() !== $this->getUser()) {
    return $this->json(['error' => 'Estantería no encontrada'], 404);
}
```

La verificación de propiedad combina existencia y autorización en un solo condicional. Un usuario que intenta renombrar la estantería de otro recibe 404, no 403. Esto oculta la existencia del recurso a usuarios no autorizados.

Actualiza `updatedAt` en cada renombrado para mantener una auditoría de cambios.

### `DELETE /api/shelves/{id}` — eliminar estantería

```php
$em->remove($shelf);
$em->flush();
return $this->json(null, 204);
```

Al eliminar una `Shelf`, el `orphanRemoval: true` de la relación con `ShelfBook` elimina en cascada todos los libros de esa estantería. No hace falta borrarlos manualmente.

---

## 3. Endpoints de libros en estantería (nivel elemento)

### `GET /api/shelves/{id}/books` — libros de una estantería

```php
$books = array_map(fn(ShelfBook $sb) => [
    'id'         => $sb->getId(),
    'status'     => $sb->getStatus(),
    'orderIndex' => $sb->getOrderIndex(),
    'addedAt'    => $sb->getAddedAt()?->format(\DateTimeInterface::ATOM),
    'book'       => $this->serializeBook($sb->getBook()),
], $shelf->getShelfBooks()->toArray());
```

El `id` expuesto es el de `ShelfBook` (la relación libro-estantería), no el del `Book`. Los endpoints de actualización, movimiento y eliminación usan este `bookId` (que es `ShelfBook.id`).

**Posible N+1:** `$this->serializeBook($sb->getBook())` accede a la entidad `Book` por cada `ShelfBook`. Si Doctrine no hizo eager loading de `Book` al cargar `ShelfBooks`, cada acceso lanza una consulta. Para listas largas convendría un JOIN con `addSelect`.

### `POST /api/shelves/{id}/books` — añadir libro

```php
$allowedStatuses = ['want_to_read', 'reading', 'read'];
if (!in_array($status, $allowedStatuses, true)) {
    return $this->json(['error' => 'status debe ser: ' . implode(', ', $allowedStatuses)], 400);
}
```

**Validación estricta del tercer argumento `true`:** `in_array($status, $allowedStatuses, true)` usa comparación estricta de tipos. Sin `true`, PHP haría comparación débil (type juggling), permitiendo que el número `0` pasase la validación contra un array de strings.

**Control de duplicados:**

```php
$existing = $shelfBookRepo->findOneBy(['shelf' => $shelf, 'book' => $book]);
if ($existing) {
    return $this->json(['error' => 'El libro ya está en esta estantería'], 409);
}
```

Código 409 Conflict: el recurso ya existe. La restricción `UNIQUE(shelf_id, book_id)` en base de datos también lo garantizaría a nivel SQL, pero comprobarlo antes evita una excepción de Doctrine.

**Cálculo del `orderIndex`:**

```php
$maxOrder = count($shelf->getShelfBooks());
$shelfBook->setOrderIndex($maxOrder);
```

El nuevo libro se coloca al final de la estantería. `count($shelf->getShelfBooks())` devuelve el número actual de libros, que coincide con el índice del siguiente elemento en una secuencia 0-based.

**Auto-importación desde Google Books:** Si el libro no existe localmente, se importa antes de añadirlo. Mismo mecanismo que `BookReviewApiController` y `ReadingProgressApiController`.

### `PATCH /api/shelves/{id}/books/{bookId}` — cambiar estado de lectura

```php
$shelfBook = $shelfBookRepo->find($bookId);
if (!$shelfBook || $shelfBook->getShelf() !== $shelf) {
    return $this->json(['error' => 'Entrada no encontrada'], 404);
}
```

Nótese que `$bookId` en la ruta es el ID de `ShelfBook`, no de `Book`. La verificación `$shelfBook->getShelf() !== $shelf` garantiza que la entrada pertenece a la estantería correcta (seguridad entre recursos).

El estado cambia sin modificar `orderIndex` ni `addedAt`, preservando la posición y fecha de incorporación del libro.

### `GET /api/shelves/full` — todas las estanterías con libros

```php
#[Route('/full', methods: ['GET'])]
public function listFull(ShelfRepository $shelfRepo): JsonResponse
```

**Posición de la ruta `/full`:** Este endpoint debe estar declarado **antes** que `/{id}` en el archivo para evitar que Symfony interprete `full` como un ID entero. En este controlador la declaración aparece tras `/{id}/books/{bookId}/move`, lo que en teoría podría causar conflictos. Sin embargo, el requisito `requirements: ['id' => '\d+']` en los endpoints con `{id}` limita la captura solo a números, por lo que `full` (no numérico) no colisiona.

**Caso de uso:** El frontend usa `GET /api/shelves/full` al cargar la página de estanterías para mostrar de una vez todos los libros organizados. Evita N peticiones (una por estantería).

**Potencial N+1:** Para cada estantería se accede a sus `ShelfBooks`, y para cada `ShelfBook` se serializa su `Book`. Con muchas estanterías y muchos libros, esto puede generar decenas de consultas. Para el TFG es aceptable.

### `POST /api/shelves/{id}/books/{bookId}/move` — mover a otra estantería

```php
if ($targetShelfId === $id) {
    return $this->json(['error' => 'La estantería destino es la misma que la origen'], 400);
}
```

**Validaciones encadenadas:**

1. Estantería origen existe y pertenece al usuario.
2. El `ShelfBook` existe y pertenece a la estantería origen.
3. `targetShelfId` es válido y no es el mismo que el origen.
4. Estantería destino existe y pertenece al mismo usuario.
5. El libro no está ya en la estantería destino (409 si hay duplicado).

**Operación atómica:** Mover un libro es un simple `setShelf($targetShelf)` en la entidad `ShelfBook`. Doctrine actualiza la FK `shelf_id` con un solo `UPDATE`. No hay DELETE + INSERT, lo que preserva el `id` del `ShelfBook` y el `addedAt` original.

```php
$newOrder = count($targetShelf->getShelfBooks());
$shelfBook->setShelf($targetShelf);
$shelfBook->setOrderIndex($newOrder);
$em->flush();
```

El libro se coloca al final de la estantería destino.

### `DELETE /api/shelves/{id}/books/{bookId}` — quitar libro

```php
$em->remove($shelfBook);
$em->flush();
return $this->json(null, 204);
```

Elimina el `ShelfBook`, no el `Book`. El libro permanece en la base de datos para que otras estanterías (de otros usuarios) que lo referencien no se vean afectadas.

---

## 4. Helper `serializeBook()`

```php
private function serializeBook(Book $book): array
{
    return [
        'id'            => $book->getId(),
        'externalId'    => $book->getExternalId(),
        'title'         => $book->getTitle(),
        'authors'       => $book->getAuthors() ?? [],
        'publisher'     => $book->getPublisher(),
        'publishedDate' => $book->getPublishedDate(),
        'coverUrl'      => $book->getCoverUrl(),
        'description'   => $book->getDescription(),
        'pageCount'     => $book->getPageCount(),
        'categories'    => $book->getCategories() ?? [],
        'language'      => $book->getLanguage(),
        'isbn10'        => $book->getIsbn10(),
        'isbn13'        => $book->getIsbn13(),
    ];
}
```

Es el serializador de `Book` más completo del proyecto: incluye todos los campos disponibles. Los de `BookReviewApiController` y `ReadingProgressApiController` son subconjuntos de este.

Los arrays `authors` y `categories` usan `?? []` para devolver siempre un array en JSON (nunca `null`), lo que simplifica el manejo en el frontend.

---

## 5. Tabla de rutas completa

| Método | Ruta | Acción |
|--------|------|--------|
| `GET` | `/api/shelves` | Listar estanterías (sin libros) |
| `POST` | `/api/shelves` | Crear estantería |
| `GET` | `/api/shelves/full` | Listar estanterías con todos sus libros |
| `PATCH` | `/api/shelves/{id}` | Renombrar estantería |
| `DELETE` | `/api/shelves/{id}` | Eliminar estantería (y sus libros) |
| `GET` | `/api/shelves/{id}/books` | Libros de una estantería |
| `POST` | `/api/shelves/{id}/books` | Añadir libro (auto-importa si no existe) |
| `PATCH` | `/api/shelves/{id}/books/{bookId}` | Cambiar estado de lectura |
| `POST` | `/api/shelves/{id}/books/{bookId}/move` | Mover libro a otra estantería |
| `DELETE` | `/api/shelves/{id}/books/{bookId}` | Quitar libro de la estantería |

---

# 31 — Manual de Usuario

Este manual describe paso a paso cómo utilizar la plataforma **TFGdaw** desde el punto de vista del usuario final. Está orientado a personas sin conocimientos técnicos y cubre todas las funcionalidades disponibles.

---

## Índice

1. [Introducción a la aplicación](#1-introducción-a-la-aplicación)
2. [Requisitos para el uso](#2-requisitos-para-el-uso)
3. [Registro e inicio de sesión](#3-registro-e-inicio-de-sesión)
4. [Navegación general](#4-navegación-general)
5. [Mi perfil](#5-mi-perfil)
6. [Búsqueda de libros](#6-búsqueda-de-libros)
7. [Mis estanterías](#7-mis-estanterías)
8. [Tracker de lectura](#8-tracker-de-lectura)
9. [Reseñas de libros](#9-reseñas-de-libros)
10. [Clubs de lectura](#10-clubs-de-lectura)
11. [Chats de club](#11-chats-de-club)
12. [Red social: publicaciones y seguimientos](#12-red-social-publicaciones-y-seguimientos)
13. [Notificaciones](#13-notificaciones)
14. [Configuración de privacidad](#14-configuración-de-privacidad)
15. [Preguntas frecuentes](#15-preguntas-frecuentes)

---

## 1. Introducción a la aplicación

TFGdaw es una plataforma web para lectores que combina la gestión personal de libros con funcionalidades de red social y clubs de lectura. Con ella se puede:

- Organizar los libros leídos, en lectura y pendientes en estanterías personalizadas.
- Llevar un registro del progreso de lectura de cada libro.
- Descubrir nuevos títulos buscando en un catálogo de millones de libros.
- Publicar reseñas y ver las valoraciones de la comunidad.
- Seguir a otros lectores y ver sus publicaciones en un feed personalizado.
- Unirse a clubs de lectura y participar en debates organizados por capítulos o temas.

La aplicación funciona directamente en el navegador; no es necesario instalar nada en el dispositivo.

---

## 2. Requisitos para el uso

Para utilizar la plataforma se necesita:

- Un navegador web actualizado: Google Chrome 110+, Mozilla Firefox 110+, Microsoft Edge 110+ o Safari 16+.
- Conexión a Internet.
- Una dirección de correo electrónico válida para el registro.

La aplicación es **responsive**: se adapta a pantallas de ordenador, tablet y móvil. No existe versión nativa de aplicación móvil; se accede siempre desde el navegador.

---

## 3. Registro e inicio de sesión

### 3.1 Crear una cuenta nueva

1. Acceder a la página de inicio de la aplicación.
2. Hacer clic en el botón **"Registrarse"** de la barra de navegación o en el hero de la página principal.
3. Completar el formulario de registro:
   - **Email**: dirección de correo electrónico. Debe ser única en el sistema.
   - **Contraseña**: mínimo 6 caracteres. Se recomienda usar una combinación de letras, números y símbolos.
   - **Confirmar contraseña**: escribir la misma contraseña para verificar que no haya errores tipográficos.
4. Hacer clic en **"Crear cuenta"**.
5. Si todos los datos son correctos, la cuenta se creará automáticamente y se redirigirá a la página de inicio de sesión.

> **Nota:** El nombre de usuario visible (el que verán los demás) se genera automáticamente a partir del email. Podrá cambiarse en cualquier momento desde el perfil.

**Errores frecuentes en el registro:**

| Error mostrado | Causa | Solución |
|----------------|-------|----------|
| "Este email ya está registrado" | La dirección ya tiene cuenta | Usar otro email o recuperar la contraseña |
| "La contraseña debe tener al menos 6 caracteres" | Contraseña demasiado corta | Introducir una contraseña más larga |
| "Las contraseñas no coinciden" | Error tipográfico en la confirmación | Volver a escribir la contraseña con cuidado |

---

### 3.2 Iniciar sesión

1. Hacer clic en **"Iniciar sesión"** en la barra de navegación.
2. Introducir el **email** y la **contraseña** registrados.
3. Hacer clic en **"Entrar"**.
4. Tras un inicio de sesión correcto, se redirigirá a la página principal con el feed de publicaciones visible.

La sesión se mantiene activa aunque se cierre el navegador o se recargue la página. No es necesario volver a iniciar sesión salvo que se cierre la sesión manualmente o transcurra un tiempo prolongado de inactividad.

---

### 3.3 Cerrar sesión

1. En la barra de navegación, hacer clic sobre el icono de usuario o el nombre visible.
2. Seleccionar **"Cerrar sesión"** en el menú desplegable.
3. La sesión quedará cerrada y se redirigirá a la página de inicio.

> Al cerrar sesión se eliminan los datos de autenticación del navegador. Será necesario volver a introducir las credenciales para acceder a contenido privado.

---

## 4. Navegación general

La barra de navegación superior está presente en todas las páginas y ofrece acceso directo a las secciones principales:

| Elemento | Destino | Disponible para |
|----------|---------|-----------------|
| Logotipo / nombre de la app | Página de inicio (`/`) | Todos |
| "Libros" | Buscador de libros (`/books`) | Todos |
| "Clubs" | Listado de clubs (`/clubs`) | Todos |
| "Mis estanterías" | Gestión de estanterías (`/shelves`) | Usuarios con sesión |
| Icono de usuario | Perfil y opciones de cuenta | Usuarios con sesión |
| "Registrarse" / "Iniciar sesión" | Formularios de acceso | Visitantes |

En **dispositivos móviles y tablets**, la barra de navegación se contrae en un menú hamburguesa (icono de tres líneas horizontales). Al pulsarlo se despliegan todas las opciones de navegación.

---

## 5. Mi perfil

El perfil es la sección personal donde se gestiona la información de la cuenta, las publicaciones propias y la configuración de privacidad.

Para acceder: hacer clic en el icono de usuario de la barra de navegación y seleccionar **"Mi perfil"**, o navegar directamente a `/profile`.

---

### 5.1 Editar información personal

En la sección **"Información personal"** se puede:

- **Nombre visible**: el nombre que verán los demás usuarios. Puede contener letras, números y guiones bajos. Debe ser único en la plataforma.
- **Biografía**: texto libre de hasta 255 caracteres para presentarse. Aparece en el perfil público.

Para guardar los cambios, hacer clic en **"Guardar cambios"** después de editar los campos deseados.

---

### 5.2 Cambiar el avatar

El avatar es la imagen que representa al usuario en publicaciones, comentarios y en el perfil.

1. En la sección de perfil, situar el cursor sobre la imagen de avatar actual.
2. Aparecerá un icono de cámara superpuesto.
3. Hacer clic sobre él para abrir el selector de archivos.
4. Seleccionar una imagen del dispositivo (formatos admitidos: JPG, PNG, GIF, WEBP; tamaño recomendado: al menos 200×200 píxeles).
5. La imagen se subirá y actualizará automáticamente.

Si no se ha subido ningún avatar, se muestra una imagen generada automáticamente con las iniciales del nombre visible.

---

### 5.3 Cambiar la contraseña

1. En el perfil, localizar la sección **"Cambiar contraseña"**.
2. Introducir la **contraseña actual** para confirmar la identidad.
3. Introducir la **nueva contraseña** (mínimo 6 caracteres).
4. Confirmar la nueva contraseña.
5. Hacer clic en **"Actualizar contraseña"**.

Si la contraseña actual no es correcta, se mostrará un mensaje de error y no se realizará el cambio.

---

### 5.4 Ver seguidores y seguidos

En la parte superior del perfil se muestran los contadores de **Seguidores** y **Siguiendo**. Al hacer clic sobre cualquiera de ellos se abre un modal con la lista de usuarios correspondiente.

Desde la lista de **seguidores** propios es posible eliminar a un seguidor haciendo clic en el botón con el icono de cruz (✕) que aparece junto a su nombre. El seguidor desaparecerá de la lista sin que reciba ninguna notificación.

---

## 6. Búsqueda de libros

La búsqueda de libros permite explorar el catálogo de Google Books directamente desde la plataforma. No es necesario tener sesión iniciada para buscar.

Para acceder: hacer clic en **"Libros"** en la barra de navegación.

---

### 6.1 Realizar una búsqueda

1. Escribir el término de búsqueda en el campo de texto (título, autor, ISBN, etc.).
2. Los resultados aparecen automáticamente mientras se escribe, sin necesidad de pulsar ningún botón.
3. Cada resultado muestra: portada del libro, título, autores, año de publicación y un extracto de la descripción.
4. Hacer clic sobre cualquier tarjeta de resultado para ver el detalle completo del libro.

---

### 6.2 Ver el detalle de un libro

La página de detalle muestra:

- **Portada** en tamaño ampliado.
- **Metadatos completos**: título, subtítulo, autores, editorial, fecha de publicación, número de páginas, idioma, categorías e ISBNs.
- **Sinopsis** del libro.
- **Valoración de la comunidad**: puntuación media con estrellas y número de reseñas.
- **Reseñas individuales** de otros usuarios de la plataforma.

Si se tiene sesión iniciada, aparecen también los botones de acción:

- **"+ Añadir a estantería"**: permite añadir el libro a una de las estanterías propias.
- **"Estoy leyendo"**: activa el tracker de lectura activa para este libro.

---

### 6.3 Añadir un libro a una estantería desde la búsqueda

1. En la página de detalle del libro, hacer clic en **"+ Añadir a estantería"**.
2. Se abre un panel lateral con la lista de estanterías disponibles.
3. Seleccionar la estantería deseada.
4. Opcionalmente, elegir el estado de lectura: **Quiero leer**, **Leyendo** o **Leído**.
5. Hacer clic en **"Añadir"**.

El libro quedará guardado en la estantería seleccionada y podrá consultarse en cualquier momento desde la sección "Mis estanterías".

---

## 7. Mis estanterías

Las estanterías son colecciones personales de libros organizadas por el propio usuario. Pueden representar listas temáticas, estados de lectura, géneros favoritos o cualquier otra categoría que el usuario desee.

Para acceder: hacer clic en **"Mis estanterías"** en la barra de navegación. Requiere sesión iniciada.

---

### 7.1 Crear una estantería

1. En la página de estanterías, hacer clic en el botón **"Nueva estantería"** (visible en el panel lateral o en la parte superior de la página).
2. Introducir el nombre deseado para la estantería.
3. Hacer clic en **"Crear"** o pulsar Enter.

La nueva estantería aparecerá en el listado del panel lateral y estará lista para añadir libros.

---

### 7.2 Renombrar o eliminar una estantería

Para **renombrar**: 
1. Situar el cursor sobre el nombre de la estantería en el panel lateral.
2. Aparecerá un icono de lápiz. Hacer clic sobre él.
3. Editar el nombre directamente en el campo de texto.
4. Pulsar Enter para confirmar.

Para **eliminar**:
1. Situar el cursor sobre el nombre de la estantería.
2. Hacer clic en el icono de papelera que aparece.
3. Confirmar la eliminación en el diálogo que se muestra.

> **Advertencia:** al eliminar una estantería se eliminan también todos los libros que contiene. Esta acción no se puede deshacer.

---

### 7.3 Gestionar los libros de una estantería

Al seleccionar una estantería en el panel lateral, el panel principal muestra todos sus libros. Para cada libro se puede:

- **Cambiar el estado de lectura**: usar el selector desplegable que aparece bajo la portada del libro (Quiero leer / Leyendo / Leído).
- **Mover a otra estantería**: abrir el menú contextual del libro (icono de tres puntos) y seleccionar "Mover a…". Se selecciona la estantería destino en el submenú.
- **Eliminar de la estantería**: abrir el menú contextual y seleccionar "Quitar de la estantería". El libro no se elimina del sistema, solo de esa estantería.

---

## 8. Tracker de lectura

El tracker de lectura permite registrar el avance en un libro que se está leyendo actualmente, ya sea por número de páginas o por porcentaje completado.

---

### 8.1 Iniciar el seguimiento de un libro

Desde la página de detalle de cualquier libro, hacer clic en **"Estoy leyendo"**. El libro se añadirá al panel de lectura activa.

También se puede activar desde la página de estanterías: al cambiar el estado de un libro a "Leyendo", se ofrece la opción de activar el tracker para ese libro.

---

### 8.2 Actualizar el progreso

En la página **"Mis estanterías"**, el panel de **lectura activa** aparece en la parte superior. Para cada libro en seguimiento se muestra:

- **Portada y título** del libro, con enlace a su página de detalle.
- **Barra de progreso visual** que indica el porcentaje completado.
- **Selector de modo**: cambiar entre modo "Páginas" y modo "Porcentaje".
- **Campo de entrada**: introducir la página actual (modo páginas) o el porcentaje completado (modo porcentaje).

Para actualizar:
1. Seleccionar el modo deseado (páginas o porcentaje).
2. Introducir el valor actual.
3. Hacer clic en **"Actualizar"** o pulsar Enter.

La barra de progreso se actualizará de forma inmediata.

---

### 8.3 Finalizar el seguimiento

Cuando se termine el libro o se quiera dejar de registrar el progreso:
1. En la tarjeta del libro en el panel de lectura activa, hacer clic en el botón con el icono de papelera.
2. El libro desaparecerá del panel de lectura activa, pero seguirá en la estantería correspondiente.

---

## 9. Reseñas de libros

Las reseñas permiten compartir la opinión sobre un libro y ver las valoraciones de otros lectores de la plataforma.

---

### 9.1 Publicar una reseña

1. Navegar a la página de detalle del libro (buscándolo en el buscador o accediendo desde la estantería).
2. En la sección **"Valoraciones de la comunidad"**, localizar el formulario de reseña (solo visible para usuarios con sesión iniciada).
3. Seleccionar una puntuación del **1 al 5 estrellas** haciendo clic sobre las estrellas del selector interactivo.
4. Opcionalmente, escribir un comentario en el campo de texto.
5. Hacer clic en **"Publicar reseña"**.

La reseña aparecerá de forma inmediata en la sección de valoraciones del libro. Las estadísticas (puntuación media y número de reseñas) se actualizarán automáticamente.

> Solo se puede publicar una reseña por libro. Si ya existe una reseña propia, se mostrará en modo lectura con las opciones de **editar** y **eliminar**.

---

### 9.2 Editar o eliminar una reseña

- **Editar**: en la reseña propia, hacer clic en el botón **"Editar"**. Se abrirá de nuevo el formulario con los valores actuales. Modificar lo deseado y hacer clic en **"Guardar"**.
- **Eliminar**: hacer clic en el botón **"Eliminar"** de la reseña propia. Se pedirá confirmación antes de borrarla definitivamente.

---

## 10. Clubs de lectura

Los clubs de lectura son grupos de usuarios que leen un libro en común y debaten sobre él. Pueden ser públicos (acceso libre) o privados (acceso bajo solicitud).

Para acceder al listado de clubs: hacer clic en **"Clubs"** en la barra de navegación.

---

### 10.1 Explorar clubs

En la página de clubs se muestra el listado de todos los clubs disponibles. Para cada club se indica:
- Nombre y descripción breve.
- Tipo: **Público** o **Privado** (indicado con un icono de candado en los privados).
- Número de miembros.
- Libro del mes activo (si tiene uno asignado).
- Rol del usuario en el club (Administrador / Miembro / ninguno).

Se puede buscar un club por nombre utilizando el campo de búsqueda en la parte superior de la lista.

---

### 10.2 Unirse a un club

**Club público:**
1. En el listado de clubs, localizar el club deseado y hacer clic en **"Unirse"**.
2. El acceso es inmediato: el usuario pasa a ser miembro del club.

**Club privado:**
1. Hacer clic en **"Solicitar acceso"** en el club deseado.
2. Se enviará una solicitud al administrador del club.
3. El botón pasará a mostrar **"Solicitud enviada"** mientras se espera la respuesta.
4. Si la solicitud es aprobada, el usuario recibirá una notificación y podrá acceder al club.
5. Si es rechazada, la solicitud desaparecerá y se podrá volver a intentar.

---

### 10.3 Ver el detalle de un club

Al hacer clic sobre un club se accede a su página de detalle. Contiene tres pestañas:

**Pestaña "Chats"** (visible solo para miembros):
- Lista de hilos de debate del club.
- Cada hilo muestra el título, si está abierto o cerrado y el número de mensajes.
- Hacer clic sobre un hilo para abrirlo y leer o enviar mensajes.

**Pestaña "Miembros"**:
- Lista de todos los miembros del club con su rol (Administrador / Miembro) y fecha de ingreso.
- En clubs públicos es visible para todos. En clubs privados, solo para miembros.

**Pestaña "Solicitudes"** (solo para administradores de clubs privados):
- Lista de solicitudes de ingreso pendientes.
- Cada solicitud muestra el nombre del usuario solicitante y la fecha.
- Los administradores pueden **aprobar** o **rechazar** cada solicitud con los botones correspondientes.

---

### 10.4 Crear un club

1. En la página de clubs, hacer clic en el botón **"+ Nuevo club"** (solo visible para usuarios con sesión).
2. En el formulario que aparece, completar:
   - **Nombre del club** (obligatorio).
   - **Descripción** (opcional): texto libre que describe el propósito del club.
   - **Visibilidad**: seleccionar **Público** (cualquiera puede unirse directamente) o **Privado** (requiere aprobación).
3. Hacer clic en **"Crear club"**.

El creador del club se convierte automáticamente en su administrador.

---

### 10.5 Administrar un club propio

Como **administrador de un club**, se tienen acceso a las siguientes opciones adicionales en la página de detalle:

**Libro del mes:**
- En la sección de información del club, hacer clic en **"Establecer libro del mes"**.
- Se abre un buscador de libros integrado. Buscar y seleccionar el libro deseado.
- Opcionalmente, establecer fechas de inicio y fin de la lectura.
- El libro del mes aparecerá destacado en la portada del club para todos los miembros.

**Gestión de miembros** (pestaña "Miembros"):
- Al lado de cada miembro aparece un icono de expulsión. Hacer clic para expulsar al miembro del club.

**Abandonar el club:**
- Un administrador no puede abandonar el club si hay otros miembros. Primero debe transferir el rol de administrador a otro miembro o expulsar a todos.

---

## 11. Chats de club

Los chats son hilos de debate organizados dentro de un club. Solo los administradores del club pueden crear y gestionar los hilos; cualquier miembro puede participar en ellos.

---

### 11.1 Leer mensajes de un hilo

1. Acceder al club y hacer clic en la pestaña **"Chats"**.
2. Hacer clic sobre el título del hilo que se quiere leer.
3. Se mostrará el listado de mensajes en orden cronológico (más antiguo arriba, más reciente abajo).
4. Los mensajes paginados se cargan de más antiguos a más recientes.

Los hilos marcados como **cerrados** (con icono de candado) se pueden leer pero no se pueden enviar mensajes nuevos en ellos.

---

### 11.2 Enviar un mensaje

1. Al final del hilo abierto, localizar el campo de texto con el texto "Escribe un mensaje…".
2. Escribir el mensaje.
3. Pulsar **Enter** o hacer clic en el botón de envío (icono de avión de papel).

El mensaje aparecerá inmediatamente en el hilo con el nombre y avatar del usuario.

---

### 11.3 Eliminar un mensaje propio

Al situar el cursor sobre un mensaje propio aparece un icono de papelera. Hacer clic sobre él para eliminar el mensaje. No se pide confirmación; la eliminación es inmediata.

Los **administradores del club** pueden eliminar cualquier mensaje del hilo, no solo los propios.

---

### 11.4 Crear un hilo (solo administradores)

1. En la pestaña "Chats" del club, hacer clic en el botón **"+ Nuevo hilo"**.
2. Introducir el título del hilo (por ejemplo: "Capítulos 1-5", "Personajes principales", "Opinión final").
3. Hacer clic en **"Crear"**.

El hilo aparecerá en la lista con estado "Abierto" y ya estará disponible para que los miembros envíen mensajes.

---

## 12. Red social: publicaciones y seguimientos

La parte social de la plataforma permite seguir a otros lectores y compartir publicaciones relacionadas con la lectura.

---

### 12.1 Seguir a un usuario

Para seguir a otro usuario:
1. Acceder a su perfil haciendo clic en su nombre o avatar (desde un post, un comentario, la lista de miembros de un club, etc.), o buscar el perfil directamente en `/users/{id}`.
2. En la cabecera del perfil, hacer clic en el botón **"Seguir"**.

- Si el perfil es **público**: el seguimiento se acepta de forma inmediata. El botón cambia a **"Siguiendo"**.
- Si el perfil es **privado**: se envía una solicitud. El botón pasa a mostrar **"Solicitud enviada"**. El usuario recibirá una notificación y podrá aceptar o rechazar la solicitud.

Para **dejar de seguir** a alguien, hacer clic en el botón **"Siguiendo"** y confirmar en el diálogo que aparece.

---

### 12.2 Publicar una entrada

1. Acceder al perfil propio (`/profile`).
2. En la sección **"Mis publicaciones"**, localizar el formulario de nueva publicación.
3. Hacer clic en el área de imagen o en el icono de cámara para seleccionar una imagen del dispositivo (formatos admitidos: JPG, PNG, GIF, WEBP).
4. Una vez seleccionada, aparecerá una previsualización de la imagen.
5. Opcionalmente, escribir una descripción en el campo de texto.
6. Hacer clic en **"Publicar"**.

La publicación aparecerá en la sección de publicaciones del perfil y en el feed de los usuarios que siguen al autor.

---

### 12.3 Interactuar con publicaciones

Las publicaciones aparecen en el **feed de la página de inicio** (solo para usuarios con sesión) y en los **perfiles de usuario**.

Para cada publicación se puede:

- **Dar "me gusta"**: hacer clic en el icono de corazón. El contador se actualizará de forma inmediata. Volver a hacer clic quita el like.
- **Ver comentarios**: hacer clic en el icono de comentario o en el contador de comentarios. Se despliegan los comentarios existentes.
- **Comentar**: en la sección de comentarios desplegada, escribir en el campo de texto y pulsar Enter o hacer clic en enviar.
- **Eliminar un comentario propio**: hacer clic en el icono de papelera que aparece junto al comentario (solo visible en los comentarios propios).
- **Eliminar la publicación** (solo el autor): hacer clic en el icono de papelera que aparece en la esquina de la publicación.

---

### 12.4 El feed de publicaciones

La página de inicio muestra el **feed personalizado**: las publicaciones más recientes de los usuarios seguidos y las propias, ordenadas de más reciente a más antigua.

Si el feed está vacío (por ejemplo, en una cuenta nueva sin seguidos), se mostrará un mensaje con sugerencia de explorar el buscador de libros y comenzar a seguir a otros lectores.

---

## 13. Notificaciones

El sistema de notificaciones informa al usuario de eventos relevantes que han ocurrido mientras no estaba conectado.

Las notificaciones se reciben por las siguientes acciones:

| Evento | Descripción |
|--------|-------------|
| Alguien empieza a seguirte | Solo en cuentas públicas |
| Solicitud de seguimiento recibida | Solo en cuentas privadas |
| Tu solicitud de seguimiento fue aceptada | — |
| Alguien da like a tu publicación | — |
| Alguien comenta tu publicación | — |
| Solicitud de ingreso a tu club | Solo para administradores |
| Tu solicitud de ingreso fue aprobada | — |
| Tu solicitud de ingreso fue rechazada | — |

Las notificaciones no leídas se indican con un punto o contador en el icono de la campana de la barra de navegación. Al hacer clic se despliega el panel de notificaciones con las entradas más recientes. Las notificaciones se marcan como leídas al acceder al panel.

---

## 14. Configuración de privacidad

La configuración de privacidad controla qué información es visible para otros usuarios de la plataforma. Se accede desde **Mi perfil → Privacidad**.

---

### 14.1 Perfil privado

Al activar la opción **"Perfil privado"**:

- Los usuarios que no te siguen no podrán ver tus estanterías ni tu participación en clubs.
- Cualquier nuevo usuario que intente seguirte deberá enviar una solicitud que deberás aprobar manualmente desde las notificaciones.
- Los usuarios que ya te seguían antes de activar la privacidad siguen viéndote sin necesidad de reconfirmación.
- Tu nombre, avatar y biografía siguen siendo visibles para todos.

Para **aceptar o rechazar solicitudes de seguimiento** pendientes:
1. Acceder al panel de notificaciones.
2. Localizar las notificaciones de tipo "Solicitud de seguimiento".
3. Hacer clic en **"Aceptar"** o **"Rechazar"** según corresponda.

---

### 14.2 Visibilidad de estanterías y clubs

De forma independiente al modo privado, se puede controlar:

- **"Estanterías públicas"**: si está activo, cualquier usuario puede ver las estanterías desde el perfil público. Si está desactivado, solo los seguidores aceptados pueden verlas.
- **"Clubes públicos"**: igual que el anterior, pero para la participación en clubs.

Estos ajustes se guardan automáticamente al cambiar el estado del toggle.

---

## 15. Preguntas frecuentes

**¿Puedo tener un libro en varias estanterías a la vez?**
Sí. Un mismo libro puede estar en varias estanterías diferentes (por ejemplo, en "Ciencia Ficción" y en "Favoritos" simultáneamente). Lo que no es posible es tener el mismo libro dos veces en la misma estantería.

**¿Qué pasa si borro una estantería?**
Se eliminan todos los libros que contiene. El libro en sí no desaparece del sistema ni de otras estanterías; solo se elimina la asociación con esa estantería concreta.

**¿Puedo cambiar mi email?**
Actualmente no está disponible el cambio de email desde el perfil. Para solucionar esto es necesario contactar con el administrador de la plataforma.

**¿Los libros provienen del catálogo de Google Books?**
Sí. El buscador consulta directamente el catálogo de Google Books. Los libros se guardan en la base de datos local de la plataforma la primera vez que algún usuario los añade a una estantería, escribe una reseña o los establece como libro del mes de un club.

**¿Puedo usar la plataforma sin registrarme?**
Se puede explorar el buscador de libros, ver los clubs y los perfiles públicos sin necesidad de cuenta. Para gestionar estanterías, publicar, unirse a clubs o participar en los chats es necesario registrarse.

**¿Qué formatos de imagen admite la plataforma para publicaciones y avatares?**
Se admiten los formatos JPG, JPEG, PNG, GIF y WEBP.

**¿Cómo cancelo una solicitud de seguimiento que envié?**
Acceder al perfil del usuario al que se envió la solicitud y hacer clic en el botón **"Solicitud enviada"**. Se mostrará la opción de cancelar la solicitud.

**¿Puedo abandonar un club del que soy administrador?**
Si eres el único administrador del club y hay otros miembros, no podrás abandonarlo directamente. Primero debes transferir el rol de administrador a otro miembro (contactando con el administrador de la plataforma si no hay otra opción) o expulsar a todos los miembros y luego abandonarlo.

---

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

---

# 33 — Accesibilidad WAI-A y Comunicación Asíncrona

Este documento acredita el cumplimiento de dos requisitos técnicos obligatorios del ciclo DAW: el nivel mínimo de accesibilidad web **WAI-A** (WCAG 2.1, nivel A) y el uso de **comunicación asíncrona** entre cliente y servidor.

---

## 1. Accesibilidad web — Nivel WAI-A (WCAG 2.1)

Las Pautas de Accesibilidad para el Contenido Web (WCAG 2.1), publicadas por el W3C, organizan los criterios de accesibilidad en tres niveles: A (mínimo), AA (recomendado) y AAA (avanzado). El nivel A es el umbral mínimo exigido en el ciclo DAW y constituye el objetivo de este proyecto.

A continuación se detallan los criterios de nivel A aplicados en TFGdaw, organizados por principio WCAG.

---

### 1.1 Principio: Perceptible

Los contenidos deben poder ser percibidos por todos los usuarios, independientemente de sus capacidades sensoriales.

**Criterio 1.1.1 — Contenido no textual (Nivel A)**

Todas las imágenes de la aplicación incluyen texto alternativo (`alt`) descriptivo:

```html
<!-- Portada de libro en BookDetailPage.tsx -->
<img
  src={coverUrl}
  alt={`Portada de ${book.title}`}
  className="book-cover"
/>

<!-- Avatar de usuario en PostCard.tsx -->
<img
  src={avatarUrl}
  alt={`Avatar de ${user.displayName}`}
  className="avatar"
/>
```

Las imágenes puramente decorativas (fondos, separadores) se implementan mediante CSS (`background-image`) o con `alt=""` vacío, para que los lectores de pantalla las ignoren.

**Criterio 1.3.1 — Información y relaciones (Nivel A)**

La estructura de la interfaz se comunica mediante marcado semántico HTML5:

- `<header>` para la barra de navegación principal.
- `<main>` para el contenido principal de cada página.
- `<nav>` para los menús de navegación.
- `<section>` y `<article>` para agrupar contenido relacionado.
- `<h1>`, `<h2>`, `<h3>` con jerarquía coherente en cada página.
- `<ul>` y `<li>` para listas de resultados, miembros y notificaciones.
- `<form>`, `<label>`, `<input>` con asociación explícita mediante `htmlFor` / `id`.

Ejemplo de formulario correctamente etiquetado en `LoginPage.tsx`:

```tsx
<form onSubmit={handleSubmit}>
  <div className="form-group">
    <label htmlFor="email" className="form-label">
      Correo electrónico
    </label>
    <input
      id="email"
      type="email"
      className="form-control"
      value={email}
      onChange={e => setEmail(e.target.value)}
      required
      autoComplete="email"
    />
  </div>
  <div className="form-group">
    <label htmlFor="password" className="form-label">
      Contraseña
    </label>
    <input
      id="password"
      type="password"
      className="form-control"
      value={password}
      onChange={e => setPassword(e.target.value)}
      required
      autoComplete="current-password"
    />
  </div>
  <button type="submit" className="btn btn-primary">
    Entrar
  </button>
</form>
```

**Criterio 1.4.1 — Uso del color (Nivel A)**

El color nunca es el único medio para transmitir información. Los estados de lectura (`want_to_read`, `reading`, `read`) se distinguen tanto por color como por etiqueta de texto. Los errores de validación se indican con un icono y un texto descriptivo, no solo con borde rojo.

---

### 1.2 Principio: Operable

Los componentes de la interfaz deben poder ser operados por todos los usuarios.

**Criterio 2.1.1 — Teclado (Nivel A)**

Todos los elementos interactivos (botones, enlaces, campos de formulario, selects, checkboxes y toggles) son accesibles mediante teclado. El orden de tabulación (`:focus`) sigue el flujo visual de la página de arriba a abajo y de izquierda a derecha, sin trampas de teclado.

Los modales (lista de seguidores, formulario de nuevo club, etc.) gestionan el foco correctamente: al abrirse, el foco se mueve al interior del modal; al cerrarse, vuelve al elemento que lo abrió.

**Criterio 2.4.2 — Título de página (Nivel A)**

Cada página tiene un título descriptivo único en el `<title>` del documento HTML:

| Ruta | Título |
|------|--------|
| `/` | TFGdaw — Plataforma de Clubes de Lectura |
| `/login` | Iniciar sesión — TFGdaw |
| `/register` | Crear cuenta — TFGdaw |
| `/books` | Buscar libros — TFGdaw |
| `/clubs` | Clubs de lectura — TFGdaw |
| `/shelves` | Mis estanterías — TFGdaw |
| `/profile` | Mi perfil — TFGdaw |

**Criterio 2.4.3 — Orden del foco (Nivel A)**

El orden de los elementos en el DOM coincide con el orden visual, por lo que el recorrido con tabulador es predecible y coherente en todas las páginas.

---

### 1.3 Principio: Comprensible

El contenido y el funcionamiento de la interfaz deben ser comprensibles.

**Criterio 3.1.1 — Idioma de la página (Nivel A)**

El atributo `lang` del elemento `<html>` está definido como `es` (español):

```html
<!-- index.html -->
<html lang="es">
```

**Criterio 3.3.1 — Identificación de errores (Nivel A)**

Cuando un formulario se envía con datos inválidos, se muestra un mensaje de error descriptivo junto al campo afectado. Los mensajes indican qué campo tiene el error y por qué, no solo que hay un error genérico.

Ejemplo en `RegisterPage.tsx`:

```tsx
{errors.password && (
  <span className="field-error" role="alert">
    {errors.password}
  </span>
)}
```

El atributo `role="alert"` hace que los lectores de pantalla anuncien el error automáticamente al aparecer en el DOM.

**Criterio 3.3.2 — Etiquetas o instrucciones (Nivel A)**

Todos los campos de formulario tienen una etiqueta (`<label>`) visible y asociada. Los campos con restricciones de formato incluyen texto de ayuda o placeholder que indica el formato esperado (por ejemplo, "mínimo 6 caracteres" en el campo de contraseña).

---

### 1.4 Principio: Robusto

El contenido debe poder ser interpretado de forma fiable por una amplia variedad de agentes de usuario.

**Criterio 4.1.1 — Análisis sintáctico (Nivel A)**

El HTML generado por React no contiene errores de anidamiento ni atributos duplicados. Los elementos interactivos personalizados (botones de like, toggles de privacidad) son implementados con elementos nativos HTML (`<button>`, `<input type="checkbox">`) en lugar de `<div>` con `onClick`, lo que garantiza la compatibilidad con tecnologías de asistencia.

**Criterio 4.1.2 — Nombre, función, valor (Nivel A)**

Los elementos interactivos cuentan con nombres accesibles. Los iconos SVG sin texto visible incluyen `aria-label` o están acompañados de un `<span className="sr-only">` (visualmente oculto pero leído por lectores de pantalla):

```tsx
<!-- Botón de like con icono SVG -->
<button
  className="like-btn"
  onClick={handleLike}
  aria-label={liked ? 'Quitar me gusta' : 'Dar me gusta'}
  aria-pressed={liked}
>
  <HeartIcon />
</button>
```

---

### 1.5 Diseño responsive como complemento a la accesibilidad

El diseño responsive no es en sí mismo un criterio de accesibilidad WCAG, pero contribuye significativamente a garantizar que los usuarios con baja visión que aumentan el zoom del navegador o usan dispositivos de pantalla pequeña puedan acceder al contenido sin pérdida de información.

La aplicación se ha probado en los siguientes tamaños de pantalla:

| Dispositivo | Ancho | Comportamiento |
|-------------|-------|----------------|
| Móvil pequeño | 320px | Una columna, texto legible, sin scroll horizontal |
| Móvil estándar | 375px | Una columna, navbar colapsada |
| Tablet | 768px | Dos columnas en listados, sidebar visible |
| Desktop | 1280px | Layout completo con sidebar y panel principal |
| Desktop wide | 1920px | Max-width aplicado para evitar líneas de texto excesivamente largas |

---

## 2. Comunicación asíncrona con el servidor

El criterio de *Entornos Cliente* del ciclo DAW exige el uso de mecanismos de comunicación asíncrona con el servidor. TFGdaw implementa esta comunicación mediante la **Fetch API** de JavaScript de forma extensiva en todo el frontend.

---

### 2.1 Patrón general: Fetch API con async/await

Todas las peticiones al backend se realizan de forma asíncrona usando la Fetch API nativa del navegador, envueltas en funciones con `async/await` para simplificar el manejo de promesas y errores:

```typescript
// src/api/client.ts — función base de todas las peticiones
export async function apiFetch<T>(
  path: string,
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' = 'GET',
  body?: unknown
): Promise<T> {
  const options: RequestInit = {
    method,
    credentials: 'include',           // enviar cookie de sesión
    headers: { 'Content-Type': 'application/json' },
  }
  if (body !== undefined) {
    options.body = JSON.stringify(body)
  }
  const res = await fetch(`/api${path}`, options)
  if (res.status === 204) return undefined as T
  const data = await res.json()
  if (!res.ok) throw new Error(data.error ?? 'Error desconocido')
  return data as T
}
```

La función `apiFetch` es el único punto de acceso a la API en todo el frontend. Centraliza el manejo de cookies, los headers, la serialización del body y la propagación de errores, evitando código duplicado en cada módulo.

---

### 2.2 Comunicación asíncrona en los componentes React

Los componentes React realizan peticiones asíncronas al montarse (`useEffect`) o en respuesta a acciones del usuario (handlers). La interfaz permanece responsive durante la carga gracias al estado `loading` y al componente `<Spinner>`:

```typescript
// Ejemplo: carga del feed en HomePage.tsx
useEffect(() => {
  if (!user) return
  setLoading(true)
  postsApi.feed()
    .then(data => setPosts(data))
    .catch(() => setError('No se pudo cargar el feed'))
    .finally(() => setLoading(false))
}, [user])
```

El usuario ve un indicador de carga mientras la petición está en vuelo, y un mensaje de error si falla, sin que la página se congele en ningún momento.

---

### 2.3 Actualización optimista de la interfaz

Para operaciones de alta frecuencia como los likes, la interfaz se actualiza de forma **optimista**: el estado visual cambia inmediatamente al hacer clic, sin esperar la respuesta del servidor. Si el servidor devuelve un error, el estado se revierte:

```typescript
// PostCard.tsx — like optimista
const handleLike = async () => {
  // actualización inmediata en la UI
  setLocalPost(prev => ({
    ...prev,
    liked: !prev.liked,
    likes: prev.liked ? prev.likes - 1 : prev.likes + 1,
  }))
  try {
    const result = await postsApi.like(post.id)
    // confirmar con el valor real del servidor
    setLocalPost(prev => ({ ...prev, liked: result.liked, likes: result.likes }))
  } catch {
    // revertir si falla
    setLocalPost(prev => ({
      ...prev,
      liked: !prev.liked,
      likes: prev.liked ? prev.likes - 1 : prev.likes + 1,
    }))
  }
}
```

---

### 2.4 Carga diferida de contenido

Los comentarios de las publicaciones no se cargan al renderizar la tarjeta, sino únicamente cuando el usuario despliega la sección de comentarios por primera vez. Esto reduce la carga inicial de la página y evita peticiones innecesarias:

```typescript
// PostCard.tsx — carga diferida de comentarios
const handleToggleComments = async () => {
  if (!showComments && !commentsLoaded) {
    const data = await postsApi.comments(post.id)
    setComments(data)
    setCommentsLoaded(true)
  }
  setShowComments(prev => !prev)
}
```

---

### 2.5 Módulos de API organizados por dominio

Para mantener el código organizado y evitar que los componentes React contengan lógica de red, todas las llamadas HTTP se encapsulan en módulos dedicados en `src/api/`:

| Módulo | Peticiones que gestiona |
|--------|------------------------|
| `auth.ts` | Login, logout, registro, `/me` |
| `books.ts` | Búsqueda y detalle de libros |
| `shelves.ts` | CRUD de estanterías y gestión de libros |
| `clubs.ts` | CRUD de clubs, membresías, libro del mes |
| `chats.ts` | Hilos y mensajes de club |
| `profile.ts` | Perfil, avatar, privacidad, contraseña |
| `reviews.ts` | Reseñas por libro |
| `readingProgress.ts` | Tracker de lectura |
| `posts.ts` | Feed, publicaciones, likes, comentarios |
| `follow.ts` | Seguir, dejar de seguir, seguidores, siguiendo |

Esta separación hace que cada componente React importe únicamente el módulo que necesita, manteniendo el código desacoplado y fácil de mantener.

---

### 2.6 Validación en el cliente antes de enviar al servidor

Antes de realizar cualquier petición al backend, los formularios validan los datos en el cliente para proporcionar retroalimentación inmediata y evitar peticiones con datos claramente incorrectos:

```typescript
// RegisterPage.tsx — validación cliente antes del fetch
const handleSubmit = async (e: FormEvent) => {
  e.preventDefault()
  const newErrors: Record<string, string> = {}

  if (!email.includes('@')) {
    newErrors.email = 'Introduce un email válido'
  }
  if (password.length < 6) {
    newErrors.password = 'La contraseña debe tener al menos 6 caracteres'
  }
  if (password !== confirmPassword) {
    newErrors.confirmPassword = 'Las contraseñas no coinciden'
  }
  if (Object.keys(newErrors).length > 0) {
    setErrors(newErrors)
    return   // no se realiza la petición
  }

  // si pasa la validación, enviar al servidor
  await authApi.register(email, password)
}
```

La validación en el cliente **no sustituye** a la validación en el servidor: el backend valida de nuevo todos los datos recibidos. La validación en el cliente sirve únicamente para mejorar la experiencia de usuario al detectar errores antes de la petición de red.

---

# 34 — Usabilidad

La usabilidad de una aplicación web mide el grado en que un sistema permite a sus usuarios alcanzar sus objetivos de forma eficaz, eficiente y satisfactoria. Este documento acredita el cumplimiento del criterio de usabilidad del ciclo DAW, describiendo los principios aplicados durante el diseño e implementación de TFGdaw.

El marco de referencia utilizado son las **10 heurísticas de usabilidad de Jakob Nielsen**, el estándar más extendido en la evaluación de interfaces de usuario.

---

## 1. Visibilidad del estado del sistema

El sistema mantiene informado al usuario en todo momento sobre lo que está ocurriendo, mediante retroalimentación apropiada y en tiempo razonable.

**Aplicación en TFGdaw:**

- El componente `<Spinner>` se muestra durante todas las operaciones asíncronas: carga del feed, búsqueda de libros, envío de formularios. El usuario nunca se queda mirando una pantalla en blanco sin saber si la aplicación está procesando su acción.
- Los botones de envío de formulario se desactivan (`disabled`) mientras la petición está en curso, impidiendo envíos duplicados y señalando visualmente que se está procesando la acción.
- La barra de progreso del tracker de lectura se actualiza visualmente de forma inmediata al guardar un nuevo valor, proporcionando retroalimentación instantánea.
- El botón de like cambia de estado (vacío → relleno) en el momento del clic, antes incluso de recibir confirmación del servidor (actualización optimista).
- Las notificaciones no leídas se indican con un contador visible en el icono de la campana, manteniéndose al día sin que el usuario tenga que navegar a ninguna sección específica.

---

## 2. Coincidencia entre el sistema y el mundo real

El sistema usa el lenguaje y los conceptos familiares para el usuario, siguiendo las convenciones del mundo real.

**Aplicación en TFGdaw:**

- La metáfora de **estantería** es directamente reconocible para cualquier lector: se organiza en estanterías igual que en casa, con libros que se mueven entre ellas.
- Los estados de lectura usan términos naturales en español: *"Quiero leer"*, *"Leyendo"*, *"Leído"*, en lugar de identificadores técnicos como `WANT_TO_READ`.
- El sistema de reseñas con **estrellas del 1 al 5** es un patrón conocido por el usuario de plataformas como Amazon o Google Maps, eliminando la curva de aprendizaje.
- Los iconos utilizados son semánticamente universales: corazón para like, papelera para eliminar, lápiz para editar, candado para privacidad. Ningún icono requiere explicación.
- Los mensajes de error están escritos en español natural y describen el problema de forma comprensible, sin exponer mensajes técnicos del servidor al usuario.

---

## 3. Control y libertad del usuario

Los usuarios a menudo eligen funciones por error y necesitan una "salida de emergencia" claramente marcada.

**Aplicación en TFGdaw:**

- Todos los modales (formulario de nuevo club, lista de seguidores, panel de añadir libro a estantería) se pueden cerrar haciendo clic fuera del área del modal o pulsando el botón ✕ en la esquina superior, sin efectos secundarios.
- Las operaciones destructivas (eliminar estantería, eliminar publicación, eliminar comentario) muestran un diálogo de confirmación antes de ejecutarse, evitando borrados accidentales.
- El botón "Abandonar club" está oculto detrás de un menú contextual para evitar que el usuario lo active sin intención.
- La navegación mediante el botón "atrás" del navegador funciona correctamente en toda la aplicación gracias a React Router, que gestiona el historial de navegación de forma coherente.
- Si un formulario falla al enviarse, los datos introducidos por el usuario se conservan en los campos; el usuario no necesita volver a escribir todo desde el principio.

---

## 4. Consistencia y estándares

Los usuarios no deben tener que preguntarse si distintas palabras, situaciones o acciones significan lo mismo.

**Aplicación en TFGdaw:**

- El **sistema de diseño** centralizado en `tokens.css` garantiza que los colores, tipografías, tamaños y espaciados son idénticos en toda la aplicación. El color primario púrpura siempre identifica acciones principales; el rojo siempre identifica acciones destructivas; el verde siempre confirma éxito.
- Los botones de acción principal usan siempre la clase `btn btn-primary` y los botones de cancelar o salir usan `btn btn-ghost`, manteniendo un patrón coherente en todos los formularios.
- Las respuestas de la API siempre devuelven el mismo formato de error (`{ "error": "..." }`), lo que permite que el frontend los gestione de forma uniforme en todos los módulos.
- El patrón de tarjeta (`card` con `card-header`, `card-body`, `card-footer`) se reutiliza en toda la aplicación para presentar contenido agrupado, desde clubs hasta publicaciones o resultados de búsqueda.
- Los textos de los botones son consistentes: siempre "Guardar cambios" para actualizar datos de perfil, siempre "Cancelar" para descartar, siempre "Eliminar" para borrar.

---

## 5. Prevención de errores

Mejor que los buenos mensajes de error es un diseño cuidadoso que evite que los problemas ocurran.

**Aplicación en TFGdaw:**

- La búsqueda de libros no se ejecuta hasta que el usuario ha escrito al menos 2 caracteres, evitando resultados vacíos o demasiado amplios.
- Al añadir un libro a una estantería, si ese libro ya está en la estantería seleccionada, el sistema detecta el duplicado antes de enviar la petición (el servidor devuelve 409 y se muestra un mensaje informativo en lugar de un error genérico).
- El campo de contraseña muestra en tiempo real si se cumplen los requisitos mínimos (longitud ≥ 6 caracteres) antes de que el usuario intente enviar el formulario.
- En el formulario de registro, la confirmación de contraseña se valida instantáneamente al escribir, sin esperar al envío.
- Los hilos de chat cerrados muestran visualmente que están bloqueados (icono de candado, campo de texto desactivado) antes de que el usuario intente escribir, evitando el frustración de escribir un mensaje que no se puede enviar.
- Las imágenes subidas son validadas en el cliente por tipo MIME antes de enviar la petición al servidor.

---

## 6. Reconocimiento antes que recuerdo

Minimizar la carga de memoria del usuario haciendo visibles los objetos, acciones y opciones.

**Aplicación en TFGdaw:**

- El estado de cada libro en las estanterías (quiero leer / leyendo / leído) se muestra visualmente con un badge de color directamente sobre la portada, sin necesidad de abrir ningún menú.
- El rol del usuario en cada club (Administrador / Miembro) se muestra como badge en el listado de clubs y en el detalle, evitando que el usuario tenga que recordar en cuáles tiene permisos de gestión.
- El botón "Siguiendo" / "Seguir" / "Solicitud enviada" en el perfil de otro usuario refleja el estado actual de la relación de seguimiento sin que el usuario deba recordarlo.
- La estantería seleccionada en la barra lateral aparece visualmente resaltada, orientando al usuario sobre qué contenido está viendo en el panel principal.
- El libro del mes activo del club se muestra en la cabecera del club con portada y título, sin necesidad de navegar a ninguna sección adicional.

---

## 7. Flexibilidad y eficiencia de uso

Los aceleradores, invisibles para el usuario novel, pueden acelerar la interacción del usuario experto.

**Aplicación en TFGdaw:**

- Los formularios de búsqueda y envío de mensajes en el chat responden a la tecla Enter, permitiendo al usuario enviar sin usar el ratón.
- El formulario de nueva publicación muestra una previsualización de la imagen seleccionada inmediatamente, sin necesidad de confirmar primero.
- La sección de comentarios en `PostCard` se carga de forma diferida: los usuarios que no quieren ver comentarios nunca pagan el coste de esa petición adicional.
- Al renombrar una estantería, el campo de edición se activa directamente sobre el nombre, sin abrir ningún modal separado.
- El buscador de libros aplica los resultados mientras el usuario escribe (debounced), sin necesidad de pulsar ningún botón de búsqueda.

---

## 8. Diseño estético y minimalista

Los diálogos no deben contener información irrelevante o que rara vez sea necesaria.

**Aplicación en TFGdaw:**

- El listado de clubs muestra solo la información esencial para tomar la decisión de unirse: nombre, tipo, número de miembros y libro activo. Los detalles completos están disponibles en la página de detalle, no en el listado.
- Las tarjetas de resultado de búsqueda de libros muestran portada, título, autores y año. La descripción completa y todos los metadatos están en la página de detalle.
- Los mensajes de error son breves y directos, sin terminología técnica ni códigos de estado HTTP visibles para el usuario.
- La barra de navegación expone únicamente las secciones principales, sin submenús complejos. Las acciones secundarias (cambiar contraseña, configurar privacidad) están agrupadas en el perfil.
- El feed de la página de inicio no incluye anuncios, sugerencias algorítmicas ni contenido no solicitado; muestra exclusivamente las publicaciones de las personas que el usuario ha decidido seguir.

---

## 9. Ayuda a los usuarios a reconocer, diagnosticar y recuperarse de los errores

Los mensajes de error deben expresarse en lenguaje sencillo, indicar con precisión el problema y sugerir una solución.

**Aplicación en TFGdaw:**

| Situación de error | Mensaje mostrado | Acción sugerida |
|-------------------|------------------|-----------------|
| Email ya registrado | "Este email ya está en uso" | — (el usuario sabe qué hacer) |
| Contraseña incorrecta en login | "Credenciales incorrectas" | — (genérico por seguridad) |
| Libro ya en la estantería | "Este libro ya está en esta estantería" | El selector cambia automáticamente a otra estantería disponible |
| Hilo de chat cerrado | "Este hilo está cerrado" | El campo de texto se desactiva visualmente antes de intentar escribir |
| Error de red en petición | "No se pudo completar la operación. Inténtalo de nuevo." | Botón de reintentar visible |
| Imagen con formato no válido | "Formato no admitido. Usa JPG, PNG, GIF o WEBP." | El selector de ficheros filtra ya por extensión |

Los errores de validación en formularios aparecen **bajo el campo afectado**, no como un mensaje global en la parte superior del formulario, lo que permite al usuario identificar y corregir el problema de un vistazo.

---

## 10. Ayuda y documentación

Aunque es mejor si el sistema puede ser utilizado sin documentación, puede ser necesario proporcionar ayuda.

**Aplicación en TFGdaw:**

- Los placeholders de los campos de formulario indican el formato esperado directamente en el campo (por ejemplo, "mínimo 6 caracteres" en el campo de contraseña).
- Los estados vacíos no muestran una pantalla en blanco, sino un mensaje contextual con instrucciones sobre qué hacer: *"Tu feed está vacío. Sigue a otros lectores para ver sus publicaciones"*, *"No tienes estanterías. Crea una para empezar a organizar tus libros"*.
- El manual de usuario completo está disponible en `docs/31-manual-usuario.md` y cubre todas las funcionalidades paso a paso.
- Los tooltips en los iconos de acción menos obvios (como el botón de eliminar seguidor) muestran una descripción breve al pasar el cursor.

---

## Evaluación general de usabilidad

La interfaz de TFGdaw fue diseñada priorizando la claridad y la reducción de la carga cognitiva del usuario. Las decisiones de diseño más relevantes en este sentido son:

- **Una sola columna de atención en móvil**: en pantallas pequeñas, el contenido se presenta en una única columna sin elementos que compitan por la atención visual.
- **Acciones contextuales**: los botones de editar, eliminar o mover aparecen únicamente al interactuar con el elemento al que pertenecen (hover en escritorio, tap en móvil), evitando que la interfaz esté sobrecargada de controles permanentemente visibles.
- **Jerarquía visual clara**: los elementos de acción principal (botones primarios) tienen mayor peso visual que los secundarios (botones fantasma), guiando al usuario hacia las acciones más frecuentes.
- **Tiempo de respuesta percibido**: las actualizaciones optimistas (like, comentarios) hacen que la aplicación se perciba como más rápida de lo que técnicamente es, mejorando la satisfacción del usuario sin necesidad de infraestructura adicional.

---

# 35 — Figuras para el TFG.docx

Este documento contiene los pies de figura y los párrafos de referencia ya redactados para cada imagen que debe insertarse en el documento TFG.docx. El proceso es el siguiente:

1. Insertar la imagen en el punto indicado dentro del Word.
2. Añadir debajo el pie de figura correspondiente (en negrita o con el estilo de leyenda del Word).
3. Copiar el párrafo de referencia al texto principal donde se indica.

Los números de figura deben ajustarse si el orden de inserción varía respecto al indicado aquí.

---

## FIGURA 1 — Diagrama Entidad-Relación

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Modelo de datos" o "Base de datos".

**Imagen a insertar:** Captura del diagrama E/R creado con draw.io / ERDPlus / DBDiagram con las 16 entidades del sistema y sus relaciones.

**Pie de figura:**
> Figura 1: Diagrama Entidad-Relación del sistema TFGdaw. Se muestran las 16 entidades del modelo de datos y sus relaciones de cardinalidad.

**Párrafo de referencia para el texto:**
> La Figura 1 muestra el diagrama Entidad-Relación completo del sistema. En él se observa cómo la entidad central `User` se relaciona con el resto de entidades del sistema: estanterías (`Shelf`), libros (`Book`), clubs (`Club`), publicaciones (`Post`) y seguimientos (`Follow`). El diagrama completo, junto con el paso a tablas detallado, se documenta en el Anexo técnico correspondiente.

---

## FIGURA 2 — Mapa de navegación

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Mapa de navegación".

**Imagen a insertar:** Diagrama de flujo con la estructura de rutas del frontend: `/`, `/login`, `/register`, `/books`, `/books/:id`, `/clubs`, `/clubs/:id`, `/shelves`, `/profile`, `/users/:id`, `/admin`. Puede hacerse con draw.io, Figma o cualquier herramienta de diagramas. Indicar qué rutas son públicas y cuáles requieren autenticación (con un color diferente o un icono de candado).

**Pie de figura:**
> Figura 2: Mapa de navegación de la aplicación TFGdaw. Las rutas marcadas en gris requieren sesión activa (ROLE_USER); la ruta del panel de administración requiere además ROLE_ADMIN.

**Párrafo de referencia para el texto:**
> Como se puede observar en la Figura 2, la aplicación cuenta con diez rutas principales. Las rutas públicas (página de inicio, buscador de libros, listado de clubs y perfiles de usuario) son accesibles para cualquier visitante sin necesidad de cuenta. Las rutas privadas (estanterías, perfil propio y panel de administración) están protegidas por el componente `PrivateRoute`, que redirige automáticamente al formulario de inicio de sesión si no hay sesión activa.

---

## FIGURA 3 — Página de inicio (HomePage)

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Diseño de pantallas" o "Páginas implementadas".

**Imagen a insertar:** Captura de pantalla de la página de inicio (`/`) con sesión iniciada, mostrando el hero, el feed de publicaciones y la sección de características.

**Pie de figura:**
> Figura 3: Página de inicio de TFGdaw. La sección superior muestra el hero con las llamadas a la acción; el cuerpo central muestra el feed de publicaciones de los usuarios seguidos.

**Párrafo de referencia para el texto:**
> La Figura 3 muestra la página de inicio de la plataforma tal como la ve un usuario autenticado. En la parte superior se encuentra el bloque hero con el nombre de la aplicación y accesos directos a las secciones principales. A continuación, el feed personalizado muestra las publicaciones más recientes de los usuarios que sigue, ordenadas cronológicamente de más reciente a más antigua.

---

## FIGURA 4 — Buscador de libros (BooksPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de la página `/books` con resultados de búsqueda visibles (por ejemplo, búsqueda de "dune").

**Pie de figura:**
> Figura 4: Buscador de libros integrado con la Google Books API. Los resultados se muestran en tarjetas con portada, autores y año de publicación.

**Párrafo de referencia para el texto:**
> La Figura 4 ilustra la funcionalidad de búsqueda de libros. El buscador consulta en tiempo real la Google Books API a través del backend, mostrando los resultados como tarjetas con portada, título, autores y año. Los usuarios autenticados pueden añadir cualquier libro a sus estanterías directamente desde el resultado, sin necesidad de navegar a la página de detalle.

---

## FIGURA 5 — Detalle de un libro (BookDetailPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de `/books/:externalId` mostrando portada, metadatos, sinopsis, formulario de reseña y valoraciones de la comunidad.

**Pie de figura:**
> Figura 5: Página de detalle de un libro. Muestra los metadatos completos del volumen, las valoraciones de la comunidad con puntuación media y el formulario de reseña para usuarios autenticados.

**Párrafo de referencia para el texto:**
> Como se observa en la Figura 5, la página de detalle de un libro presenta toda la información disponible del volumen: portada ampliada, metadatos (editorial, fecha, páginas, idioma, ISBNs), sinopsis completa y la sección de valoraciones de la comunidad con la puntuación media, el número de reseñas y las opiniones individuales de otros usuarios.

---

## FIGURA 6 — Mis estanterías (ShelvesPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de `/shelves` mostrando el sidebar con las estanterías y el panel principal con los libros de la estantería seleccionada. Idealmente con el tracker de lectura visible en la parte superior.

**Pie de figura:**
> Figura 6: Página de gestión de estanterías. El panel lateral lista las estanterías del usuario; el panel principal muestra los libros de la estantería seleccionada con sus estados de lectura. En la parte superior se muestra el tracker de lectura activa.

**Párrafo de referencia para el texto:**
> La Figura 6 muestra la página de estanterías, que es la sección central de la biblioteca personal del usuario. El layout se divide en un sidebar de navegación entre estanterías y un panel principal donde se gestionan los libros. En la parte superior del panel se encuentra el tracker de lectura activa, que muestra el progreso de los libros que el usuario está leyendo actualmente con una barra visual y controles de actualización.

---

## FIGURA 7 — Clubs de lectura (ClubsPage y ClubDetailPage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura del listado de clubs (`/clubs`) o de la página de detalle de un club (`/clubs/:id`) con las pestañas Chats, Miembros y Solicitudes.

**Pie de figura:**
> Figura 7: Página de detalle de un club de lectura. Se muestran las tres pestañas de navegación interna (Chats, Miembros, Solicitudes) y el panel con el libro del mes activo.

**Párrafo de referencia para el texto:**
> La Figura 7 muestra la página de detalle de un club de lectura. La información del club (nombre, descripción, visibilidad, número de miembros y libro del mes) se presenta en la cabecera. El contenido se organiza en tres pestañas: Chats, con los hilos de debate disponibles para los miembros; Miembros, con la lista de participantes y sus roles; y Solicitudes, visible únicamente para los administradores del club en clubes privados.

---

## FIGURA 8 — Mi perfil (ProfilePage)

**Dónde insertar:** Sección "Diseño de pantallas".

**Imagen a insertar:** Captura de `/profile` mostrando el avatar, nombre, bio, contadores de seguidores/seguidos y las secciones de edición.

**Pie de figura:**
> Figura 8: Página de perfil propio. Muestra la información personal del usuario, los contadores de seguidores y seguidos, la sección de publicaciones propias y las opciones de configuración de privacidad.

**Párrafo de referencia para el texto:**
> La Figura 8 ilustra la página de perfil del usuario autenticado. Desde esta sección el usuario puede editar su información personal (nombre visible, biografía y avatar), consultar y gestionar su lista de seguidores y seguidos, configurar las opciones de privacidad de la cuenta y publicar nuevas entradas en su perfil con imagen adjunta.

---

## FIGURA 9 — Diseño responsive (versión móvil)

**Dónde insertar:** Sección "Diseño de Interfaces" → subsección "Diseño responsive".

**Imagen a insertar:** Dos capturas en paralelo: la misma página (por ejemplo, clubs o home) en escritorio (1280px) y en móvil (375px), mostrando la adaptación del layout.

**Pie de figura:**
> Figura 9: Comparativa del diseño responsive de TFGdaw. La imagen izquierda muestra la vista en escritorio (1280px); la imagen derecha muestra la adaptación a dispositivo móvil (375px) con menú hamburguesa y layout de columna única.

**Párrafo de referencia para el texto:**
> La Figura 9 ilustra la adaptación del diseño a diferentes tamaños de pantalla. En la vista de escritorio se aprovecha el ancho disponible con layouts de múltiples columnas y el sidebar de navegación siempre visible. En la vista móvil, la barra de navegación se contrae en un menú hamburguesa, los layouts pasan a una única columna y el tamaño de los elementos interactivos aumenta para facilitar la interacción táctil. Este comportamiento se implementa exclusivamente con media queries CSS, sin dependencia de ningún framework externo.

---

## FIGURA 10 — Panel de administración (AdminPage)

**Dónde insertar:** Sección "Diseño de pantallas" o "Manual de administración".

**Imagen a insertar:** Captura de `/admin` mostrando las estadísticas globales y las secciones de gestión de usuarios, clubs y publicaciones.

**Pie de figura:**
> Figura 10: Panel de administración de la plataforma. Muestra las estadísticas globales del sistema (usuarios, libros, clubs, publicaciones y reseñas) y las herramientas de gestión de usuarios y contenido.

**Párrafo de referencia para el texto:**
> La Figura 10 muestra el panel de administración, accesible exclusivamente a los usuarios con rol `ROLE_ADMIN`. La sección de estadísticas ofrece una visión global del estado de la plataforma con los totales de usuarios registrados, libros importados, clubs creados, publicaciones activas y reseñas escritas. Las secciones de gestión permiten al administrador activar o desactivar cuentas de usuario, promover usuarios a administrador, eliminar clubs y moderar publicaciones inapropiadas.

---

## FIGURA 11 — Arquitectura del sistema

**Dónde insertar:** Sección "Diseño e implementación" → subsección "Arquitectura".

**Imagen a insertar:** Diagrama de bloques con las tres capas: Navegador (React SPA) → Backend Symfony (API REST, Controladores, Entidades, BD) → Google Books API. Puede crearse con draw.io en 10 minutos.

**Pie de figura:**
> Figura 11: Arquitectura del sistema TFGdaw. Se representan las tres capas principales: el cliente React en el navegador, el backend Symfony con su API REST y base de datos, y la integración con la Google Books API como servicio externo.

**Párrafo de referencia para el texto:**
> La Figura 11 representa la arquitectura general del sistema. El frontend React se ejecuta en el navegador del usuario como una SPA, comunicándose de forma asíncrona con el backend Symfony mediante peticiones HTTP a la API REST. El backend gestiona la autenticación, la lógica de negocio y el acceso a la base de datos MySQL. Para la búsqueda de libros, el backend actúa como proxy hacia la Google Books API, importando y cacheando los resultados en la base de datos local para reducir la dependencia de servicios externos.

---

## Checklist de figuras para el TFG.docx

| # | Figura | ¿Insertada? |
|---|--------|-------------|
| 1 | Diagrama E/R | ☐ |
| 2 | Mapa de navegación | ☐ |
| 3 | Página de inicio | ☐ |
| 4 | Buscador de libros | ☐ |
| 5 | Detalle de libro | ☐ |
| 6 | Mis estanterías | ☐ |
| 7 | Club de lectura | ☐ |
| 8 | Mi perfil | ☐ |
| 9 | Diseño responsive | ☐ |
| 10 | Panel de administración | ☐ |
| 11 | Arquitectura del sistema | ☐ |