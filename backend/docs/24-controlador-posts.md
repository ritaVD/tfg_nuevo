# 24 — Controlador de Posts: análisis completo

`PostApiController` gestiona todo el contenido publicado en la plataforma: publicaciones con imagen, el feed personalizado, el sistema de likes y los comentarios. Es el controlador central del módulo social.

---

## 1. Estructura del controlador

```php
#[Route('/api', name: 'api_posts_')]
class PostApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private PostLikeRepository $likeRepo,
        private PostCommentRepository $commentRepo,
    ) {}
```

Las cuatro dependencias cubren todas las operaciones:
- `$em` para persistir y eliminar entidades.
- `$postRepo` para buscar posts (feed, por usuario, por ID).
- `$likeRepo` para verificar si un like existe y contar likes.
- `$commentRepo` para listar y contar comentarios.

---

## 2. `GET /api/posts` — feed personalizado

```php
#[Route('/posts', name: 'feed', methods: ['GET'])]
public function feed(): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me    = $this->getUser();
    $posts = $this->postRepo->findFeed($me, 40);

    return $this->json(array_map(fn(Post $p) => $this->serialize($p, $me), $posts));
}
```

**Qué devuelve:** hasta 40 posts ordenados de más reciente a más antiguo, incluyendo los propios y los de usuarios a los que sigue con `status = 'accepted'`.

La consulta está en `PostRepository::findFeed()` (ver [23-repositorios-detalle.md](23-repositorios-detalle.md)):

```sql
SELECT p.*
FROM post p
LEFT JOIN follow f ON f.follower_id = :me
                  AND f.following_id = p.user_id
                  AND f.status = 'accepted'
WHERE p.user_id = :me OR f.id IS NOT NULL
ORDER BY p.created_at DESC
LIMIT 40
```

El LEFT JOIN garantiza que los posts propios aparezcan incluso si el usuario no sigue a nadie. Si el resultado del join es `NULL` (`f.id IS NOT NULL` falla), solo se incluye el post si el autor es el propio usuario.

---

## 3. `GET /api/users/{id}/posts` — posts de un usuario concreto

```php
#[Route('/users/{id}/posts', name: 'by_user', requirements: ['id' => '\d+'], methods: ['GET'])]
public function byUser(int $id, UserRepository $userRepo): JsonResponse
{
    $user = $userRepo->find($id);
    if (!$user) {
        return $this->json(['error' => 'Usuario no encontrado'], 404);
    }

    $me    = $this->getUser();
    $posts = $this->postRepo->findByUser($user);

    return $this->json(array_map(fn(Post $p) => $this->serialize($p, $me), $posts));
}
```

Este endpoint es **público** — no llama a `denyAccessUnlessGranted`. Cualquier visitante puede ver los posts de cualquier usuario. La privacidad (ocultar posts de cuentas privadas a no seguidores) la gestiona el **frontend** comprobando `followStatus` e `isPrivate` del perfil.

`$me = $this->getUser()` puede ser `null` si no hay sesión activa. El helper `serialize()` lo acepta y en ese caso `liked` siempre es `false`.

---

## 4. `POST /api/posts` — crear publicación

```php
#[Route('/posts', name: 'create', methods: ['POST'])]
public function create(Request $request): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $file = $request->files->get('image');
    if (!$file) {
        return $this->json(['error' => 'Se requiere una imagen'], 400);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower($file->guessExtension() ?? '');
    if (!in_array($ext, $allowed, true)) {
        return $this->json(['error' => 'Formato de imagen no permitido'], 400);
    }

    $filename  = uniqid('post_', true) . '.' . $ext;
    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';
    $file->move($uploadDir, $filename);

    $description = trim((string) ($request->request->get('description', ''))) ?: null;

    $post = new Post($me, $filename, $description);
    $this->em->persist($post);
    $this->em->flush();

    return $this->json($this->serialize($post, $me), 201);
}
```

