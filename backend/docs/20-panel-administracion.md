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
    "isAdmin": true
  },
  {
    "id": 2,
    "email": "usuario@ejemplo.com",
    "displayName": "MariaG",
    "avatar": "avatar_2.jpg",
    "roles": ["ROLE_USER"],
    "isVerified": false,
    "isAdmin": false
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
| Eliminar cualquier usuario | No | `DELETE /api/admin/users/{id}` |
| Eliminar su propio usuario | Sí (`DELETE /api/profile`) | Solo desde el perfil, no desde el panel |
| Ver todos los clubes | Solo los públicos | `GET /api/admin/clubs` (todos) |
| Eliminar cualquier club | Solo los suyos | `DELETE /api/admin/clubs/{id}` |
| Eliminar cualquier post | Solo los suyos | `DELETE /api/admin/posts/{id}` |
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

Dos operaciones tienen protección adicional para evitar estados irreparables:

1. **No puedes degradarte a ti mismo:** `PATCH /api/admin/users/{id}/role` devuelve `400` si `id` coincide con el usuario autenticado.
2. **No puedes eliminarte desde el panel:** `DELETE /api/admin/users/{id}` devuelve `400` si `id` coincide con el usuario autenticado.

Ambas verificaciones comparan `$user->getId() === $me->getId()` con igualdad estricta sobre enteros.
