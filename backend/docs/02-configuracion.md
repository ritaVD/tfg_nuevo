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
