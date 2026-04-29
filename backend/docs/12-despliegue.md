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
