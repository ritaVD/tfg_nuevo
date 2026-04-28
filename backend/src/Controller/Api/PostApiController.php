<?php

namespace App\Controller\Api;

use App\Entity\Post;
use App\Entity\PostComment;
use App\Entity\PostLike;
use App\Repository\PostCommentRepository;
use App\Repository\PostLikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_posts_')]
class PostApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PostRepository $postRepo,
        private PostLikeRepository $likeRepo,
        private PostCommentRepository $commentRepo,
    ) {}

    // ── GET /api/posts  →  feed del usuario autenticado ──────
    #[Route('/posts', name: 'feed', methods: ['GET'])]
    public function feed(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me    = $this->getUser();
        $posts = $this->postRepo->findFeed($me, 40);

        return $this->json(array_map(fn(Post $p) => $this->serialize($p, $me), $posts));
    }

    // ── GET /api/users/{id}/posts  →  posts de un usuario ───
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

    // ── POST /api/posts  →  crear post (multipart/form-data) ─
    // Campos: image (file), description (string, opcional)
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

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file->move($uploadDir, $filename);

        $description = trim((string) ($request->request->get('description', ''))) ?: null;

        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $post = new Post($me, $filename, $description);
        $this->em->persist($post);
        $this->em->flush();

        return $this->json($this->serialize($post, $me), 201);
    }

    // ── DELETE /api/posts/{id}  →  eliminar post propio ──────
    #[Route('/posts/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $post = $this->postRepo->find($id);

        $isGlobalAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$post || ($post->getUser()->getId() !== $me->getId() && !$isGlobalAdmin)) {
            return $this->json(['error' => 'Post no encontrado'], 404);
        }

        // Eliminar imagen del disco
        $imgPath = $this->getParameter('kernel.project_dir') . '/public/uploads/posts/' . $post->getImagePath();
        if (file_exists($imgPath)) {
            @unlink($imgPath);
        }

        $this->em->remove($post);
        $this->em->flush();

        return $this->json(null, 204);
    }

    // ── POST /api/posts/{id}/like  →  toggle like ────────────
    #[Route('/posts/{id}/like', name: 'like', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function like(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $post = $this->postRepo->find($id);

        if (!$post) {
            return $this->json(['error' => 'Post no encontrado'], 404);
        }

        $existing = $this->likeRepo->findByPostAndUser($post, $me);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
            return $this->json(['liked' => false, 'likes' => $this->likeRepo->countByPost($post)]);
        }

        $this->em->persist(new PostLike($post, $me));
        $this->em->flush();

        return $this->json(['liked' => true, 'likes' => $this->likeRepo->countByPost($post)]);
    }

    // ── GET /api/posts/{id}/comments  →  comentarios ─────────
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

    // ── POST /api/posts/{id}/comments  →  añadir comentario ──
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

        /** @var \App\Entity\User $me */
        $me      = $this->getUser();
        $comment = new PostComment($post, $me, $content);
        $this->em->persist($comment);
        $this->em->flush();

        return $this->json($this->serializeComment($comment), 201);
    }

    // ── DELETE /api/posts/{id}/comments/{commentId} ──────────
    #[Route('/posts/{id}/comments/{commentId}', name: 'comments_delete', requirements: ['id' => '\d+', 'commentId' => '\d+'], methods: ['DELETE'])]
    public function deleteComment(int $id, int $commentId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me      = $this->getUser();
        $post    = $this->postRepo->find($id);
        $comment = $this->commentRepo->find($commentId);

        if (!$post || !$comment || $comment->getPost()->getId() !== $post->getId()) {
            return $this->json(['error' => 'Comentario no encontrado'], 404);
        }

        $isOwner   = $comment->getUser()->getId() === $me->getId();
        $isPostOwner = $post->getUser()->getId() === $me->getId();

        if (!$isOwner && !$isPostOwner) {
            return $this->json(['error' => 'Sin permisos para eliminar este comentario'], 403);
        }

        $this->em->remove($comment);
        $this->em->flush();

        return $this->json(null, 204);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function serialize(Post $post, mixed $me): array
    {
        $u     = $post->getUser();
        $meId  = $me ? (method_exists($me, 'getId') ? $me->getId() : null) : null;
        $liked = $me ? $this->likeRepo->findByPostAndUser($post, $me) !== null : false;

        return [
            'id'          => $post->getId(),
            'imagePath'   => $post->getImagePath(),
            'description' => $post->getDescription(),
            'createdAt'   => $post->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'likes'       => $this->likeRepo->countByPost($post),
            'liked'       => $liked,
            'commentCount'=> $this->commentRepo->count(['post' => $post]),
            'user'        => [
                'id'          => $u->getId(),
                'displayName' => $u->getDisplayName() ?? $u->getEmail(),
                'avatar'      => $u->getAvatar(),
            ],
        ];
    }

    private function serializeComment(PostComment $c): array
    {
        $u = $c->getUser();
        return [
            'id'        => $c->getId(),
            'content'   => $c->getContent(),
            'createdAt' => $c->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'user'      => [
                'id'          => $u->getId(),
                'displayName' => $u->getDisplayName() ?? $u->getEmail(),
                'avatar'      => $u->getAvatar(),
            ],
        ];
    }
}