**Paso a paso:**

**Paso 1 — Obtener el archivo:**
`$request->files->get('image')` extrae el archivo del campo `image` del formulario `multipart/form-data`. Si no se envió ningún archivo, el objeto es `null` y se devuelve 400.

**Paso 2 — Validar la extensión por contenido real:**
`$file->guessExtension()` usa `finfo_file()` internamente para detectar el tipo MIME real del archivo analizando su cabecera binaria (magic bytes), **no** por la extensión del nombre. Esto impide que alguien suba un archivo malicioso renombrándolo con extensión `.jpg`.

| Tipo MIME real | `guessExtension()` devuelve |
|----------------|----------------------------|
| `image/jpeg` | `jpg` |
| `image/png` | `png` |
| `image/gif` | `gif` |
| `image/webp` | `webp` |
| `application/pdf` | `pdf` → rechazado |

**Paso 3 — Generar nombre único:**
`uniqid('post_', true)` genera un identificador único con:
- Prefijo `post_` para identificar el tipo de recurso.
- `true` activa la entropía adicional con microsegundos decimales, lo que hace colisiones prácticamente imposibles incluso en servidores de alta concurrencia.

Ejemplo de nombre resultante: `post_66f2a3c1d4e9f.123456.jpg`

**Paso 4 — Mover el archivo:**
`$file->move($uploadDir, $filename)` mueve el archivo del directorio temporal de PHP (`/tmp`) al directorio permanente de uploads. Si el directorio no existe, lanza una excepción.

**Paso 5 — Leer la descripción:**
`$request->request->get('description', '')` lee el campo de texto del formulario multipart (no del body JSON, porque la petición es `multipart/form-data`). El operador `?: null` convierte string vacío a `null` para almacenarlo como `NULL` en BD.

**Paso 6 — Crear la entidad:**
`new Post($me, $filename, $description)` usa el constructor con parámetros (Estilo 1 de los patrones, ver [17-patrones-codigo.md](17-patrones-codigo.md)). El constructor inicializa `createdAt`, `likes` y `comments` automáticamente.

---

## 5. `DELETE /api/posts/{id}` — eliminar post

```php
#[Route('/posts/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me   = $this->getUser();
    $post = $this->postRepo->find($id);

    $isGlobalAdmin = $this->isGranted('ROLE_ADMIN');
    if (!$post || ($post->getUser()->getId() !== $me->getId() && !$isGlobalAdmin)) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    $imgPath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImagePath();
    if (file_exists($imgPath)) {
        @unlink($imgPath);
    }

    $this->em->remove($post);
    $this->em->flush();

    return $this->json(null, 204);
}
```

**Quién puede eliminar un post:**
1. El **autor** del post (`$post->getUser()->getId() !== $me->getId()` es `false`).
2. Un usuario con **`ROLE_ADMIN`** (`$isGlobalAdmin` es `true`).

La condición `!$post || (... && !$isGlobalAdmin)` devuelve 404 en todos los casos de fallo, incluido el caso donde el post existe pero no pertenece al usuario. Esto sigue el patrón de no revelar información sobre recursos ajenos.

**Eliminación del archivo en disco:**
Antes de llamar a `$em->remove()`, se borra el archivo de imagen:
1. `file_exists($imgPath)` comprueba que el archivo existe para evitar un error si ya fue eliminado manualmente.
2. `@unlink($imgPath)` borra el archivo. El `@` suprime el error si ocurre una condición de carrera entre la comprobación y el borrado.

La entidad se elimina de BD independientemente del resultado del `unlink`. Es mejor tener un registro BD huérfano (sin imagen) que una imagen huérfana (sin registro BD), ya que los archivos sin registro no se pueden gestionar desde la aplicación.

**Efecto en cascada:**
La entidad `Post` tiene `orphanRemoval: true` en sus colecciones `likes` y `comments`. Al eliminar el `Post`, Doctrine elimina automáticamente todos sus `PostLike` y `PostComment`.

