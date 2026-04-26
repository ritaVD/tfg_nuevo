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
