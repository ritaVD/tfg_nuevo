# 29 — Controlador de Progreso de Lectura: análisis completo

`ReadingProgressApiController` permite a los usuarios hacer seguimiento de su lectura activa. A diferencia de las estanterías (que organizan libros) o las reseñas (que valoran libros ya leídos), el progreso de lectura registra el avance en tiempo real: páginas leídas o porcentaje completado.

---

## 1. Estructura del controlador

```php
#[Route('/api/reading-progress', name: 'api_reading_progress_')]
class ReadingProgressApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReadingProgressRepository $repo,
        private BookRepository $bookRepo,
    ) {}
```

**Inyección por constructor:** Las tres dependencias se usan en la mayoría de métodos, por lo que se inyectan por constructor en lugar de por parámetro de acción. Esta elección reduce la firma de cada método público.

**Restricción `UNIQUE(user_id, book_id)`:** Un usuario solo puede tener un registro de progreso por libro. Si intenta crear uno duplicado, el servidor devuelve el registro existente (código 200) en lugar de un error.

---

## 2. `GET /api/reading-progress` — libros en seguimiento activo

```php
#[Route('', name: 'list', methods: ['GET'])]
public function list(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $items = $this->repo->findBy(['user' => $this->getUser()], ['updatedAt' => 'DESC']);

    return $this->json(array_map(fn($p) => $this->serialize($p), $items));
}
```

**Ordenación por `updatedAt DESC`:** Los libros en los que el usuario actualizó su progreso más recientemente aparecen primero. Esto es más útil que ordenar por `startedAt`, ya que los libros activamente leídos suben a la cima de la lista.

**Sin paginación:** Se asume que un usuario no tiene decenas de libros en lectura simultánea. Si la lista fuera muy larga, habría que agregar `limit`/`offset`.

---

## 3. `POST /api/reading-progress` — iniciar seguimiento

```php
#[Route('', name: 'add', methods: ['POST'])]
public function add(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $data       = json_decode($request->getContent(), true) ?? [];
    $externalId = trim((string) ($data['externalId'] ?? ''));
    $mode       = in_array($data['mode'] ?? '', ['pages', 'percent']) ? $data['mode'] : 'percent';
    ...
}
```

**Body de la petición:**

```json
{
  "externalId": "zyTCAlFPjgYC",
  "mode": "pages",
  "totalPages": 320
}
```

**Validación del `mode` con fallback:** Si el cliente envía un modo desconocido, se usa `'percent'` por defecto en lugar de devolver un error. Es más permisivo que la validación estricta de otros controladores.

**Idempotencia al crear:**

```php
$existing = $this->repo->findOneBy(['user' => $this->getUser(), 'book' => $book]);
if ($existing) {
    return $this->json($this->serialize($existing), 200);
}
```

Si el usuario ya está rastreando el libro, se devuelve el registro existente con código 200. El cliente no necesita comprobar si ya existe antes de llamar: el servidor lo gestiona.

**`totalPages` opcional:** Si se envía, se almacena en el registro de progreso. Si no se envía, el sistema usa `$book->getPageCount()` al serializar (fallback al dato de Google Books).

---

## 4. `PATCH /api/reading-progress/{id}` — actualizar progreso

```php
#[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
public function update(int $id, Request $request): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $progress = $this->repo->find($id);
    if (!$progress || $progress->getUser() !== $this->getUser()) {
        return $this->json(['error' => 'No encontrado'], 404);
    }
    ...
}
```

**Verificación de propiedad:** `$progress->getUser() !== $this->getUser()` garantiza que el usuario solo puede modificar su propio progreso. Si otro usuario conociera el ID de un registro ajeno, recibiría 404 (en lugar de 403), ocultando la existencia del recurso.

**Campos actualizables:**

```php
if (isset($data['mode']) && in_array($data['mode'], ['pages', 'percent'])) {
    $progress->setMode($data['mode']);
}
if (array_key_exists('currentPage', $data)) {
    $progress->setCurrentPage($data['currentPage'] !== null ? max(0, (int)$data['currentPage']) : null);
}
if (array_key_exists('totalPages', $data)) {
    $progress->setTotalPages($data['totalPages'] !== null && (int)$data['totalPages'] > 0 ? (int)$data['totalPages'] : null);
}
if (array_key_exists('percent', $data)) {
    $progress->setPercent($data['percent'] !== null ? max(0, min(100, (int)$data['percent'])) : null);
}
$progress->setUpdatedAt(new \DateTimeImmutable());
```

