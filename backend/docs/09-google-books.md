# 09 — Integración con Google Books API

El backend actúa como **intermediario** entre el frontend React y la Google Books API. Esto tiene dos ventajas: la API key nunca se expone al navegador, y los libros se pueden cachear localmente en la base de datos.

---

## 1. Arquitectura de la integración

```
React                 Backend Symfony              Google Books API
  │                        │                              │
  │  GET /api/books/search  │                              │
  │  ?q=potter             │                              │
  │───────────────────────►│                              │
  │                        │  GET /books/v1/volumes        │
  │                        │  ?q=potter&key=API_KEY        │
  │                        │─────────────────────────────►│
  │                        │  { items: [...] }             │
  │                        │◄─────────────────────────────│
  │                        │ Filtra, ordena y normaliza    │
  │  200 { results: [...] }│                              │
  │◄───────────────────────│                              │
```

---

## 2. Controladores involucrados

| Controlador | Función |
|-------------|---------|
| `BookExternalApiController` | Búsqueda y detalle de libros directamente desde Google |
| `ShelfApiController` | Importa libros de Google al añadirlos a una estantería |
| `BookReviewApiController` | Importa libros de Google al crear una reseña |
| `ReadingProgressApiController` | Importa libros de Google al registrar el progreso |

---

## 3. `GET /api/books/search` — Búsqueda avanzada

### Parámetros de consulta

| Parámetro | Tipo | Descripción | Default |
|-----------|------|-------------|---------|
| `q` | string | Texto libre (busca en todo) | — |
| `title` | string | Filtro por título (`intitle:`) | — |
| `author` | string | Filtro por autor (`inauthor:`) | — |
| `isbn` | string | Filtro por ISBN (`isbn:`) | — |
| `subject` | string | Filtro por categoría (`subject:`) | — |
| `publisher` | string | Filtro por editorial (`inpublisher:`) | — |
| `page` | int | Número de página (paginación) | `1` |
| `limit` | int | Resultados por página (máx. 40) | `20` |
| `orderBy` | string | `relevance` o `newest` | `relevance` |
| `lang` | string | Filtro por idioma (`es`, `en`, etc.) | — |
| `printType` | string | `books`, `magazines` o `all` | `books` |
| `filter` | string | `free-ebooks`, `paid-ebooks`, `ebooks`, `partial`, `full` | — |

Al menos uno de los filtros de texto (`q`, `title`, `author`, `isbn`, `subject`, `publisher`) es obligatorio.

### Construcción de la consulta a Google

Los filtros se combinan en un único parámetro `q` usando los operadores de búsqueda de Google Books:

```
q=potter intitle:harry inauthor:rowling subject:fantasy
```

### Algoritmo de re-ranking

Google Books devuelve hasta 40 resultados, pero la calidad varía mucho. El backend aplica un algoritmo propio antes de devolver los resultados:

```
1. Filtrar: descartar libros sin imagen de portada
   (libros sin portada son generalmente entradas incompletas o no publicados)

2. Separar en dos grupos:
   - Con puntuaciones (ratingsCount > 0)
   - Sin puntuaciones

3. Ordenar los libros CON puntuaciones por:
   score = ratingsCount × averageRating (descendente)
   → Un libro con 1000 valoraciones y 4.5★ aparece antes que uno con 10 y 5★

4. Ordenar los libros SIN puntuaciones por número de páginas (descendente)
   → Un libro de 400 páginas es más probable que sea real que uno de 5

5. Combinar: primero los valorados, luego los no valorados

6. Recortar al límite solicitado (máx. 40)
```

Este algoritmo mejora la experiencia porque los resultados más relevantes y populares aparecen primero.

---

## 4. `GET /api/books/{externalId}` — Detalle de un libro

Devuelve los metadatos completos de un volumen de Google Books directamente, sin almacenar en BD.

El `externalId` es el identificador de volumen de Google Books (ej: `zyTCAlFPjgYC`, `OL7353617M`).

---

## 5. Importación automática de libros (lazy import)

