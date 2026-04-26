# 28 — Controlador de Reseñas: análisis completo

`BookReviewApiController` gestiona las reseñas de libros. Cada usuario puede tener como máximo una reseña por libro (restricción `UNIQUE(user_id, book_id)`). El controlador implementa el patrón **upsert**: crear y actualizar se realizan con el mismo endpoint `POST`.

---

## 1. Estructura del controlador

```php
#[Route('/api/books/{externalId}/reviews', name: 'api_book_reviews_',
    requirements: ['externalId' => '[^/]+'])]
class BookReviewApiController extends AbstractController
```

**Prefijo de ruta:** `/api/books/{externalId}/reviews` — las reseñas son subrecursos del libro identificado por su `externalId` de Google Books.

**Requisito `[^/]+`:** El `externalId` de Google Books puede contener guiones y letras mayúsculas (ej. `zyTCAlFPjgYC`). El regex `[^/]+` acepta cualquier carácter excepto `/`, evitando conflictos de enrutamiento.

**Sin inyección por constructor:** Todos los repositorios y servicios se inyectan por parámetro de acción. Esta es la variante opuesta a `ReadingProgressApiController`, válida cuando los métodos tienen dependencias diferentes entre sí.

---

## 2. `GET /api/books/{externalId}/reviews` — listar reseñas

```php
public function list(
    string $externalId,
    BookRepository $bookRepo,
    BookReviewRepository $reviewRepo
): JsonResponse {
    $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

    if (!$book) {
        return $this->json(['stats' => ['average' => null, 'count' => 0], 'myRating' => null, 'reviews' => []]);
    }

    $me    = $this->getUser();
    $stats = $reviewRepo->getStats($book);

    $myRating = null;
    if ($me) {
        $myReview = $reviewRepo->findOneByUserAndBook($me, $book);
        if ($myReview) {
            $myRating = ['id' => $myReview->getId(), 'rating' => $myReview->getRating(), 'content' => $myReview->getContent()];
        }
    }

    $reviews = array_map(fn(BookReview $r) => $this->serializeReview($r), $reviewRepo->findByBook($book));

    return $this->json(['stats' => $stats, 'myRating' => $myRating, 'reviews' => $reviews]);
}
```

**Respuesta vacía en lugar de 404:** Si el libro no existe en la base de datos local (nunca nadie lo añadió a una estantería ni lo reseñó), se devuelve una respuesta válida con estadísticas vacías en lugar de un 404. El frontend puede renderizar "Sin reseñas aún" sin necesidad de manejo especial de errores.

**Endpoint público (sin autenticación obligatoria):** Cualquier visitante puede ver las reseñas de un libro. El usuario autenticado recibe adicionalmente su propia reseña (`myRating`) para saber si ya valoró el libro.

**Estructura de la respuesta:**

```json
{
  "stats": { "average": 4.2, "count": 15 },
  "myRating": { "id": 42, "rating": 5, "content": "Excelente libro" },
  "reviews": [
    {
      "id": 42,
      "rating": 5,
      "content": "Excelente libro",
      "createdAt": "2024-03-15T10:30:00+00:00",
      "user": { "id": 1, "displayName": "Rita", "avatar": "/uploads/..." }
    }
  ]
}
```

`myRating` es `null` si el usuario no está autenticado o no ha reseñado el libro. El frontend usa este campo para decidir si mostrar el formulario de nueva reseña o el de edición.

---

## 3. `POST /api/books/{externalId}/reviews` — upsert (crear o actualizar)

```php
public function upsert(
    string $externalId,
    Request $request,
    BookRepository $bookRepo,
    BookReviewRepository $reviewRepo,
    EntityManagerInterface $em,
    HttpClientInterface $httpClient
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $data    = json_decode($request->getContent(), true) ?? [];
    $rating  = (int) ($data['rating'] ?? 0);
    $content = trim((string) ($data['content'] ?? '')) ?: null;

    if ($rating < 1 || $rating > 5) {
        return $this->json(['error' => 'rating debe ser entre 1 y 5'], 400);
    }
    ...
}
```

**Patrón upsert:** En lugar de tener `POST` para crear y `PATCH` para actualizar, este controlador unifica las dos operaciones:

```php
$review = $reviewRepo->findOneByUserAndBook($me, $book);

if ($review) {
    // Actualizar reseña existente
    $review->setRating($rating);
    $review->setContent($content);
} else {
    // Crear nueva reseña
    $review = new BookReview($me, $book, $rating, $content);
    $em->persist($review);
}

$em->flush();
```

