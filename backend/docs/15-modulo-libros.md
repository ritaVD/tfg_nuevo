# 15 — Módulo de Libros

El módulo de libros engloba todo lo relacionado con la gestión de lecturas personales: búsqueda de libros, organización en estanterías, seguimiento del progreso de lectura y reseñas.

---

## 1. Visión general

```
Google Books API
       │
       │  búsqueda / detalle
       ▼
BookExternalApiController ──► [resultados temporales, no se guardan]
       │
       │  al añadir a estantería / reseña / progreso
       ▼
  Book (entidad) ──────────────────────────────────────────────┐
       │                                                        │
       ├── ShelfBook (libro en estantería con estado)           │
       │       └── Shelf (estantería del usuario)              │
       │                                                        │
       ├── ReadingProgress (progreso por páginas o %)          │
       │                                                        │
       └── BookReview (reseña y puntuación 1-5)                │
                                                               │
                                              Club.currentBook ┘
```

Un libro (`Book`) se crea en la BD **la primera vez** que alguien lo referencia (estantería, reseña, progreso o libro del mes de un club). A partir de ese momento, todos los usuarios comparten la misma entidad `Book`.

---

## 2. Búsqueda de libros

### `GET /api/books/search`

Proxy hacia Google Books. Los resultados **no se guardan** en la BD; son temporales y solo se muestran al usuario para que elija qué libro quiere usar.

Ver [09-google-books.md](09-google-books.md) para la documentación completa de parámetros y el algoritmo de re-ranking.

### `GET /api/books/{externalId}`

Detalle completo de un libro desde Google Books. Tampoco se guarda en BD.

---

## 3. Estanterías

### 3.1 Qué es una estantería

Una estantería (`Shelf`) es una colección nombrada de libros que pertenece a un usuario. Cada usuario puede tener tantas estanterías como quiera con nombres personalizados: "Leídos", "Por leer", "Favoritos", etc.

### 3.2 Estados de un libro en la estantería

Cuando se añade un libro, se asigna un estado de lectura (`ShelfBook.status`):

| Estado | Descripción |
|--------|-------------|
| `want_to_read` | Quiero leerlo (lista de deseos) |
| `reading` | Lo estoy leyendo ahora |
| `read` | Ya lo he leído |

El estado se puede cambiar con `PATCH /api/shelves/{id}/books/{bookId}`.

### 3.3 Flujo completo de una estantería

```
1. Crear estantería
   POST /api/shelves
   { "name": "Ciencia Ficción" }
   → { "id": 5, "name": "Ciencia Ficción" }

2. Añadir libro (importa de Google si no existe en BD)
   POST /api/shelves/5/books
   { "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }
   → { "id": 23, "status": "want_to_read", "book": {...} }

3. Cambiar estado cuando lo empieces a leer
   PATCH /api/shelves/5/books/23
   { "status": "reading" }

4. Marcar como leído
   PATCH /api/shelves/5/books/23
   { "status": "read" }

5. Mover a otra estantería
   POST /api/shelves/5/books/23/move
   { "targetShelfId": 8 }

6. Quitar de la estantería
   DELETE /api/shelves/5/books/23
```

### 3.4 Listado completo

`GET /api/shelves/full` devuelve todas las estanterías con sus libros en una sola petición, evitando hacer N peticiones adicionales:

```json
[
  {
    "id": 5,
    "name": "Ciencia Ficción",
    "books": [
      {
        "id": 23,
        "status": "read",
        "orderIndex": 0,
        "addedAt": "2026-03-15T12:00:00+00:00",
        "book": {
          "id": 7,
          "externalId": "zyTCAlFPjgYC",
          "title": "Dune",
          "authors": ["Frank Herbert"],
          "coverUrl": "https://...",
          "pageCount": 896,
          ...
        }
      }
    ]
  }
]
```

### 3.5 Importación automática al añadir un libro

Cuando se hace `POST /api/shelves/{id}/books`, el backend comprueba si el libro ya existe en la BD por su `externalId`. Si no existe, lo importa de Google Books en ese mismo momento. Este proceso es transparente para el usuario.

```
Usuario selecciona libro en buscador
        │
        │  externalId: "zyTCAlFPjgYC"
        ▼
¿Existe en BD?
    ├── Sí → usa el existente
    └── No → GET https://books.googleapis.com/volumes/zyTCAlFPjgYC
              → Crea Book en BD
              → Añade a estantería
```

