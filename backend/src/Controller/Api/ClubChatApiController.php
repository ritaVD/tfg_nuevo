<?php

namespace App\Controller\Api;

use App\Entity\ClubChat;
use App\Entity\ClubChatMessage;
use App\Repository\ClubChatMessageRepository;
use App\Repository\ClubChatRepository;
use App\Repository\ClubMemberRepository;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clubs/{clubId}/chats', name: 'api_club_chats_', requirements: ['clubId' => '\d+'])]
class ClubChatApiController extends AbstractController
{
    public function __construct(
        private ClubChatMessageRepository $msgRepo,
    ) {}

    // -------------------------------------------------------
    // GET /api/clubs/{clubId}/chats  →  lista de hilos del club
    // -------------------------------------------------------
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        int $clubId,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo
    ): JsonResponse {
        $club = $clubRepo->find($clubId);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        // Clubs privados: solo miembros pueden ver los hilos
        if ($club->getVisibility() === 'private') {
            $user = $this->getUser();
            if (!$user || !$memberRepo->findOneBy(['club' => $club, 'user' => $user])) {
                return $this->json(['error' => 'Acceso denegado'], 403);
            }
        }

        $chats = $chatRepo->findByClubWithCreator($club);

        return $this->json(array_map(fn(ClubChat $c) => $this->serializeChat($c), $chats));
    }

    // -------------------------------------------------------
    // POST /api/clubs/{clubId}/chats  →  crear hilo (solo miembros)
    // Body JSON: { "title": "..." }
    // -------------------------------------------------------
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        int $clubId,
        Request $request,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepo->find($clubId);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        $membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        $isClubAdmin = $membership?->getRole() === 'admin';
        $isWebAdmin  = $this->isGranted('ROLE_ADMIN');

        if (!$isClubAdmin && !$isWebAdmin) {
            return $this->json(['error' => 'Solo los administradores del club pueden crear hilos de chat'], 403);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            return $this->json(['error' => 'title es obligatorio'], 400);
        }

        $chat = new ClubChat();
        $chat->setClub($club);
        $chat->setCreatedBy($this->getUser());
        $chat->setTitle($title);
        $chat->setIsOpen(true);
        $chat->setCreatedAt(new \DateTimeImmutable());

        $em->persist($chat);
        $em->flush();

        return $this->json($this->serializeChat($chat), 201);
    }

    // -------------------------------------------------------
    // GET /api/clubs/{clubId}/chats/{chatId}  →  detalle de un hilo
    // -------------------------------------------------------
    #[Route('/{chatId}', name: 'detail', requirements: ['chatId' => '\d+'], methods: ['GET'])]
    public function detail(
        int $clubId,
        int $chatId,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo
    ): JsonResponse {
        [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
        if ($error) return $error;

        if ($club->getVisibility() === 'private') {
            $user = $this->getUser();
            if (!$user || !$memberRepo->findOneBy(['club' => $club, 'user' => $user])) {
                return $this->json(['error' => 'Acceso denegado'], 403);
            }
        }

        return $this->json($this->serializeChat($chat));
    }

    // -------------------------------------------------------
    // PATCH /api/clubs/{clubId}/chats/{chatId}  →  editar hilo
    // Puede editar: title, isOpen
    // Quién puede: el creador del hilo o un admin del club
    // Body JSON: { "title": "...", "isOpen": false }
    // -------------------------------------------------------
    #[Route('/{chatId}', name: 'update', requirements: ['chatId' => '\d+'], methods: ['PATCH'])]
    public function update(
        int $clubId,
        int $chatId,
        Request $request,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
        if ($error) return $error;

        $membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        $isAdmin    = $membership?->getRole() === 'admin';
        $isCreator  = $chat->getCreatedBy() === $this->getUser();

        if (!$isAdmin && !$isCreator) {
            return $this->json(['error' => 'Solo el creador o un administrador pueden editar el hilo'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['title'])) {
            $title = trim((string) $data['title']);
            if ($title === '') {
                return $this->json(['error' => 'title no puede estar vacío'], 400);
            }
            $chat->setTitle($title);
        }

        if (isset($data['isOpen'])) {
            $wasOpen = $chat->isOpen();
            $chat->setIsOpen((bool) $data['isOpen']);
            if ($wasOpen && !$chat->isOpen()) {
                $chat->setClosedAt(new \DateTimeImmutable());
            } elseif (!$wasOpen && $chat->isOpen()) {
                $chat->setClosedAt(null);
            }
        }

        $em->flush();

        return $this->json($this->serializeChat($chat));
    }

    // -------------------------------------------------------
    // DELETE /api/clubs/{clubId}/chats/{chatId}  →  eliminar hilo (solo admin)
    // -------------------------------------------------------
    #[Route('/{chatId}', name: 'delete', requirements: ['chatId' => '\d+'], methods: ['DELETE'])]
    public function delete(
        int $clubId,
        int $chatId,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
        if ($error) return $error;

        $membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        if ($membership?->getRole() !== 'admin' && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Solo los administradores pueden eliminar hilos'], 403);
        }

        $em->remove($chat);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // GET /api/clubs/{clubId}/chats/{chatId}/messages
    // Lista de mensajes paginada (más antiguos primero)
    // Query params: page (default 1), limit (default 50, max 100)
    // -------------------------------------------------------
    #[Route('/{chatId}/messages', name: 'messages_list', requirements: ['chatId' => '\d+'], methods: ['GET'])]
    public function listMessages(
        int $clubId,
        int $chatId,
        Request $request,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo,
        ClubChatMessageRepository $messageRepo
    ): JsonResponse {
        [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
        if ($error) return $error;

        if ($club->getVisibility() === 'private') {
            $user = $this->getUser();
            if (!$user || !$memberRepo->findOneBy(['club' => $club, 'user' => $user])) {
                return $this->json(['error' => 'Acceso denegado'], 403);
            }
        }

        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(max((int) $request->query->get('limit', 50), 1), 100);

        $messages = $messageRepo->findPaginated($chat->getId(), $page, $limit);
        $total    = $messageRepo->countByChat($chat->getId());

        return $this->json([
            'page'     => $page,
            'limit'    => $limit,
            'total'    => $total,
            'messages' => array_map(fn(ClubChatMessage $m) => $this->serializeMessage($m), $messages),
        ]);
    }

    // -------------------------------------------------------
    // POST /api/clubs/{clubId}/chats/{chatId}/messages
    // Enviar mensaje (solo miembros, hilo abierto)
    // Body JSON: { "content": "..." }
    // -------------------------------------------------------
    #[Route('/{chatId}/messages', name: 'messages_create', requirements: ['chatId' => '\d+'], methods: ['POST'])]
    public function sendMessage(
        int $clubId,
        int $chatId,
        Request $request,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
        if ($error) return $error;

        $isMember   = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        $isWebAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isMember && !$isWebAdmin) {
            return $this->json(['error' => 'Solo los miembros pueden enviar mensajes'], 403);
        }

        if (!$chat->isOpen()) {
            return $this->json(['error' => 'El hilo está cerrado'], 400);
        }

        $data    = json_decode($request->getContent(), true) ?? [];
        $content = trim((string) ($data['content'] ?? ''));

        if ($content === '') {
            return $this->json(['error' => 'content es obligatorio'], 400);
        }

        $message = new ClubChatMessage();
        $message->setChat($chat);
        $message->setUser($this->getUser());
        $message->setContent($content);
        $message->setCreatedAt(new \DateTimeImmutable());

        $em->persist($message);
        $em->flush();

        return $this->json($this->serializeMessage($message), 201);
    }

    // -------------------------------------------------------
    // DELETE /api/clubs/{clubId}/chats/{chatId}/messages/{messageId}
    // Borrar mensaje (propio mensaje o admin del club)
    // -------------------------------------------------------
    #[Route('/{chatId}/messages/{messageId}', name: 'messages_delete', requirements: ['chatId' => '\d+', 'messageId' => '\d+'], methods: ['DELETE'])]
    public function deleteMessage(
        int $clubId,
        int $chatId,
        int $messageId,
        ClubRepository $clubRepo,
        ClubMemberRepository $memberRepo,
        ClubChatRepository $chatRepo,
        ClubChatMessageRepository $messageRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        [$club, $chat, $error] = $this->resolveChat($clubId, $chatId, $clubRepo, $chatRepo);
        if ($error) return $error;

        $message = $messageRepo->find($messageId);
        if (!$message || $message->getChat() !== $chat) {
            return $this->json(['error' => 'Mensaje no encontrado'], 404);
        }

        $membership = $memberRepo->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        $isAdmin    = $membership?->getRole() === 'admin' || $this->isGranted('ROLE_ADMIN');
        $isOwner    = $message->getUser() === $this->getUser();

        if (!$isAdmin && !$isOwner) {
            return $this->json(['error' => 'Solo puedes borrar tus propios mensajes'], 403);
        }

        $em->remove($message);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Resuelve y valida club + chat. Devuelve [Club, ClubChat, null] o [null, null, JsonResponse].
     */
    private function resolveChat(
        int $clubId,
        int $chatId,
        ClubRepository $clubRepo,
        ClubChatRepository $chatRepo
    ): array {
        $club = $clubRepo->find($clubId);
        if (!$club) {
            return [null, null, $this->json(['error' => 'Club no encontrado'], 404)];
        }

        $chat = $chatRepo->find($chatId);
        if (!$chat || $chat->getClub() !== $club) {
            return [null, null, $this->json(['error' => 'Hilo no encontrado'], 404)];
        }

        return [$club, $chat, null];
    }

    private function serializeChat(ClubChat $chat): array
    {
        return [
            'id'           => $chat->getId(),
            'title'        => $chat->getTitle(),
            'isOpen'       => $chat->isOpen(),
            'messageCount' => $this->msgRepo->countByChat($chat->getId()),
            'createdAt'    => $chat->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'closedAt'     => $chat->getClosedAt()?->format(\DateTimeInterface::ATOM),
            'createdBy'    => [
                'id'          => $chat->getCreatedBy()->getId(),
                'displayName' => $chat->getCreatedBy()->getDisplayName(),
                'avatar'      => $chat->getCreatedBy()->getAvatar(),
            ],
        ];
    }

    private function serializeMessage(ClubChatMessage $message): array
    {
        return [
            'id'        => $message->getId(),
            'content'   => $message->getContent(),
            'createdAt' => $message->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'user'      => [
                'id'          => $message->getUser()->getId(),
                'displayName' => $message->getUser()->getDisplayName(),
                'avatar'      => $message->getUser()->getAvatar(),
            ],
        ];
    }
}