Esta decisión simplifica el cliente: el frontend siempre hace `POST`, sin necesidad de conocer si el usuario ya tiene reseña. El servidor resuelve internamente si es creación o actualización.

**`content` es opcional:** La expresión `trim(...) ?: null` convierte una cadena vacía en `null`. El usuario puede valorar con 1-5 estrellas sin escribir texto.

**Auto-importación del libro:** Si el libro no existe localmente, se importa automáticamente desde Google Books antes de crear la reseña. Este es el mismo mecanismo usado en `ShelfApiController` y `ReadingProgressApiController`.

**Respuesta incluye estadísticas actualizadas:**

```json
{
  "review": { "id": 42, "rating": 5, "content": "...", "createdAt": "...", "user": {...} },
  "stats": { "average": 4.3, "count": 16 }
}
```

El frontend puede actualizar la media mostrada en pantalla inmediatamente sin hacer una segunda petición.

---

## 4. `DELETE /api/books/{externalId}/reviews` — eliminar mi reseña

```php
public function delete(
    string $externalId,
    BookRepository $bookRepo,
    BookReviewRepository $reviewRepo,
    EntityManagerInterface $em
): JsonResponse {
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me   = $this->getUser();
    $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

    if (!$book) {
        return $this->json(['error' => 'Libro no encontrado'], 404);
    }

    $review = $reviewRepo->findOneByUserAndBook($me, $book);
    if (!$review) {
        return $this->json(['error' => 'No tienes una reseña para este libro'], 404);
    }

    $em->remove($review);
    $em->flush();

    $stats = $reviewRepo->getStats($book);

    return $this->json(['stats' => $stats]);
}
```

**Sin parámetro `{id}` en la ruta:** El usuario solo puede borrar su propia reseña (una por libro), por lo que no hace falta el ID de la reseña. El endpoint deduce la reseña a partir del usuario autenticado y el libro en la URL.

**Responde con estadísticas actualizadas** tras el borrado, igual que `upsert`. El frontend puede actualizar la media sin petición adicional.

---

## 5. Helper `serializeReview()`

```php
private function serializeReview(BookReview $r): array
{
    $u = $r->getUser();
    return [
        'id'        => $r->getId(),
        'rating'    => $r->getRating(),
        'content'   => $r->getContent(),
        'createdAt' => $r->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'user'      => [
            'id'          => $u->getId(),
            'displayName' => $u->getDisplayName() ?? $u->getEmail(),
            'avatar'      => $u->getAvatar(),
        ],
    ];
}
```

**Fallback `displayName ?? email`:** Si el usuario no tiene `displayName` configurado, se usa el email. Esto garantiza que siempre hay algo visible en la UI aunque el perfil esté incompleto.

---

## 6. Helper `importBookFromGoogle()`

```php
private function importBookFromGoogle(string $externalId, HttpClientInterface $httpClient, EntityManagerInterface $em): ?Book
```

Idéntico en lógica al de `ShelfApiController` y `ReadingProgressApiController`. Los tres controladores duplican este método porque no existe un servicio compartido para la importación. Para el TFG es aceptable, aunque en producción se extraería a un `BookImporter` service.

**Flujo:**
1. Llama a `https://www.googleapis.com/books/v1/volumes/{externalId}`.
2. Si la respuesta es 200, construye una entidad `Book` con los campos del `volumeInfo`.
3. Extrae `ISBN_10` e `ISBN_13` del array `industryIdentifiers`.
4. Prefiere `thumbnail` sobre `smallThumbnail` para la portada.
5. Persiste y devuelve el libro, o `null` si falla la petición.

---

## 7. Tabla de permisos

| Acción | Anónimo | Autenticado |
|--------|---------|-------------|
| Ver reseñas | ✓ | ✓ (+ ve su propia reseña) |
| Crear/editar reseña | ✗ | ✓ (solo la propia) |
| Eliminar reseña | ✗ | ✓ (solo la propia) |

---

## 8. Resumen de endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/books/{externalId}/reviews` | Listar reseñas + estadísticas |
| `POST` | `/api/books/{externalId}/reviews` | Crear o actualizar mi reseña |
| `DELETE` | `/api/books/{externalId}/reviews` | Eliminar mi reseña |