**`isset` vs `array_key_exists`:** Se usa `array_key_exists` (en lugar de `isset`) para los campos numéricos. La diferencia es crucial:

```
isset($data['currentPage'])           → false cuando el valor es null
array_key_exists('currentPage', $data) → true incluso cuando el valor es null
```

Si el cliente envía `{ "currentPage": null }` para borrar el progreso de páginas, `isset` lo ignoraría. Con `array_key_exists` el null se propaga correctamente.

**Sanitización de rangos:**
- `currentPage`: `max(0, ...)` — nunca negativo.
- `percent`: `max(0, min(100, ...))` — clampeado entre 0 y 100.
- `totalPages`: solo se actualiza si es un entero positivo (`> 0`).

**`updatedAt` siempre se actualiza** al hacer PATCH, independientemente de qué campos cambian.

---

## 5. Dos modos de seguimiento

El campo `mode` determina cómo el usuario introduce su progreso:

| `mode` | Campos relevantes | Caso de uso |
|--------|-------------------|-------------|
| `pages` | `currentPage`, `totalPages` | El usuario prefiere introducir "estoy en la página 150 de 320" |
| `percent` | `percent` | El usuario prefiere "llevo el 47%" (ebook, sin páginas físicas) |

**`getComputedPercent()`:** Método de la entidad que calcula el porcentaje a partir del modo activo:

```php
// En la entidad ReadingProgress
public function getComputedPercent(): ?int
{
    if ($this->mode === 'percent') {
        return $this->percent;
    }
    if ($this->currentPage !== null && $this->totalPages !== null && $this->totalPages > 0) {
        return (int) round(($this->currentPage / $this->totalPages) * 100);
    }
    return null;
}
```

Esto expone siempre `computed` en la serialización, independientemente del modo. El frontend puede usar `computed` para barras de progreso sin preocuparse por el modo.

---

## 6. `DELETE /api/reading-progress/{id}` — dejar de rastrear

```php
#[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $progress = $this->repo->find($id);
    if (!$progress || $progress->getUser() !== $this->getUser()) {
        return $this->json(['error' => 'No encontrado'], 404);
    }

    $this->em->remove($progress);
    $this->em->flush();

    return $this->json(null, 204);
}
```

**204 No Content:** La respuesta de eliminación exitosa es un cuerpo vacío con código 204. Es la convención REST para operaciones de borrado.

---

## 7. Helper `serialize()`

```php
private function serialize(ReadingProgress $p): array
{
    $book = $p->getBook();
    return [
        'id'          => $p->getId(),
        'mode'        => $p->getMode(),
        'currentPage' => $p->getCurrentPage(),
        'totalPages'  => $p->getTotalPages() ?? $book->getPageCount(),
        'percent'     => $p->getPercent(),
        'computed'    => $p->getComputedPercent(),
        'startedAt'   => $p->getStartedAt()?->format(\DateTimeInterface::ATOM),
        'updatedAt'   => $p->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        'book'        => [
            'id'         => $book->getId(),
            'externalId' => $book->getExternalId(),
            'title'      => $book->getTitle(),
            'authors'    => $book->getAuthors() ?? [],
            'coverUrl'   => $book->getCoverUrl(),
            'pageCount'  => $book->getPageCount(),
        ],
    ];
}
```

**`totalPages ?? $book->getPageCount()`:** Si el usuario no especificó el total de páginas al crear el registro, se usa el número de páginas del libro según Google Books. Esto hace que `computed` funcione aunque el usuario no haya configurado `totalPages` explícitamente.

**Ejemplo de respuesta serializada:**

```json
{
  "id": 7,
  "mode": "pages",
  "currentPage": 150,
  "totalPages": 320,
  "percent": null,
  "computed": 47,
  "startedAt": "2024-03-10T09:00:00+00:00",
  "updatedAt": "2024-03-15T20:30:00+00:00",
  "book": {
    "id": 3,
    "externalId": "zyTCAlFPjgYC",
    "title": "El Quijote",
    "authors": ["Miguel de Cervantes"],
    "coverUrl": "https://books.google.com/...",
    "pageCount": 320
  }
}
```

---

## 8. Resumen de endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/reading-progress` | Libros en seguimiento activo |
| `POST` | `/api/reading-progress` | Iniciar seguimiento (idempotente) |
| `PATCH` | `/api/reading-progress/{id}` | Actualizar progreso |
| `DELETE` | `/api/reading-progress/{id}` | Dejar de rastrear |
