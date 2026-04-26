# 30 — Controlador de Estanterías: análisis completo

`ShelfApiController` es el controlador más amplio del proyecto. Gestiona dos niveles de recursos: las estanterías como contenedores (`Shelf`) y los libros dentro de cada estantería (`ShelfBook`). Incluye operaciones de organización como mover libros entre estanterías y cambiar el estado de lectura.

---

## 1. Estructura del controlador

```php
#[Route('/api/shelves')]
class ShelfApiController extends AbstractController
```

**Sin nombre de ruta explícito:** A diferencia de otros controladores (que usan `name: 'api_...'`), este controlador no define un prefijo de nombre. Los nombres de ruta los genera Symfony automáticamente.

**Sin inyección por constructor:** Todas las dependencias se inyectan por parámetro de acción. Cada método declara solo lo que necesita, lo cual es especialmente útil aquí porque los endpoints de estanterías y los de libros requieren combinaciones distintas de repositorios.

---

## 2. Endpoints de estanterías (nivel contenedor)

### `GET /api/shelves` — listar estanterías

```php
#[Route('', methods: ['GET'])]
public function list(ShelfRepository $repo): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $shelves = $repo->findBy(['user' => $this->getUser()]);

    return $this->json(array_map(fn($s) => [
        'id' => $s->getId(),
        'name' => $s->getName()
    ], $shelves));
}
```

Respuesta mínima: solo `id` y `name`. Los libros no se incluyen aquí para mantener la respuesta ligera. Para obtener libros, usar `GET /api/shelves/{id}/books` o `GET /api/shelves/full`.

### `POST /api/shelves` — crear estantería

```php
$shelf->setOrderIndex(0);
$shelf->setCreatedAt(new \DateTimeImmutable());
$shelf->setUpdatedAt(new \DateTimeImmutable());
```

`orderIndex` se inicializa a `0`. Actualmente no hay un endpoint para reordenar estanterías, por lo que este campo está reservado para uso futuro.

### `PATCH /api/shelves/{id}` — renombrar

```php
$shelf = $shelfRepo->find($id);
if (!$shelf || $shelf->getUser() !== $this->getUser()) {
    return $this->json(['error' => 'Estantería no encontrada'], 404);
}
```

La verificación de propiedad combina existencia y autorización en un solo condicional. Un usuario que intenta renombrar la estantería de otro recibe 404, no 403. Esto oculta la existencia del recurso a usuarios no autorizados.

Actualiza `updatedAt` en cada renombrado para mantener una auditoría de cambios.

### `DELETE /api/shelves/{id}` — eliminar estantería

```php
$em->remove($shelf);
$em->flush();
return $this->json(null, 204);
```

Al eliminar una `Shelf`, el `orphanRemoval: true` de la relación con `ShelfBook` elimina en cascada todos los libros de esa estantería. No hace falta borrarlos manualmente.

---

## 3. Endpoints de libros en estantería (nivel elemento)

### `GET /api/shelves/{id}/books` — libros de una estantería

```php
$books = array_map(fn(ShelfBook $sb) => [
    'id'         => $sb->getId(),
    'status'     => $sb->getStatus(),
    'orderIndex' => $sb->getOrderIndex(),
    'addedAt'    => $sb->getAddedAt()?->format(\DateTimeInterface::ATOM),
    'book'       => $this->serializeBook($sb->getBook()),
], $shelf->getShelfBooks()->toArray());
```

El `id` expuesto es el de `ShelfBook` (la relación libro-estantería), no el del `Book`. Los endpoints de actualización, movimiento y eliminación usan este `bookId` (que es `ShelfBook.id`).

**Posible N+1:** `$this->serializeBook($sb->getBook())` accede a la entidad `Book` por cada `ShelfBook`. Si Doctrine no hizo eager loading de `Book` al cargar `ShelfBooks`, cada acceso lanza una consulta. Para listas largas convendría un JOIN con `addSelect`.

### `POST /api/shelves/{id}/books` — añadir libro

```php
$allowedStatuses = ['want_to_read', 'reading', 'read'];
if (!in_array($status, $allowedStatuses, true)) {
    return $this->json(['error' => 'status debe ser: ' . implode(', ', $allowedStatuses)], 400);
}
```

**Validación estricta del tercer argumento `true`:** `in_array($status, $allowedStatuses, true)` usa comparación estricta de tipos. Sin `true`, PHP haría comparación débil (type juggling), permitiendo que el número `0` pasase la validación contra un array de strings.

**Control de duplicados:**

```php
$existing = $shelfBookRepo->findOneBy(['shelf' => $shelf, 'book' => $book]);
if ($existing) {
    return $this->json(['error' => 'El libro ya está en esta estantería'], 409);
}
```

Código 409 Conflict: el recurso ya existe. La restricción `UNIQUE(shelf_id, book_id)` en base de datos también lo garantizaría a nivel SQL, pero comprobarlo antes evita una excepción de Doctrine.

**Cálculo del `orderIndex`:**

```php
$maxOrder = count($shelf->getShelfBooks());
$shelfBook->setOrderIndex($maxOrder);
```

El nuevo libro se coloca al final de la estantería. `count($shelf->getShelfBooks())` devuelve el número actual de libros, que coincide con el índice del siguiente elemento en una secuencia 0-based.

