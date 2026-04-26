<?php

namespace App\Controller\Api;

use App\Entity\Follow;
use App\Entity\Notification;
use App\Repository\FollowRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class FollowApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FollowRepository $followRepo,
        private UserRepository $userRepo,
    ) {}

    // ── POST /api/users/{id}/follow ──────────────────────────
    #[Route('/api/users/{id}/follow', name: 'api_follow', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function follow(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $target = $this->userRepo->find($id);

        if (!$target) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }
        if ($me->getId() === $target->getId()) {
            return $this->json(['error' => 'No puedes seguirte a ti mismo'], 400);
        }
        if ($this->followRepo->findFollow($me, $target)) {
            return $this->json(['error' => 'Ya enviaste una solicitud o sigues a este usuario'], 409);
        }

        $status = $target->isPrivate() ? Follow::STATUS_PENDING : Follow::STATUS_ACCEPTED;
        $follow = new Follow($me, $target, $status);
        $this->em->persist($follow);
        $this->em->flush();

        // Notificación al destinatario
        if ($status === Follow::STATUS_ACCEPTED) {
            $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW));
        } else {
            $this->em->persist(new Notification($target, $me, Notification::TYPE_FOLLOW_REQUEST, null, null, $follow->getId()));
        }
        $this->em->flush();

        return $this->json([
            'status'      => $status,
            'isFollowing' => $status === Follow::STATUS_ACCEPTED,
            'followers'   => $this->followRepo->countFollowers($target),
        ]);
    }

    // ── DELETE /api/users/{id}/follow ────────────────────────
    #[Route('/api/users/{id}/follow', name: 'api_unfollow', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function unfollow(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $target = $this->userRepo->find($id);

        if (!$target) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        $follow = $this->followRepo->findFollow($me, $target);
        if (!$follow) {
            return $this->json(['error' => 'No sigues a este usuario'], 404);
        }

        $this->em->remove($follow);
        $this->em->flush();

        return $this->json([
            'status'      => null,
            'isFollowing' => false,
            'followers'   => $this->followRepo->countFollowers($target),
        ]);
    }

    // ── GET /api/users/{id}/followers ────────────────────────
    #[Route('/api/users/{id}/followers', name: 'api_followers', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function followers(int $id): JsonResponse
    {
        $target = $this->userRepo->find($id);
        if (!$target) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        return $this->json(array_map(
            fn(Follow $f) => $this->serializeUser($f->getFollower()),
            $this->followRepo->findFollowers($target)
        ));
    }

    // ── GET /api/users/{id}/following ────────────────────────
    #[Route('/api/users/{id}/following', name: 'api_following_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function following(int $id): JsonResponse
    {
        $target = $this->userRepo->find($id);
        if (!$target) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        return $this->json(array_map(
            fn(Follow $f) => $this->serializeUser($f->getFollowing()),
            $this->followRepo->findFollowing($target)
        ));
    }

    // ── DELETE /api/users/{id}/followers  →  eliminar un seguidor ──
    #[Route('/api/users/{id}/followers', name: 'api_remove_follower', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function removeFollower(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me       = $this->getUser();
        $follower = $this->userRepo->find($id);

        if (!$follower) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // El follow a eliminar es: follower=$follower, following=$me
        $follow = $this->followRepo->findFollow($follower, $me);
        if (!$follow) {
            return $this->json(['error' => 'Este usuario no te sigue'], 404);
        }

        $this->em->remove($follow);
        $this->em->flush();

        return $this->json([
            'followers' => $this->followRepo->countFollowers($me),
        ]);
    }

    // ── GET /api/follow-requests  (mis solicitudes entrantes) ─
    #[Route('/api/follow-requests', name: 'api_follow_requests', methods: ['GET'])]
    public function incomingRequests(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me = $this->getUser();

        return $this->json(array_map(function (Follow $f) {
            return [
                'id'        => $f->getId(),
                'createdAt' => $f->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'user'      => $this->serializeUser($f->getFollower()),
            ];
        }, $this->followRepo->findIncomingRequests($me)));
    }

    // ── POST /api/follow-requests/{id}/accept ────────────────
    #[Route('/api/follow-requests/{id}/accept', name: 'api_follow_accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $follow = $this->followRepo->find($id);

        if (!$follow || $follow->getFollowing()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Solicitud no encontrada'], 404);
        }
        if ($follow->isAccepted()) {
            return $this->json(['error' => 'Ya aceptada'], 409);
        }

        $requester = $follow->getFollower();
        $follow->accept();
        $this->em->flush();

        // Notificar al que envió la solicitud que fue aceptada
        $this->em->persist(new Notification($requester, $me, Notification::TYPE_FOLLOW_ACCEPTED));
        $this->em->flush();

        return $this->json(['status' => 'accepted']);
    }

    // ── DELETE /api/follow-requests/{id} (rechazar) ──────────
    #[Route('/api/follow-requests/{id}', name: 'api_follow_decline', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function decline(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $follow = $this->followRepo->find($id);

        if (!$follow || $follow->getFollowing()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Solicitud no encontrada'], 404);
        }

        $this->em->remove($follow);
        $this->em->flush();

        return $this->json(['status' => 'declined']);
    }

    private function serializeUser(\App\Entity\User $u): array
    {
        return [
            'id'          => $u->getId(),
            'displayName' => $u->getDisplayName() ?? $u->getEmail(),
            'avatar'      => $u->getAvatar(),
            'email'       => $u->getEmail(),
        ];
    }
}
