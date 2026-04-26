# 17 — Patrones de código y convenciones

Este documento describe los patrones de programación recurrentes en el backend: cómo están estructurados los controladores, cómo se diseñan las entidades, cómo funciona la serialización y qué decisiones de diseño se repiten a lo largo del código.

---

## 1. Inicialización en el constructor de entidades

Varias entidades usan el constructor para establecer valores por defecto y reducir el riesgo de crear objetos en estado inválido. Hay dos estilos:

### Estilo 1: constructor con parámetros obligatorios

Usado en entidades que no tienen sentido sin sus datos fundamentales. Garantiza que el objeto nunca exista sin los datos requeridos:

```php
// Post.php
public function __construct(User $user, string $imagePath, ?string $description = null)
{
    $this->user        = $user;
    $this->imagePath   = $imagePath;
    $this->description = $description;
    $this->createdAt   = new \DateTimeImmutable();   // timestamp automático
    $this->likes       = new ArrayCollection();
    $this->comments    = new ArrayCollection();
}
```

```php
// Follow.php
public function __construct(User $follower, User $following, string $status = self::STATUS_ACCEPTED)
{
    $this->follower  = $follower;
    $this->following = $following;
    $this->status    = $status;
    $this->createdAt = new \DateTimeImmutable();
}
```

```php
// BookReview.php
public function __construct(User $user, Book $book, int $rating, ?string $content = null)
{
    $this->user      = $user;
    $this->book      = $book;
    $this->rating    = $rating;
    $this->content   = $content;
    $this->createdAt = new \DateTimeImmutable();
}
```

```php
// Notification.php
public function __construct(
    User $recipient, User $actor, string $type,
    ?Post $post = null, ?Club $club = null, ?int $refId = null
) {
    // todos los campos en un solo punto
}
```

**Ventaja:** crear estas entidades en el controlador es una sola línea expresiva:
```php
$this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW));
$this->em->persist(new PostLike($post, $me));
```

### Estilo 2: constructor vacío + setters

Usado en entidades más complejas (como `Club`, `Shelf`, `ClubChat`) donde los datos se asignan progresivamente:

```php
$club = new Club();
$club->setName($name);
$club->setVisibility($visibility);
$club->setOwner($this->getUser());
$club->setCreatedAt(new \DateTimeImmutable());
$em->persist($club);
```

---

## 2. Helpers de serialización en controladores

Cada controlador tiene uno o varios métodos privados `serialize*()` que transforman entidades en arrays para la respuesta JSON. Esto centraliza el formato de salida y evita repetir la misma estructura en múltiples acciones:

```php
// PostApiController.php
private function serialize(Post $post, mixed $me): array
{
    return [
        'id'          => $post->getId(),
        'imagePath'   => $post->getImagePath(),
        'description' => $post->getDescription(),
        'createdAt'   => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'likes'       => $this->likeRepo->countByPost($post),
        'liked'       => $me ? $this->likeRepo->findByPostAndUser($post, $me) !== null : false,
        'commentCount'=> $this->commentRepo->count(['post' => $post]),
        'user'        => [
            'id'          => $post->getUser()->getId(),
            'displayName' => $post->getUser()->getDisplayName() ?? $post->getUser()->getEmail(),
            'avatar'      => $post->getUser()->getAvatar(),
        ],
    ];
}
```

Este patrón aparece en todos los controladores:
- `serialize(Post $p)` en `PostApiController`
- `serializeBook(Book $b)` en `ShelfApiController`
- `serializeChat(ClubChat $c)` en `ClubChatApiController`
- `serializeMessage(ClubChatMessage $m)` en `ClubChatApiController`
- `serializeReview(BookReview $r)` en `BookReviewApiController`
- `serializeCurrentBook(Club $c)` en `ClubApiController`
- `serializeOwnProfile(User $u)` en `UserApiController`

---

## 3. Patrón de autorización a nivel de recurso

En Symfony se puede controlar el acceso a nivel de ruta (en `security.yaml`) o a nivel de recurso dentro del controlador. Este proyecto usa el segundo enfoque para tener control fino:

```php
// Paso 1: verificar autenticación
$this->denyAccessUnlessGranted('ROLE_USER');

// Paso 2: buscar el recurso
$shelf = $shelfRepo->find($id);

// Paso 3: verificar propiedad del recurso
if (!$shelf || $shelf->getUser() !== $this->getUser()) {
    return $this->json(['error' => 'Estantería no encontrada'], 404);
}
```

Se devuelve **404** en lugar de **403** intencionalmente. Si se devolviera 403, se estaría confirmando que el recurso existe pero no pertenece al usuario. Con 404, no se revela ninguna información sobre recursos ajenos.

---

## 4. Patrón de validación de entrada

La validación se hace manualmente al inicio de cada método, sin el componente Validator de Symfony. El flujo es:

```
1. Leer y limpiar el input
2. Validar campos obligatorios
3. Validar formato/rango
4. Verificar unicidad en BD si aplica
5. Ejecutar la lógica de negocio
```

Ejemplo completo de `POST /api/auth/register`:

```php
// 1. Leer y limpiar
$email       = trim((string) ($data['email'] ?? ''));
$password    = (string) ($data['password'] ?? '');
$displayName = trim((string) ($data['displayName'] ?? ''));

// 2. Campos obligatorios
if ($email === '' || $password === '') {
    return $this->json(['error' => 'email y password son obligatorios'], 400);
}

// 3. Formato
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return $this->json(['error' => 'El email no es válido'], 400);
}
if (strlen($password) < 6) {
    return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 400);
}

// 4. Unicidad en BD
if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
    return $this->json(['error' => 'Ya existe una cuenta con ese email'], 409);
}

// 5. Lógica de negocio
$user = new User();
// ...
```

