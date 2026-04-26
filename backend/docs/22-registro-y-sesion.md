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
