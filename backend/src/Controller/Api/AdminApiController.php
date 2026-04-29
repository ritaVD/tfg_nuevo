<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\ClubRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin', name: 'api_admin_')]
class AdminApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private ClubRepository $clubRepo,
        private PostRepository $postRepo,
    ) {}

    // -------------------------------------------------------
    // GET /api/admin/stats  →  estadísticas generales
    // -------------------------------------------------------
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->json([
            'users' => $this->userRepo->count([]),
            'clubs' => $this->clubRepo->count([]),
            'posts' => $this->postRepo->count([]),
        ]);
    }

    // -------------------------------------------------------
    // GET /api/admin/users  →  lista todos los usuarios
    // -------------------------------------------------------
    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function users(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $this->userRepo->findBy([], ['id' => 'DESC']);

        return $this->json(array_map(fn(User $u) => [
            'id'          => $u->getId(),
            'email'       => $u->getEmail(),
            'displayName' => $u->getDisplayName(),
            'avatar'      => $u->getAvatar(),
            'roles'       => $u->getRoles(),
            'isVerified'  => $u->isVerified(),
            'isAdmin'     => in_array('ROLE_ADMIN', $u->getRoles(), true),
            'isBanned'    => $u->isBanned(),
        ], $users));
    }

    // -------------------------------------------------------
    // PATCH /api/admin/users/{id}/role  →  promover/degradar admin
    // Body JSON: { "isAdmin": true|false }
    // -------------------------------------------------------
    #[Route('/users/{id}/role', name: 'users_role', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function setRole(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        /** @var User $me */
        $me = $this->getUser();
        if ($user->getId() === $me->getId()) {
            return $this->json(['error' => 'No puedes cambiar tu propio rol'], 400);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $isAdmin = (bool) ($data['isAdmin'] ?? false);

        $roles = array_filter($user->getRoles(), fn($r) => $r !== 'ROLE_ADMIN' && $r !== 'ROLE_USER');
        if ($isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles(array_values($roles));
        $this->em->flush();

        return $this->json([
            'id'      => $user->getId(),
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles(), true),
            'roles'   => $user->getRoles(),
        ]);
    }

    // -------------------------------------------------------
    // PATCH /api/admin/users/{id}/ban  →  banear/desbanear usuario
    // Body JSON: { "isBanned": true|false }
    // -------------------------------------------------------
    #[Route('/users/{id}/ban', name: 'users_ban', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function setBan(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        if ($user->getId() === $me->getId()) {
            return $this->json(['error' => 'No puedes banearte a ti mismo'], 400);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $user->setIsBanned((bool) ($data['isBanned'] ?? false));
        $this->em->flush();

        return $this->json([
            'id'       => $user->getId(),
            'isBanned' => $user->isBanned(),
        ]);
    }

    // -------------------------------------------------------
    // DELETE /api/admin/users/{id}  →  eliminar usuario
    // -------------------------------------------------------
    #[Route('/users/{id}', name: 'users_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->userRepo->find($id);
        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        /** @var User $me */
        $me = $this->getUser();
        if ($user->getId() === $me->getId()) {
            return $this->json(['error' => 'No puedes eliminar tu propia cuenta desde el panel'], 400);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // GET /api/admin/clubs  →  lista todos los clubs
    // -------------------------------------------------------
    #[Route('/clubs', name: 'clubs_list', methods: ['GET'])]
    public function clubs(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $clubs = $this->clubRepo->findBy([], ['id' => 'DESC']);

        return $this->json(array_map(fn($club) => [
            'id'          => $club->getId(),
            'name'        => $club->getName(),
            'description' => $club->getDescription(),
            'visibility'  => $club->getVisibility(),
            'memberCount' => $club->getMembers()->count(),
            'owner'       => $club->getOwner() ? [
                'id'          => $club->getOwner()->getId(),
                'displayName' => $club->getOwner()->getDisplayName(),
                'email'       => $club->getOwner()->getEmail(),
            ] : null,
            'createdAt'   => $club->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], $clubs));
    }

    // -------------------------------------------------------
    // DELETE /api/admin/clubs/{id}  →  eliminar cualquier club
    // -------------------------------------------------------
    #[Route('/clubs/{id}', name: 'clubs_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteClub(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $club = $this->clubRepo->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        $this->em->remove($club);
        $this->em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // GET /api/admin/posts  →  lista todos los posts recientes
    // -------------------------------------------------------
    #[Route('/posts', name: 'posts_list', methods: ['GET'])]
    public function posts(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $posts = $this->postRepo->findBy([], ['id' => 'DESC'], 100);

        return $this->json(array_map(fn($post) => [
            'id'          => $post->getId(),
            'description' => $post->getDescription(),
            'imagePath'   => $post->getImagePath(),
            'createdAt'   => $post->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'user'        => [
                'id'          => $post->getUser()->getId(),
                'displayName' => $post->getUser()->getDisplayName(),
                'email'       => $post->getUser()->getEmail(),
            ],
        ], $posts));
    }

    // -------------------------------------------------------
    // DELETE /api/admin/posts/{id}  →  eliminar cualquier post
    // -------------------------------------------------------
    #[Route('/posts/{id}', name: 'posts_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deletePost(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $post = $this->postRepo->find($id);
        if (!$post) {
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
}
