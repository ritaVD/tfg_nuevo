<?php

namespace App\Controller\Api;

use App\Entity\Book;
use App\Entity\Club;
use App\Entity\ClubJoinRequest;
use App\Entity\ClubMember;
use App\Entity\Notification;
use App\Repository\BookRepository;
use App\Repository\ClubJoinRequestRepository;
use App\Repository\ClubMemberRepository;
use App\Repository\ClubRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clubs', name: 'api_clubs_')]
class ClubApiController extends AbstractController
{
    // -------------------------------------------------------
    // GET /api/clubs  →  lista todos los clubs públicos
    // -------------------------------------------------------
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        ClubJoinRequestRepository $requestRepo
    ): JsonResponse {
        $clubs = $clubRepository->findBy([], ['id' => 'DESC']);
        $user  = $this->getUser();

        $memberCounts   = $clubMemberRepository->getMemberCountsForClubs($clubs);
        $membershipsMap = $user ? $clubMemberRepository->getMembershipsMapForUser($user, $clubs) : [];

        $pendingMap = [];
        if ($user) {
            $pendingRequests = $requestRepo->findBy(['user' => $user, 'status' => 'pending']);
            foreach ($pendingRequests as $req) {
                $pendingMap[$req->getClub()->getId()] = true;
            }
        }

        return $this->json(array_map(function (Club $club) use ($memberCounts, $membershipsMap, $pendingMap, $user) {
            $membership = $membershipsMap[$club->getId()] ?? null;
            return [
                'id'               => $club->getId(),
                'name'             => $club->getName(),
                'description'      => $club->getDescription(),
                'visibility'       => $club->getVisibility(),
                'memberCount'      => $memberCounts[$club->getId()] ?? 0,
                'userRole'         => $membership?->getRole(),
                'hasPendingRequest'=> $user ? ($pendingMap[$club->getId()] ?? false) : false,
                'currentBook'      => $this->serializeCurrentBook($club),
            ];
        }, $clubs));
    }

    // -------------------------------------------------------
    // POST /api/clubs  →  crear club
    // Body JSON: { "name": "...", "description": "...", "visibility": "public|private" }
    // -------------------------------------------------------
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $payload    = json_decode($request->getContent(), true) ?? [];
        $name       = trim((string) ($payload['name'] ?? ''));
        $description = $payload['description'] ?? null;
        $visibility  = (string) ($payload['visibility'] ?? 'public');

        if ($name === '') {
            return $this->json(['error' => 'name es obligatorio'], 400);
        }
        if (!in_array($visibility, ['public', 'private'], true)) {
            return $this->json(['error' => 'visibility debe ser public o private'], 400);
        }

        $club = new Club();
        $club->setName($name);
        $club->setDescription($description);
        $club->setVisibility($visibility);
        $club->setOwner($this->getUser());
        $club->setCreatedAt(new \DateTimeImmutable());
        $club->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($club);

        $member = new ClubMember();
        $member->setClub($club);
        $member->setUser($this->getUser());
        $member->setRole('admin');
        $member->setJoinedAt(new \DateTimeImmutable());
        $em->persist($member);

        $em->flush();

        return $this->json([
            'id'         => $club->getId(),
            'name'       => $club->getName(),
            'visibility' => $club->getVisibility(),
        ], 201);
    }

    // -------------------------------------------------------
    // GET /api/clubs/{id}  →  detalle de un club
    // -------------------------------------------------------
    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        ClubJoinRequestRepository $requestRepo
    ): JsonResponse {
        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        $user              = $this->getUser();
        $userRole          = null;
        $hasPendingRequest = false;

        if ($user) {
            $membership = $clubMemberRepository->findOneBy(['club' => $club, 'user' => $user]);
            $userRole   = $membership?->getRole();
            if (!$userRole) {
                $pendingReq        = $requestRepo->findOneBy(['club' => $club, 'user' => $user, 'status' => 'pending']);
                $hasPendingRequest = $pendingReq !== null;
            }
        }

        $owner = $club->getOwner();

        return $this->json([
            'id'               => $club->getId(),
            'name'             => $club->getName(),
            'description'      => $club->getDescription(),
            'visibility'       => $club->getVisibility(),
            'memberCount'      => $clubMemberRepository->countByClub($club),
            'userRole'         => $userRole,
            'hasPendingRequest'=> $hasPendingRequest,
            'currentBook'      => $this->serializeCurrentBook($club),
            'owner'            => $owner ? [
                'id'          => $owner->getId(),
                'email'       => $owner->getEmail(),
                'displayName' => $owner->getDisplayName(),
            ] : null,
        ]);
    }

    // -------------------------------------------------------
    // PATCH /api/clubs/{id}  →  editar club (solo admin)
    // Body JSON: { "name": "...", "description": "...", "visibility": "public|private" }
    // -------------------------------------------------------
    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository)) {
            return $this->json(['error' => 'Solo los administradores pueden editar el club'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                return $this->json(['error' => 'name no puede estar vacío'], 400);
            }
            $club->setName($name);
        }

        if (array_key_exists('description', $data)) {
            $club->setDescription($data['description']);
        }

        if (isset($data['visibility'])) {
            if (!in_array($data['visibility'], ['public', 'private'], true)) {
                return $this->json(['error' => 'visibility debe ser public o private'], 400);
            }
            $club->setVisibility($data['visibility']);
        }

        $club->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'id'          => $club->getId(),
            'name'        => $club->getName(),
            'description' => $club->getDescription(),
            'visibility'  => $club->getVisibility(),
        ]);
    }

    // -------------------------------------------------------
    // DELETE /api/clubs/{id}  →  eliminar club (solo admin)
    // -------------------------------------------------------
    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository) && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Solo los administradores pueden eliminar el club'], 403);
        }

        $em->remove($club);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // POST /api/clubs/{id}/join  →  unirse a un club
    // -------------------------------------------------------
    #[Route('/{id}/join', name: 'join', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function join(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        $existing = $clubMemberRepository->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        if ($existing) {
            return $this->json(['status' => 'already_member', 'role' => $existing->getRole()]);
        }

        if ($club->getVisibility() === 'public') {
            $member = new ClubMember();
            $member->setClub($club);
            $member->setUser($this->getUser());
            $member->setRole('member');
            $member->setJoinedAt(new \DateTimeImmutable());
            $em->persist($member);
            $em->flush();

            return $this->json(['status' => 'joined', 'role' => 'member']);
        }

        // Club privado: solicitud de ingreso
        $existingRequest = $em->getRepository(ClubJoinRequest::class)->findOneBy([
            'club' => $club,
            'user' => $this->getUser(),
        ]);
        if ($existingRequest) {
            return $this->json(['status' => 'already_requested', 'requestStatus' => $existingRequest->getStatus()]);
        }

        $req = new ClubJoinRequest();
        $req->setClub($club);
        $req->setUser($this->getUser());
        $req->setStatus('pending');
        $req->setRequestedAt(new \DateTimeImmutable());
        $em->persist($req);
        $em->flush();

        // Notificar al admin del club
        $admin = $club->getOwner();
        if ($admin && $admin->getId() !== $this->getUser()->getId()) {
            $em->persist(new Notification($admin, $this->getUser(), Notification::TYPE_CLUB_REQUEST, null, $club, $req->getId()));
            $em->flush();
        }

        return $this->json(['status' => 'requested']);
    }

    // -------------------------------------------------------
    // DELETE /api/clubs/{id}/leave  →  abandonar club
    // -------------------------------------------------------
    #[Route('/{id}/leave', name: 'leave', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function leave(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        $membership = $clubMemberRepository->findOneBy(['club' => $club, 'user' => $this->getUser()]);
        if (!$membership) {
            return $this->json(['error' => 'No eres miembro de este club'], 404);
        }

        if ($membership->getRole() === 'admin' && $clubMemberRepository->countByClub($club) > 1) {
            return $this->json(['error' => 'El administrador no puede abandonar el club si hay otros miembros. Transfiere el rol primero.'], 400);
        }

        $em->remove($membership);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // GET /api/clubs/{id}/members  →  lista de miembros
    // -------------------------------------------------------
    #[Route('/{id}/members', name: 'members_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function members(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository
    ): JsonResponse {
        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        // Los clubs privados solo muestran miembros a otros miembros
        if ($club->getVisibility() === 'private') {
            $user = $this->getUser();
            if (!$user || !$clubMemberRepository->findOneBy(['club' => $club, 'user' => $user])) {
                return $this->json(['error' => 'Acceso denegado'], 403);
            }
        }

        $members = array_map(fn(ClubMember $m) => [
            'id'       => $m->getId(),
            'role'     => $m->getRole(),
            'joinedAt' => $m->getJoinedAt()?->format(\DateTimeInterface::ATOM),
            'user'     => [
                'id'          => $m->getUser()->getId(),
                'displayName' => $m->getUser()->getDisplayName(),
                'avatar'      => $m->getUser()->getAvatar(),
            ],
        ], $clubMemberRepository->findMembersWithUser($club));

        return $this->json($members);
    }

    // -------------------------------------------------------
    // DELETE /api/clubs/{id}/members/{memberId}  →  expulsar miembro (solo admin)
    // -------------------------------------------------------
    #[Route('/{id}/members/{memberId}', name: 'members_kick', requirements: ['id' => '\d+', 'memberId' => '\d+'], methods: ['DELETE'])]
    public function kickMember(
        int $id,
        int $memberId,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository)) {
            return $this->json(['error' => 'Solo los administradores pueden expulsar miembros'], 403);
        }

        $target = $clubMemberRepository->find($memberId);
        if (!$target || $target->getClub() !== $club) {
            return $this->json(['error' => 'Miembro no encontrado'], 404);
        }

        if ($target->getUser() === $this->getUser()) {
            return $this->json(['error' => 'No puedes expulsarte a ti mismo. Usa /leave.'], 400);
        }

        $em->remove($target);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // GET /api/clubs/{id}/requests  →  solicitudes pendientes (solo admin)
    // -------------------------------------------------------
    #[Route('/{id}/requests', name: 'requests_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function joinRequests(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        ClubJoinRequestRepository $joinRequestRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository)) {
            return $this->json(['error' => 'Solo los administradores pueden ver las solicitudes'], 403);
        }

        $requests = $joinRequestRepo->findPendingWithUser($club);

        return $this->json(array_map(fn(ClubJoinRequest $r) => [
            'id'          => $r->getId(),
            'status'      => $r->getStatus(),
            'requestedAt' => $r->getRequestedAt()?->format(\DateTimeInterface::ATOM),
            'user'        => [
                'id'          => $r->getUser()->getId(),
                'displayName' => $r->getUser()->getDisplayName(),
                'avatar'      => $r->getUser()->getAvatar(),
            ],
        ], $requests));
    }

    // -------------------------------------------------------
    // POST /api/clubs/{id}/requests/{requestId}/approve  →  aceptar solicitud (solo admin)
    // -------------------------------------------------------
    #[Route('/{id}/requests/{requestId}/approve', name: 'requests_approve', requirements: ['id' => '\d+', 'requestId' => '\d+'], methods: ['POST'])]
    public function approveRequest(
        int $id,
        int $requestId,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        ClubJoinRequestRepository $joinRequestRepo,
        EntityManagerInterface $em,
        NotificationRepository $notifRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository)) {
            return $this->json(['error' => 'Solo los administradores pueden gestionar solicitudes'], 403);
        }

        $req = $joinRequestRepo->find($requestId);
        if (!$req || $req->getClub() !== $club || $req->getStatus() !== 'pending') {
            return $this->json(['error' => 'Solicitud no encontrada'], 404);
        }

        $requester = $req->getUser();
        $req->setStatus('approved');
        $req->setResolvedBy($this->getUser());
        $req->setResolvedAt(new \DateTimeImmutable());

        $member = new ClubMember();
        $member->setClub($club);
        $member->setUser($requester);
        $member->setRole('member');
        $member->setJoinedAt(new \DateTimeImmutable());
        $em->persist($member);
        $em->flush();

        // Notificar al usuario que fue aceptado
        $em->persist(new Notification($requester, $this->getUser(), Notification::TYPE_CLUB_APPROVED, null, $club));
        $em->flush();

        // Eliminar la notificación de solicitud del admin (ya procesada)
        $notifRepo->deleteByRefIdAndType($this->getUser(), Notification::TYPE_CLUB_REQUEST, $requestId);

        return $this->json(['status' => 'approved']);
    }

    // -------------------------------------------------------
    // POST /api/clubs/{id}/requests/{requestId}/reject  →  rechazar solicitud (solo admin)
    // -------------------------------------------------------
    #[Route('/{id}/requests/{requestId}/reject', name: 'requests_reject', requirements: ['id' => '\d+', 'requestId' => '\d+'], methods: ['POST'])]
    public function rejectRequest(
        int $id,
        int $requestId,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        ClubJoinRequestRepository $joinRequestRepo,
        EntityManagerInterface $em,
        NotificationRepository $notifRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository)) {
            return $this->json(['error' => 'Solo los administradores pueden gestionar solicitudes'], 403);
        }

        $req = $joinRequestRepo->find($requestId);
        if (!$req || $req->getClub() !== $club || $req->getStatus() !== 'pending') {
            return $this->json(['error' => 'Solicitud no encontrada'], 404);
        }

        $requester = $req->getUser();
        $req->setStatus('rejected');
        $req->setResolvedBy($this->getUser());
        $req->setResolvedAt(new \DateTimeImmutable());
        $em->flush();

        // Notificar al usuario que fue rechazado
        $em->persist(new Notification($requester, $this->getUser(), Notification::TYPE_CLUB_REJECTED, null, $club));
        $em->flush();

        // Eliminar la notificación de solicitud del admin (ya procesada)
        $notifRepo->deleteByRefIdAndType($this->getUser(), Notification::TYPE_CLUB_REQUEST, $requestId);

        return $this->json(['status' => 'rejected']);
    }

    // -------------------------------------------------------
    // PUT /api/clubs/{id}/current-book  →  establecer libro del mes (solo admin)
    // Body JSON: { "externalId": "zyTCAlFPjgYC" }
    // Si el libro no está en BD se importa automáticamente de Google Books.
    // -------------------------------------------------------
    #[Route('/{id}/current-book', name: 'current_book_set', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function setCurrentBook(
        int $id,
        Request $request,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        BookRepository $bookRepository,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository) && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Solo los administradores pueden establecer el libro del mes'], 403);
        }

        $data       = json_decode($request->getContent(), true) ?? [];
        $externalId = trim((string) ($data['externalId'] ?? ''));

        if ($externalId === '') {
            return $this->json(['error' => 'externalId es obligatorio'], 400);
        }

        // Parse date range (ISO date strings: "2026-04-01")
        $dateFrom = null;
        $dateUntil = null;

        if (!empty($data['dateFrom'])) {
            $dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d', $data['dateFrom']);
            if (!$dateFrom) {
                return $this->json(['error' => 'dateFrom no es una fecha válida (YYYY-MM-DD)'], 400);
            }
            $dateFrom = $dateFrom->setTime(0, 0, 0);
        } else {
            $dateFrom = new \DateTimeImmutable('today');
        }

        if (!empty($data['dateUntil'])) {
            $dateUntil = \DateTimeImmutable::createFromFormat('Y-m-d', $data['dateUntil']);
            if (!$dateUntil) {
                return $this->json(['error' => 'dateUntil no es una fecha válida (YYYY-MM-DD)'], 400);
            }
            $dateUntil = $dateUntil->setTime(23, 59, 59);
            if ($dateUntil <= $dateFrom) {
                return $this->json(['error' => 'La fecha de fin debe ser posterior a la de inicio'], 400);
            }
        }

        $book = $bookRepository->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);
        if (!$book) {
            $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
            if (!$book) {
                return $this->json(['error' => 'No se encontró el libro en Google Books'], 404);
            }
        }

        $club->setCurrentBook($book);
        $club->setCurrentBookSince($dateFrom);
        $club->setCurrentBookUntil($dateUntil);
        $club->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->serializeCurrentBook($club));
    }

    // -------------------------------------------------------
    // DELETE /api/clubs/{id}/current-book  →  quitar libro del mes (solo admin)
    // -------------------------------------------------------
    #[Route('/{id}/current-book', name: 'current_book_remove', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function removeCurrentBook(
        int $id,
        ClubRepository $clubRepository,
        ClubMemberRepository $clubMemberRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $club = $clubRepository->find($id);
        if (!$club) {
            return $this->json(['error' => 'Club no encontrado'], 404);
        }

        if (!$this->isAdmin($club, $clubMemberRepository) && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Solo los administradores pueden modificar el libro del mes'], 403);
        }

        $club->setCurrentBook(null);
        $club->setCurrentBookSince(null);
        $club->setCurrentBookUntil(null);
        $club->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // Helper: ¿es el usuario actual admin del club?
    // -------------------------------------------------------
    private function isAdmin(Club $club, ClubMemberRepository $clubMemberRepository): bool
    {
        $membership = $clubMemberRepository->findOneBy([
            'club' => $club,
            'user' => $this->getUser(),
        ]);

        return $membership?->getRole() === 'admin';
    }

    private function serializeCurrentBook(Club $club): ?array
    {
        $book = $club->getCurrentBook();
        if (!$book) {
            return null;
        }

        return [
            'id'            => $book->getId(),
            'externalId'    => $book->getExternalId(),
            'title'         => $book->getTitle(),
            'authors'       => $book->getAuthors() ?? [],
            'coverUrl'      => $book->getCoverUrl(),
            'publishedDate' => $book->getPublishedDate(),
            'since'         => $club->getCurrentBookSince()?->format('Y-m-d'),
            'until'         => $club->getCurrentBookUntil()?->format('Y-m-d'),
        ];
    }

    private function importBookFromGoogle(string $externalId, HttpClientInterface $httpClient, EntityManagerInterface $em): ?Book
    {
        $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;
        $query  = $apiKey ? ['key' => $apiKey] : [];

        try {
            $resp = $httpClient->request(
                'GET',
                'https://www.googleapis.com/books/v1/volumes/' . urlencode($externalId),
                ['query' => $query, 'headers' => ['Accept' => 'application/json']]
            );

            if ($resp->getStatusCode() !== 200) {
                return null;
            }

            $item = $resp->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $vi       = $item['volumeInfo'] ?? [];
        $links    = $vi['imageLinks'] ?? [];
        $industry = $vi['industryIdentifiers'] ?? [];
        $isbn10   = null;
        $isbn13   = null;

        foreach ($industry as $identifier) {
            if (($identifier['type'] ?? '') === 'ISBN_10') $isbn10 = $identifier['identifier'] ?? null;
            if (($identifier['type'] ?? '') === 'ISBN_13') $isbn13 = $identifier['identifier'] ?? null;
        }

        $book = new Book();
        $book->setExternalSource('google_books');
        $book->setExternalId($item['id'] ?? $externalId);
        $book->setTitle($vi['title'] ?? 'Sin título');
        $book->setAuthors($vi['authors'] ?? []);
        $book->setPublisher($vi['publisher'] ?? null);
        $book->setPublishedDate($vi['publishedDate'] ?? null);
        $book->setLanguage($vi['language'] ?? null);
        $book->setDescription($vi['description'] ?? null);
        $book->setPageCount(isset($vi['pageCount']) ? (int) $vi['pageCount'] : null);
        $book->setCategories($vi['categories'] ?? []);
        $book->setCoverUrl($links['thumbnail'] ?? ($links['smallThumbnail'] ?? null));
        $book->setIsbn10($isbn10);
        $book->setIsbn13($isbn13);

        $em->persist($book);
        $em->flush();

        return $book;
    }
}