**Auto-importación desde Google Books:** Si el libro no existe localmente, se importa antes de añadirlo. Mismo mecanismo que `BookReviewApiController` y `ReadingProgressApiController`.

### `PATCH /api/shelves/{id}/books/{bookId}` — cambiar estado de lectura

```php
$shelfBook = $shelfBookRepo->find($bookId);
if (!$shelfBook || $shelfBook->getShelf() !== $shelf) {
    return $this->json(['error' => 'Entrada no encontrada'], 404);
}
```

Nótese que `$bookId` en la ruta es el ID de `ShelfBook`, no de `Book`. La verificación `$shelfBook->getShelf() !== $shelf` garantiza que la entrada pertenece a la estantería correcta (seguridad entre recursos).

El estado cambia sin modificar `orderIndex` ni `addedAt`, preservando la posición y fecha de incorporación del libro.

### `GET /api/shelves/full` — todas las estanterías con libros

```php
#[Route('/full', methods: ['GET'])]
public function listFull(ShelfRepository $shelfRepo): JsonResponse
```

**Posición de la ruta `/full`:** Este endpoint debe estar declarado **antes** que `/{id}` en el archivo para evitar que Symfony interprete `full` como un ID entero. En este controlador la declaración aparece tras `/{id}/books/{bookId}/move`, lo que en teoría podría causar conflictos. Sin embargo, el requisito `requirements: ['id' => '\d+']` en los endpoints con `{id}` limita la captura solo a números, por lo que `full` (no numérico) no colisiona.

**Caso de uso:** El frontend usa `GET /api/shelves/full` al cargar la página de estanterías para mostrar de una vez todos los libros organizados. Evita N peticiones (una por estantería).

**Potencial N+1:** Para cada estantería se accede a sus `ShelfBooks`, y para cada `ShelfBook` se serializa su `Book`. Con muchas estanterías y muchos libros, esto puede generar decenas de consultas. Para el TFG es aceptable.

### `POST /api/shelves/{id}/books/{bookId}/move` — mover a otra estantería

```php
if ($targetShelfId === $id) {
    return $this->json(['error' => 'La estantería destino es la misma que la origen'], 400);
}
```

**Validaciones encadenadas:**

1. Estantería origen existe y pertenece al usuario.
2. El `ShelfBook` existe y pertenece a la estantería origen.
3. `targetShelfId` es válido y no es el mismo que el origen.
4. Estantería destino existe y pertenece al mismo usuario.
5. El libro no está ya en la estantería destino (409 si hay duplicado).

**Operación atómica:** Mover un libro es un simple `setShelf($targetShelf)` en la entidad `ShelfBook`. Doctrine actualiza la FK `shelf_id` con un solo `UPDATE`. No hay DELETE + INSERT, lo que preserva el `id` del `ShelfBook` y el `addedAt` original.

```php
$newOrder = count($targetShelf->getShelfBooks());
$shelfBook->setShelf($targetShelf);
$shelfBook->setOrderIndex($newOrder);
$em->flush();
```

El libro se coloca al final de la estantería destino.

### `DELETE /api/shelves/{id}/books/{bookId}` — quitar libro

```php
$em->remove($shelfBook);
$em->flush();
return $this->json(null, 204);
```

Elimina el `ShelfBook`, no el `Book`. El libro permanece en la base de datos para que otras estanterías (de otros usuarios) que lo referencien no se vean afectadas.

---

## 4. Helper `serializeBook()`

```php
private function serializeBook(Book $book): array
{
    return [
        'id'            => $book->getId(),
        'externalId'    => $book->getExternalId(),
        'title'         => $book->getTitle(),
        'authors'       => $book->getAuthors() ?? [],
        'publisher'     => $book->getPublisher(),
        'publishedDate' => $book->getPublishedDate(),
        'coverUrl'      => $book->getCoverUrl(),
        'description'   => $book->getDescription(),
        'pageCount'     => $book->getPageCount(),
        'categories'    => $book->getCategories() ?? [],
        'language'      => $book->getLanguage(),
        'isbn10'        => $book->getIsbn10(),
        'isbn13'        => $book->getIsbn13(),
    ];
}
```

Es el serializador de `Book` más completo del proyecto: incluye todos los campos disponibles. Los de `BookReviewApiController` y `ReadingProgressApiController` son subconjuntos de este.

Los arrays `authors` y `categories` usan `?? []` para devolver siempre un array en JSON (nunca `null`), lo que simplifica el manejo en el frontend.

---

## 5. Tabla de rutas completa

| Método | Ruta | Acción |
|--------|------|--------|
| `GET` | `/api/shelves` | Listar estanterías (sin libros) |
| `POST` | `/api/shelves` | Crear estantería |
| `GET` | `/api/shelves/full` | Listar estanterías con todos sus libros |
| `PATCH` | `/api/shelves/{id}` | Renombrar estantería |
| `DELETE` | `/api/shelves/{id}` | Eliminar estantería (y sus libros) |
| `GET` | `/api/shelves/{id}/books` | Libros de una estantería |
| `POST` | `/api/shelves/{id}/books` | Añadir libro (auto-importa si no existe) |
| `PATCH` | `/api/shelves/{id}/books/{bookId}` | Cambiar estado de lectura |
| `POST` | `/api/shelves/{id}/books/{bookId}/move` | Mover libro a otra estantería |
| `DELETE` | `/api/shelves/{id}/books/{bookId}` | Quitar libro de la estantería |
