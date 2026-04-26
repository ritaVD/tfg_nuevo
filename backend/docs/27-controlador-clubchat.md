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
