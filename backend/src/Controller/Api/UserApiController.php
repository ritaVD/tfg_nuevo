<?php

namespace App\Controller\Api;

use App\Entity\ClubJoinRequest;
use App\Entity\User;
use App\Repository\ClubJoinRequestRepository;
use App\Repository\ClubMemberRepository;
use App\Repository\FollowRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_user_')]
class UserApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private FollowRepository $followRepo,
    ) {}

    // -------------------------------------------------------
    // GET /api/profile  →  perfil completo del usuario actual
    // -------------------------------------------------------
    #[Route('/profile', name: 'profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->serializeOwnProfile($user));
    }

    // -------------------------------------------------------
    // PUT /api/profile  →  editar displayName y bio
    // Body JSON: { "displayName": "...", "bio": "..." }
    // -------------------------------------------------------
    #[Route('/profile', name: 'profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['displayName'])) {
            $displayName = trim((string) $data['displayName']);

            if ($displayName === '') {
                return $this->json(['error' => 'El nombre de usuario no puede estar vacío'], 400);
            }

            if (strlen($displayName) < 3) {
                return $this->json(['error' => 'El nombre de usuario debe tener al menos 3 caracteres'], 400);
            }

            if (!preg_match('/^[\w.\-]+$/u', $displayName)) {
                return $this->json(['error' => 'Solo letras, números, puntos, guiones y guiones bajos'], 400);
            }

            // Check uniqueness (exclude self)
            $existing = $userRepository->findOneBy(['displayName' => $displayName]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['error' => 'Este nombre de usuario ya está en uso'], 409);
            }

            $user->setDisplayName($displayName);
        }

        if (array_key_exists('bio', $data)) {
            $bio = trim((string) $data['bio']);
            $user->setBio($bio !== '' ? $bio : null);
        }

        $this->em->flush();

        return $this->json($this->serializeOwnProfile($user));
    }

    // -------------------------------------------------------
    // POST /api/profile/avatar  →  subir avatar
    // Form-data: campo "avatar" con el archivo
    // -------------------------------------------------------
    #[Route('/profile/avatar', name: 'profile_avatar', methods: ['POST'])]
    public function uploadAvatar(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $file = $request->files->get('avatar');

        if (!$file) {
            return $this->json(['error' => 'No se envió ningún archivo'], 400);
        }

        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
            $filename
        );

        $user->setAvatar($filename);
        $this->em->flush();

        return $this->json(['avatar' => $filename]);
    }

    // -------------------------------------------------------
    // PUT /api/profile/password  →  cambiar contraseña
    // Body JSON: { "currentPassword": "...", "newPassword": "..." }
    // -------------------------------------------------------
    #[Route('/profile/password', name: 'profile_password', methods: ['PUT'])]
    public function changePassword(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $currentPassword = (string) ($data['currentPassword'] ?? '');
        $newPassword     = (string) ($data['newPassword'] ?? '');

        if ($currentPassword === '' || $newPassword === '') {
            return $this->json(['error' => 'Se requieren currentPassword y newPassword'], 400);
        }

        if (!$this->hasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'La contraseña actual es incorrecta'], 400);
        }

        if (strlen($newPassword) < 6) {
            return $this->json(['error' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
        }

        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $this->em->flush();

        return $this->json(['status' => 'password_updated']);
    }

    // -------------------------------------------------------
    // PUT /api/profile/privacy  →  configurar qué es público
    // Body JSON: { "shelvesPublic": true, "clubsPublic": false }
    // -------------------------------------------------------
    #[Route('/profile/privacy', name: 'profile_privacy', methods: ['PUT'])]
    public function updatePrivacy(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['shelvesPublic'])) {
            $user->setShelvesPublic((bool) $data['shelvesPublic']);
        }

        if (isset($data['clubsPublic'])) {
            $user->setClubsPublic((bool) $data['clubsPublic']);
        }

        if (isset($data['isPrivate'])) {
            $user->setIsPrivate((bool) $data['isPrivate']);
        }

        $this->em->flush();

        return $this->json($this->serializeOwnProfile($user));
    }

    // -------------------------------------------------------
    // GET /api/my-requests  →  solicitudes de ingreso enviadas por el usuario
    // -------------------------------------------------------
    #[Route('/my-requests', name: 'my_requests', methods: ['GET'])]
    public function myRequests(ClubJoinRequestRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $requests = $repo->findBy(['user' => $this->getUser()], ['requestedAt' => 'DESC']);

        return $this->json(array_map(fn(ClubJoinRequest $r) => [
            'id'          => $r->getId(),
            'status'      => $r->getStatus(),
            'requestedAt' => $r->getRequestedAt()?->format(\DateTimeInterface::ATOM),
            'club'        => [
                'id'         => $r->getClub()->getId(),
                'name'       => $r->getClub()->getName(),
                'visibility' => $r->getClub()->getVisibility(),
            ],
        ], $requests));
    }

    // -------------------------------------------------------
    // GET /api/admin-requests  →  solicitudes pendientes en clubs donde el usuario es admin
    // -------------------------------------------------------
    #[Route('/admin-requests', name: 'admin_requests', methods: ['GET'])]
    public function adminRequests(ClubMemberRepository $memberRepo, ClubJoinRequestRepository $requestRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Clubs donde el usuario es admin
        $memberships = $memberRepo->findBy(['user' => $this->getUser(), 'role' => 'admin']);

        $result = [];
        foreach ($memberships as $membership) {
            $club     = $membership->getClub();
            $pending  = $requestRepo->findBy(['club' => $club, 'status' => 'pending']);

            foreach ($pending as $req) {
                $result[] = [
                    'id'          => $req->getId(),
                    'status'      => $req->getStatus(),
                    'requestedAt' => $req->getRequestedAt()?->format(\DateTimeInterface::ATOM),
                    'club'        => [
                        'id'   => $club->getId(),
                        'name' => $club->getName(),
                    ],
                    'user' => [
                        'id'          => $req->getUser()->getId(),
                        'displayName' => $req->getUser()->getDisplayName() ?? $req->getUser()->getEmail(),
                    ],
                ];
            }
        }

        return $this->json($result);
    }

    // -------------------------------------------------------
    // GET /api/users/search?q=...  →  buscar usuarios por displayName
    // -------------------------------------------------------
    #[Route('/users/search', name: 'user_search', methods: ['GET'])]
    public function search(Request $request, UserRepository $userRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if (strlen($q) < 2) {
            return $this->json([]);
        }

        /** @var User|null $me */
        $me    = $this->getUser();
        $users = $userRepository->search($q);

        return $this->json(array_map(function (User $u) use ($me) {
            $followStatus = 'none';
            if ($me && $me->getId() !== $u->getId()) {
                $follow = $this->followRepo->findFollow($me, $u);
                if ($follow) {
                    $followStatus = $follow->getStatus();
                }
            }
            return [
                'id'          => $u->getId(),
                'displayName' => $u->getDisplayName(),
                'avatar'      => $u->getAvatar(),
                'bio'         => $u->getBio(),
                'followers'   => $this->followRepo->countFollowers($u),
                'followStatus'=> $followStatus,
                'isMe'        => $me && $me->getId() === $u->getId(),
            ];
        }, $users));
    }

    // -------------------------------------------------------
    // GET /api/users/{id}  →  perfil público de otro usuario
    // Respeta la configuración de privacidad del usuario
    // -------------------------------------------------------
    #[Route('/users/{id}', name: 'user_public', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getUserProfile(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        /** @var User|null $me */
        $me           = $this->getUser();
        $followStatus = 'none';
        if ($me && $me->getId() !== $user->getId()) {
            $follow = $this->followRepo->findFollow($me, $user);
            if ($follow) {
                $followStatus = $follow->getStatus(); // 'pending' | 'accepted'
            }
        }

        return $this->json([
            'id'           => $user->getId(),
            'displayName'  => $user->getDisplayName(),
            'bio'          => $user->getBio(),
            'avatar'       => $user->getAvatar(),
            'followers'    => $this->followRepo->countFollowers($user),
            'following'    => $this->followRepo->countFollowing($user),
            'followStatus' => $followStatus,
            'isFollowing'  => $followStatus === 'accepted',
            'shelves'     => $user->isShelvesPublic()
                ? array_map(
                    fn($s) => [
                        'id'    => $s->getId(),
                        'name'  => $s->getName(),
                        'books' => array_map(
                            fn($sb) => [
                                'id'       => $sb->getBook()->getId(),
                                'title'    => $sb->getBook()->getTitle(),
                                'authors'  => $sb->getBook()->getAuthors() ?? [],
                                'coverUrl' => $sb->getBook()->getCoverUrl(),
                                'thumbnail'=> $sb->getBook()->getCoverUrl(),
                            ],
                            $s->getShelfBooks()->toArray()
                        ),
                    ],
                    $user->getShelves()->toArray()
                )
                : null,
            'clubs'       => $user->isClubsPublic()
                ? array_map(
                    fn($m) => [
                        'id'         => $m->getClub()->getId(),
                        'name'       => $m->getClub()->getName(),
                        'visibility' => $m->getClub()->getVisibility(),
                        'role'       => $m->getRole(),
                    ],
                    $user->getClubMemberships()->toArray()
                )
                : null,
        ]);
    }

    // -------------------------------------------------------
    // Serializa el perfil completo del usuario autenticado
    // -------------------------------------------------------
    private function serializeOwnProfile(User $user): array
    {
        return [
            'id'            => $user->getId(),
            'email'         => $user->getEmail(),
            'displayName'   => $user->getDisplayName(),
            'bio'           => $user->getBio(),
            'avatar'        => $user->getAvatar(),
            'shelvesPublic' => $user->isShelvesPublic(),
            'clubsPublic'   => $user->isClubsPublic(),
            'isPrivate'     => $user->isPrivate(),
            'followers'     => $this->followRepo->countFollowers($user),
            'following'     => $this->followRepo->countFollowing($user),
            'shelves'       => array_map(
                fn($s) => ['id' => $s->getId(), 'name' => $s->getName()],
                $user->getShelves()->toArray()
            ),
            'clubs'         => array_map(
                fn($m) => [
                    'id'         => $m->getClub()->getId(),
                    'name'       => $m->getClub()->getName(),
                    'visibility' => $m->getClub()->getVisibility(),
                    'role'       => $m->getRole(),
                ],
                $user->getClubMemberships()->toArray()
            ),
        ];
    }
}
