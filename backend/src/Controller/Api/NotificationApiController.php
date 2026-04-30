<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Repository\FollowRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_notif_')]
class NotificationApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationRepository $repo,
    ) {}

    // ── GET /api/notifications  →  últimas notificaciones del usuario ──
    #[Route('/notifications', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $notifs = $this->repo->findForUser($me, 30);

        return $this->json([
            'unread' => $this->repo->countUnread($me),
            'items'  => array_map(fn(Notification $n) => $this->serialize($n), $notifs),
        ]);
    }

    // ── GET /api/notifications/history  →  historial completo ──
    #[Route('/notifications/history', name: 'history', methods: ['GET'])]
    public function history(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $notifs = $this->repo->findAllForUser($me, 100);

        return $this->json([
            'items' => array_map(fn(Notification $n) => $this->serialize($n), $notifs),
        ]);
    }

    // ── POST /api/notifications/read-all  →  marcar todas como leídas ──
    #[Route('/notifications/read-all', name: 'read_all', methods: ['POST'])]
    public function readAll(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me = $this->getUser();
        $this->repo->markAllRead($me);

        return $this->json(['unread' => 0]);
    }

    // ── POST /api/notifications/follow-requests/{followId}/accept ──
    #[Route('/notifications/follow-requests/{followId}/accept', name: 'follow_accept', methods: ['POST'], requirements: ['followId' => '\d+'])]
    public function acceptFollow(int $followId, FollowRepository $followRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $follow = $followRepo->find($followId);

        if (!$follow || $follow->getFollowing()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Solicitud no encontrada'], 404);
        }
        if ($follow->isAccepted()) {
            return $this->json(['error' => 'Ya aceptada'], 409);
        }

        $requester = $follow->getFollower();
        $follow->accept();
        $this->em->flush();

        // Notificar al solicitante
        $this->em->persist(new Notification($requester, $me, Notification::TYPE_FOLLOW_ACCEPTED));
        $this->em->flush();

        // Eliminar la notificación de solicitud del receptor (ya procesada)
        $this->repo->deleteByRefIdAndType($me, Notification::TYPE_FOLLOW_REQUEST, $followId);

        return $this->json(['status' => 'accepted']);
    }

    // ── DELETE /api/notifications/follow-requests/{followId} (rechazar) ──
    #[Route('/notifications/follow-requests/{followId}', name: 'follow_decline', methods: ['DELETE'], requirements: ['followId' => '\d+'])]
    public function declineFollow(int $followId, FollowRepository $followRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me     = $this->getUser();
        $follow = $followRepo->find($followId);

        if (!$follow || $follow->getFollowing()->getId() !== $me->getId()) {
            return $this->json(['error' => 'Solicitud no encontrada'], 404);
        }

        $this->em->remove($follow);
        $this->em->flush();

        // Eliminar la notificación de solicitud del receptor (ya procesada)
        $this->repo->deleteByRefIdAndType($me, Notification::TYPE_FOLLOW_REQUEST, $followId);

        return $this->json(['status' => 'declined']);
    }

    private function serialize(Notification $n): array
    {
        $actor = $n->getActor();
        $post  = $n->getPost();
        $club  = $n->getClub();

        return [
            'id'        => $n->getId(),
            'type'      => $n->getType(),
            'isRead'    => $n->isRead(),
            'createdAt' => $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'refId'     => $n->getRefId(),
            'actor'     => $actor ? [
                'id'          => $actor->getId(),
                'displayName' => $actor->getDisplayName() ?? $actor->getEmail(),
                'avatar'      => $actor->getAvatar(),
            ] : null,
            'post'      => $post ? [
                'id'        => $post->getId(),
                'imagePath' => $post->getImagePath(),
            ] : null,
            'club'      => $club ? [
                'id'   => $club->getId(),
                'name' => $club->getName(),
            ] : null,
        ];
    }
}
