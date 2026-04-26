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