---

## 5. Patrón de helper privado `isAdmin()`

En `ClubApiController`, la verificación de si el usuario es admin del club se extrae a un método privado para no repetirla en cada acción:

```php
private function isAdmin(Club $club, ClubMemberRepository $repo): bool
{
    $membership = $repo->findOneBy([
        'club' => $club,
        'user' => $this->getUser(),
    ]);
    return $membership?->getRole() === 'admin';
}
```

El operador `?->` (nullsafe) hace que si `$membership` es `null`, el resultado sea `null` (falsy) en lugar de lanzar una excepción.

---

## 6. Patrón de helper `resolveChat()` para validación encadenada

Cuando una petición necesita resolver dos recursos relacionados, el patrón de helper devuelve una tupla `[recurso1, recurso2, error_o_null]`:

```php
private function resolveChat(int $clubId, int $chatId, ...): array
{
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

Uso en cada acción:
```php
[$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
if ($error) return $error;
// Aquí $club y $chat están garantizados como válidos
```

Esto elimina la duplicación de la doble validación (club existe + chat pertenece al club) en los 6 endpoints del `ClubChatApiController`.

---

## 7. Patrón de importación lazy de libros

La importación de libros desde Google Books sigue siempre el mismo patrón en los cuatro controladores que lo usan (`ShelfApiController`, `BookReviewApiController`, `ReadingProgressApiController`, `ClubApiController`):

```php
$book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

if (!$book) {
    $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
    if ($book === null) {
        return $this->json(['error' => 'No se encontró el libro en Google Books'], 404);
    }
}
```

El método `importBookFromGoogle()` está **duplicado** en los cuatro controladores. Esta es una deuda técnica conocida; idealmente estaría en un `BookImportService` inyectable. Sin embargo, para el alcance del TFG, la duplicación es aceptable.

---

## 8. Patrón de constantes en entidades

Las entidades con valores string fijos usan constantes de clase para evitar strings mágicos dispersos:

```php
// Follow.php
class Follow
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    // ...
}

// Uso en el controlador:
$status = $target->isPrivate() ? Follow::STATUS_PENDING : Follow::STATUS_ACCEPTED;
$follow = new Follow($me, $target, $status);
```

```php
// Notification.php
class Notification
{
    const TYPE_FOLLOW          = 'follow';
    const TYPE_FOLLOW_REQUEST  = 'follow_request';
    const TYPE_LIKE            = 'like';
    // ...
}

// Uso:
new Notification($target, $me, Notification::TYPE_FOLLOW);
```

Si en el futuro se quiere renombrar un tipo, basta con cambiar la constante.

---

## 9. Inyección de dependencias en controladores

Symfony soporta dos formas de inyectar dependencias en los controladores:

**Por constructor** (para dependencias usadas en múltiples métodos):
```php
class PostApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private PostLikeRepository $likeRepo,
        private PostCommentRepository $commentRepo,
    ) {}
```

**Por parámetro de acción** (para dependencias usadas solo en un método):
```php
#[Route('/api/shelves', methods: ['GET'])]
public function list(ShelfRepository $repo): JsonResponse
{
    // $repo solo se necesita aquí, no en otros métodos del controlador
}
```

Symfony inyecta automáticamente por tipo (autowiring), independientemente del lugar.

---

## 10. Método `getComputedPercent()` en entidades

La entidad `ReadingProgress` tiene un método de negocio que calcula el porcentaje de progreso de forma abstracta, ocultando la lógica de qué modo está activo:

```php
public function getComputedPercent(): int
{
    if ($this->mode === 'percent') {
        return $this->percent ?? 0;
    }
    // modo 'pages'
    $total = $this->totalPages ?? $this->book?->getPageCount();
    if (!$total || $total <= 0) return 0;
    return (int) round(($this->currentPage ?? 0) / $total * 100);
}
```

Cascada de fuentes para `$total`:
1. `totalPages` del progreso (override manual del usuario).
2. `pageCount` del libro (dato de Google Books).
3. Si ninguno está disponible, devuelve `0`.

El controlador simplemente llama `$p->getComputedPercent()` sin necesidad de conocer el modo.

---

## 11. Formato de fechas: `DateTimeImmutable` vs `DateTime`

Todo el proyecto usa exclusivamente `\DateTimeImmutable` en lugar de `\DateTime`. La diferencia es:

- `DateTime` es mutable: `$date->modify('+1 day')` cambia el objeto original.
- `DateTimeImmutable` es inmutable: `$date->modify('+1 day')` devuelve un objeto **nuevo**, el original no cambia.

Esto previene bugs sutiles donde una fecha se modifica accidentalmente al pasarla a una función.

---

## 12. Eliminación de archivos con `@unlink`

El operador `@` suprime errores en PHP. Se usa específicamente en `unlink()` al borrar imágenes:

```php
if (file_exists($imgPath)) {
    @unlink($imgPath);
}
```

La comprobación `file_exists()` reduce el riesgo, pero si el archivo desaparece entre la comprobación y el `unlink()` (condición de carrera), el `@` evita un error fatal. La entidad se borra de BD siempre, independientemente del resultado del `unlink()`.