---

## 6. `POST /api/posts/{id}/like` — toggle de like

```php
#[Route('/posts/{id}/like', name: 'like', requirements: ['id' => '\d+'], methods: ['POST'])]
public function like(int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $me   = $this->getUser();
    $post = $this->postRepo->find($id);

    if (!$post) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    $existing = $this->likeRepo->findByPostAndUser($post, $me);
    if ($existing) {
        // Ya tiene like → quitar
        $this->em->remove($existing);
        $this->em->flush();
        return $this->json(['liked' => false, 'likes' => $this->likeRepo->countByPost($post)]);
    }

    // No tiene like → añadir
    $this->em->persist(new PostLike($post, $me));
    $this->em->flush();

    return $this->json(['liked' => true, 'likes' => $this->likeRepo->countByPost($post)]);
}
```

**Patrón toggle:** Un único endpoint `POST` actúa como interruptor. Si ya existe un like del usuario para ese post, lo elimina; si no existe, lo crea. No hay endpoint separado para "quitar like".

**`countByPost()` después del flush:**
El conteo se hace **después** del `flush()`, ya que hasta entonces la operación no está confirmada en BD. Usando `countByPost()` después del flush se obtiene el recuento correcto que ya incluye o excluye el like recién modificado.

> **Nota sobre concurrencia:** La tabla `post_like` tiene una restricción `UNIQUE (post_id, user_id)`. Si dos peticiones simultáneas del mismo usuario intentan crear el mismo like, una fallará con error de unicidad. Esto protege contra doble click en el frontend aunque haya latencia de red.

**Respuesta en ambos casos:**
```json
// Al añadir
{ "liked": true,  "likes": 9 }

// Al quitar
{ "liked": false, "likes": 8 }
```

El frontend actualiza el contador y el estado del botón con este único dato.

---

## 7. `GET /api/posts/{id}/comments` — listar comentarios

```php
#[Route('/posts/{id}/comments', name: 'comments_list', requirements: ['id' => '\d+'], methods: ['GET'])]
public function listComments(int $id): JsonResponse
{
    $post = $this->postRepo->find($id);
    if (!$post) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    return $this->json(array_map(
        fn(PostComment $c) => $this->serializeComment($c),
        $this->commentRepo->findByPost($post)
    ));
}
```

Endpoint **público** (sin `denyAccessUnlessGranted`). Los comentarios se ordenan cronológicamente de más antiguo a más reciente (orden natural de conversación), a diferencia del feed que va de más reciente a más antiguo.

---

## 8. `POST /api/posts/{id}/comments` — añadir comentario

```php
#[Route('/posts/{id}/comments', name: 'comments_create', requirements: ['id' => '\d+'], methods: ['POST'])]
public function addComment(int $id, Request $request): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    $post = $this->postRepo->find($id);
    if (!$post) {
        return $this->json(['error' => 'Post no encontrado'], 404);
    }

    $data    = json_decode($request->getContent(), true) ?? [];
    $content = trim((string) ($data['content'] ?? ''));
    if ($content === '') {
        return $this->json(['error' => 'El comentario no puede estar vacío'], 400);
    }

    $comment = new PostComment($post, $me, $content);
    $this->em->persist($comment);
    $this->em->flush();

    return $this->json($this->serializeComment($comment), 201);
}
```

`trim()` elimina espacios en blanco al inicio y al final. Un comentario con solo espacios (`"   "`) queda vacío tras el trim y se rechaza con 400. Esta validación evita comentarios que aparecen en blanco en la interfaz.

`new PostComment($post, $me, $content)` usa el constructor parametrizado. El `createdAt` se inicializa automáticamente dentro del constructor.

---

## 9. `DELETE /api/posts/{id}/comments/{commentId}` — eliminar comentario