---

## 4. Progreso de lectura

### 4.1 Dos modos de seguimiento

**Modo páginas (`pages`):**
```json
{ "externalId": "zyTC...", "mode": "pages", "totalPages": 896 }
```
El progreso se actualiza con `currentPage`:
```json
{ "currentPage": 250 }
→ computed: 27.9%
```

**Modo porcentaje (`percent`):**
```json
{ "externalId": "zyTC...", "mode": "percent" }
```
El progreso se actualiza directamente con el porcentaje:
```json
{ "percent": 30 }
→ computed: 30%
```

El campo `computed` en la respuesta siempre calcula el porcentaje independientemente del modo:
- En modo `pages`: `computed = (currentPage / totalPages) * 100`
- En modo `percent`: `computed = percent`

### 4.2 Respuesta del progreso

```json
{
  "id": 12,
  "mode": "pages",
  "currentPage": 250,
  "totalPages": 896,
  "percent": null,
  "computed": 27.9,
  "startedAt": "2026-04-01T00:00:00+00:00",
  "updatedAt": "2026-04-19T20:00:00+00:00",
  "book": {
    "id": 7,
    "externalId": "zyTCAlFPjgYC",
    "title": "Dune",
    "authors": ["Frank Herbert"],
    "coverUrl": "https://...",
    "pageCount": 896
  }
}
```

### 4.3 Restricción única

Solo puede haber **un registro de progreso por usuario y libro**. Si el usuario intenta añadir un libro que ya está siendo seguido, el endpoint devuelve el registro existente (`200 OK`) en lugar de crear uno nuevo.

### 4.4 Cambiar de modo

Se puede cambiar entre `pages` y `percent` en cualquier momento con `PATCH`:
```json
{ "mode": "percent", "percent": 35 }
```

---

## 5. Reseñas de libros

### 5.1 Una reseña por usuario y libro

Cada usuario puede escribir una sola reseña por libro (restricción única en BD). Si escribe una segunda reseña, la primera se actualiza (patrón **upsert**).

### 5.2 Crear o actualizar una reseña

```
POST /api/books/{externalId}/reviews
{ "rating": 4, "content": "Una obra maestra de la ciencia ficción." }
```

- `rating`: obligatorio, entre 1 y 5.
- `content`: opcional (se puede puntuar sin escribir texto).
- Si el libro no existe en BD, se importa de Google Books automáticamente.

### 5.3 Estadísticas de un libro

`GET /api/books/{externalId}/reviews` devuelve tres bloques de información:

```json
{
  "stats": {
    "average": 4.3,
    "count": 27,
    "distribution": {
      "1": 1,
      "2": 2,
      "3": 3,
      "4": 8,
      "5": 13
    }
  },
  "myRating": {
    "id": 5,
    "rating": 4,
    "content": "Una obra maestra..."
  },
  "reviews": [
    {
      "id": 5,
      "rating": 4,
      "content": "Una obra maestra...",
      "createdAt": "2026-04-10T15:00:00+00:00",
      "user": {
        "id": 2,
        "displayName": "MariaG",
        "avatar": "avatar_2.jpg"
      }
    }
  ]
}
```

- `stats`: estadísticas globales del libro (independientes del usuario).
- `myRating`: la reseña del usuario actual (o `null` si no ha reseñado).
- `reviews`: todas las reseñas con texto, de más reciente a más antigua.

Si el libro no tiene ninguna reseña aún en la BD, `stats.average` es `null` y `stats.count` es `0`.

### 5.4 Eliminar una reseña

```
DELETE /api/books/{externalId}/reviews
```

Elimina la reseña del usuario autenticado para ese libro. Devuelve las estadísticas actualizadas.

---

## 6. Relación entre los tres submódulos

| Entidad | ¿Comparten el mismo `Book`? | Restricción por usuario |
|---------|-----------------------------|------------------------|
| `ShelfBook` | Sí | Un libro puede estar en varias estanterías del mismo usuario |
| `ReadingProgress` | Sí | Un solo registro por usuario y libro |
| `BookReview` | Sí | Una sola reseña por usuario y libro |

Ejemplo: tres usuarios que añaden "Dune" a sus estanterías comparten la misma entidad `Book` (id=7), pero cada uno tiene su propio `ShelfBook`, `ReadingProgress` y `BookReview`.
