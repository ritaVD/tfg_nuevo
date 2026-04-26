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

**Clubs privados** solo muestran los miembros a otros miembros. Un visitante externo (autenticado o no) recibe 403.

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
    $membership = $clubMemberRepository->findOneBy([
        'club' => $club,
        'user' => $this->getUser(),
    ]);
    return $membership?->getRole() === 'admin';
}
```

Se llama en 6 endpoints: `update`, `delete`, `kickMember`, `joinRequests`, `approveRequest`, `rejectRequest`. Centraliza la verificación y garantiza que la lógica de "¿es admin?" es consistente en todo el controlador.

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
