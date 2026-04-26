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