Cuando el usuario quiere **añadir un libro a una estantería**, **crear una reseña** o **registrar progreso**, el backend necesita tener el libro en su base de datos local. El flujo es:

```php
// 1. Buscar en BD local
$book = $bookRepo->findOneBy([
    'externalId' => $externalId,
    'externalSource' => 'google_books'
]);

// 2. Si no existe, importarlo
if (!$book) {
    $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
    if ($book === null) {
        return $this->json(['error' => 'No se encontró el libro'], 404);
    }
}
```

### Datos que se importan

```php
$book = new Book();
$book->setExternalSource('google_books');
$book->setExternalId($item['id']);
$book->setTitle($vi['title'] ?? 'Sin título');
$book->setAuthors($vi['authors'] ?? []);
$book->setPublisher($vi['publisher'] ?? null);
$book->setPublishedDate($vi['publishedDate'] ?? null);
$book->setLanguage($vi['language'] ?? null);
$book->setDescription($vi['description'] ?? null);
$book->setPageCount((int) $vi['pageCount']);
$book->setCategories($vi['categories'] ?? []);
$book->setCoverUrl($links['thumbnail'] ?? $links['smallThumbnail'] ?? null);
$book->setIsbn10($isbn10);
$book->setIsbn13($isbn13);
```

Una vez importado, el libro queda en la BD y las siguientes referencias no requieren llamada a la API externa.

---

## 6. Normalización de ISBNs

Los ISBNs se extraen del campo `industryIdentifiers` de la respuesta de Google Books:

```json
"industryIdentifiers": [
  { "type": "ISBN_10", "identifier": "8408172174" },
  { "type": "ISBN_13", "identifier": "9788408172179" }
]
```

El backend itera el array y extrae `isbn10` e `isbn13` por separado. Si Google devuelve solo uno de los dos, el otro queda en `null`.

En las búsquedas por ISBN, se limpia la entrada del usuario eliminando todo lo que no sea dígitos o la letra `X`:
```php
$isbn = preg_replace('/[^0-9Xx]/', '', $request->query->get('isbn', ''));
```

---

## 7. Manejo de errores de la API externa

El controlador gestiona tres tipos de fallo:

| Situación | Respuesta al cliente |
|-----------|---------------------|
| Google Books devuelve 4xx/5xx | `502 Bad Gateway` con detalles |
| Timeout o error de red | `502 Bad Gateway` con mensaje de error |
| `externalId` no existe (404 de Google) | `404 Not Found` |

En los contextos de importación (añadir libro, crear reseña, etc.), si la importación falla, la operación completa se aborta y se informa al usuario.

---

## 8. Configuración de la API key

La API key se configura en el archivo `.env`:

```
GOOGLE_BOOKS_API_KEY=AIzaSy...
```

Y se lee en el controlador con:
```php
$apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;
```

Si no hay API key configurada, las peticiones se hacen sin autenticación. Google Books permite un número limitado de peticiones anónimas, pero con la API key el límite sube considerablemente. En producción, la API key es **obligatoria**.

---

## 9. Estructura de respuesta normalizada

Tanto la búsqueda como el detalle devuelven los libros con la misma estructura:

```json
{
  "externalId":    "zyTCAlFPjgYC",
  "title":         "Harry Potter y la piedra filosofal",
  "subtitle":      null,
  "authors":       ["J.K. Rowling"],
  "publisher":     "Salamandra",
  "publishedDate": "1999",
  "categories":    ["Juvenile Fiction"],
  "language":      "es",
  "description":   "Harry Potter es un joven...",
  "pageCount":     309,
  "averageRating": 4.5,
  "ratingsCount":  15820,
  "thumbnail":     "https://books.google.com/books/content?id=...",
  "previewLink":   "https://books.google.es/books?id=...",
  "infoLink":      "https://play.google.com/store/books/...",
  "isbn10":        "8478884459",
  "isbn13":        "9788478884452"
}
```

La búsqueda además incluye paginación:
```json
{
  "page": 1,
  "limit": 20,
  "totalItems": 1243,
  "results": [ {...}, {...}, ... ]
}
```
