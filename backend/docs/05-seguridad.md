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

## 5. El proceso de login paso a paso

Cuando el frontend envía `POST /api/login`:

```
1. El firewall `main` intercepta la petición (ruta: api_login)
2. Symfony lee los campos `email` y `password` del JSON
3. Llama al provider para buscar el usuario por email en BD
4. Si no existe → llama a JsonLoginFailureHandler → 401
5. Verifica la contraseña con UserPasswordHasher
6. Si es incorrecta → llama a JsonLoginFailureHandler → 401
7. Si es correcta → crea la sesión PHP con los datos del usuario
8. Llama a JsonLoginSuccessHandler → 200 con datos del usuario
```

---

## 6. `JsonLoginSuccessHandler`

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

## 7. `JsonLoginFailureHandler`

**Archivo:** `src/Security/JsonLoginFailureHandler.php`

Se ejecuta cuando las credenciales son incorrectas (email no encontrado o contraseña incorrecta). Siempre devuelve el mismo mensaje genérico para no revelar si el email existe:

```json
{ "error": "Credenciales incorrectas" }
```

**Código HTTP:** `401 Unauthorized`.

---

## 8. Roles y autorización

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

## 9. Serialización segura de contraseñas en sesión

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

## 10. Verificación de email

La clase `src/Security/EmailVerifier.php` utiliza el bundle `SymfonyCastsVerifyEmailBundle` para generar y validar tokens de verificación de email firmados. Se envía un enlace al email del usuario al registrarse.

> **Nota actual:** En el flujo de registro via API (`/api/auth/register`), `isVerified` se establece directamente a `true` para simplificar el proceso durante el desarrollo. La verificación por email está disponible pero no es obligatoria en la versión actual.

---

## 11. CORS y cookies en desarrollo

En desarrollo, el frontend React corre en `localhost:5173` y el backend en `localhost:8000`. Para que las cookies de sesión funcionen en peticiones cross-origin, el frontend debe configurar:

```javascript
// En todas las peticiones fetch/axios:
credentials: 'include'  // o withCredentials: true
```

Y el servidor debe responder con las cabeceras `Access-Control-Allow-Origin` y `Access-Control-Allow-Credentials: true`. Esto se configura en el servidor web (Apache/Nginx) o mediante un bundle de CORS en Symfony.