```php
#[Route('/posts/{id}/comments/{commentId}', ...)]
public function deleteComment(int $id, int $commentId): JsonResponse
{
    $post    = $this->postRepo->find($id);
    $comment = $this->commentRepo->find($commentId);

    if (!$post || !$comment || $comment->getPost()->getId() !== $post->getId()) {
        return $this->json(['error' => 'Comentario no encontrado'], 404);
    }

    $isOwner     = $comment->getUser()->getId() === $me->getId();
    $isPostOwner = $post->getUser()->getId() === $me->getId();

    if (!$isOwner && !$isPostOwner) {
        return $this->json(['error' => 'Sin permisos para eliminar este comentario'], 403);
    }

    $this->em->remove($comment);
    $this->em->flush();

    return $this->json(null, 204);
}
```

**Quién puede eliminar un comentario:**
- El **autor del comentario** (`$isOwner`).
- El **autor del post** (`$isPostOwner`) — puede moderar los comentarios en su propia publicación.

La validación triple `!$post || !$comment || $comment->getPost()->getId() !== $post->getId()` garantiza tres cosas:
1. El post existe.
2. El comentario existe.
3. El comentario pertenece a ese post (evita borrar un comentario de otro post con el mismo ID).

Este es uno de los pocos endpoints del proyecto que devuelve **403 en lugar de 404** para el caso de permisos denegados, porque aquí es razonable que el usuario sepa que el comentario existe pero no tiene permiso para borrarlo.

---

## 10. Helper `serialize()` — formato de un post

```php
private function serialize(Post $post, mixed $me): array
{
    $u     = $post->getUser();
    $liked = $me ? $this->likeRepo->findByPostAndUser($post, $me) !== null : false;

    return [
        'id'           => $post->getId(),
        'imagePath'    => $post->getImagePath(),
        'description'  => $post->getDescription(),
        'createdAt'    => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
        'likes'        => $this->likeRepo->countByPost($post),
        'liked'        => $liked,
        'commentCount' => $this->commentRepo->count(['post' => $post]),
        'user'         => [
            'id'          => $u->getId(),
            'displayName' => $u->getDisplayName() ?? $u->getEmail(),
            'avatar'      => $u->getAvatar(),
        ],
    ];
}
```

**Dos consultas por post serializado:**
1. `$this->likeRepo->countByPost($post)` — `SELECT COUNT(*) WHERE post_id = ?`
2. `$this->commentRepo->count(['post' => $post])` — `SELECT COUNT(*) WHERE post_id = ?`

Si `$me` no es null, hay una tercera: `findByPostAndUser()` para saber si el usuario actual dio like.

Para un feed de 40 posts, esto supone hasta 120 consultas adicionales. Es una deuda técnica conocida; la solución óptima sería una consulta con subconsultas o JOINes para obtener todos los datos en una sola pasada. Para el alcance del TFG es aceptable.

**`$u->getDisplayName() ?? $u->getEmail()`:** Fallback al email si el `displayName` es null. En la práctica nunca debería ser null (el registro siempre genera uno), pero este fallback previene un error si hubiera datos legacy o inconsistentes.

---

## 11. Resumen de endpoints

| Método | Ruta | Autenticación | Descripción |
|--------|------|---------------|-------------|
| `GET` | `/api/posts` | Requerida | Feed: posts propios + seguidos |
| `GET` | `/api/users/{id}/posts` | Pública | Posts de un usuario concreto |
| `POST` | `/api/posts` | Requerida | Crear post (multipart/form-data) |
| `DELETE` | `/api/posts/{id}` | Requerida | Eliminar (propio o admin global) |
| `POST` | `/api/posts/{id}/like` | Requerida | Toggle like/unlike |
| `GET` | `/api/posts/{id}/comments` | Pública | Listar comentarios |
| `POST` | `/api/posts/{id}/comments` | Requerida | Añadir comentario |
| `DELETE` | `/api/posts/{id}/comments/{cId}` | Requerida | Borrar (autor o dueño del post) |
