<?php

namespace App\Controller\Api;

use App\Entity\Book;
use App\Entity\ReadingProgress;
use App\Repository\BookRepository;
use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/reading-progress', name: 'api_reading_progress_')]
class ReadingProgressApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReadingProgressRepository $repo,
        private BookRepository $bookRepo,
    ) {}

    // GET /api/reading-progress
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $items = $this->repo->findBy(['user' => $this->getUser()], ['updatedAt' => 'DESC']);

        return $this->json(array_map(fn($p) => $this->serialize($p), $items));
    }

    // POST /api/reading-progress
    // Body: { externalId, mode, totalPages? }
    #[Route('', name: 'add', methods: ['POST'])]
    public function add(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data       = json_decode($request->getContent(), true) ?? [];
        $externalId = trim((string) ($data['externalId'] ?? ''));
        $mode       = in_array($data['mode'] ?? '', ['pages', 'percent']) ? $data['mode'] : 'percent';

        if ($externalId === '') {
            return $this->json(['error' => 'externalId requerido'], 400);
        }

        $book = $this->bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);
        if (!$book) {
            $book = $this->importBookFromGoogle($externalId, $httpClient);
            if (!$book) {
                return $this->json(['error' => 'No se encontró el libro en Google Books'], 404);
            }
        }

        // Check if already tracking
        $existing = $this->repo->findOneBy(['user' => $this->getUser(), 'book' => $book]);
        if ($existing) {
            return $this->json($this->serialize($existing), 200);
        }

        $progress = new ReadingProgress();
        $progress->setUser($this->getUser());
        $progress->setBook($book);
        $progress->setMode($mode);
        $progress->setStartedAt(new \DateTimeImmutable());
        $progress->setUpdatedAt(new \DateTimeImmutable());

        if (isset($data['totalPages']) && (int)$data['totalPages'] > 0) {
            $progress->setTotalPages((int)$data['totalPages']);
        }

        $this->em->persist($progress);
        $this->em->flush();

        return $this->json($this->serialize($progress), 201);
    }

    // PATCH /api/reading-progress/{id}
    // Body: { currentPage?, percent?, totalPages?, mode? }
    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $progress = $this->repo->find($id);
        if (!$progress || $progress->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['mode']) && in_array($data['mode'], ['pages', 'percent'])) {
            $progress->setMode($data['mode']);
        }
        if (array_key_exists('currentPage', $data)) {
            $progress->setCurrentPage($data['currentPage'] !== null ? max(0, (int)$data['currentPage']) : null);
        }
        if (array_key_exists('totalPages', $data)) {
            $progress->setTotalPages($data['totalPages'] !== null && (int)$data['totalPages'] > 0 ? (int)$data['totalPages'] : null);
        }
        if (array_key_exists('percent', $data)) {
            $progress->setPercent($data['percent'] !== null ? max(0, min(100, (int)$data['percent'])) : null);
        }

        $progress->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json($this->serialize($progress));
    }

    // DELETE /api/reading-progress/{id}
    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $progress = $this->repo->find($id);
        if (!$progress || $progress->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $this->em->remove($progress);
        $this->em->flush();

        return $this->json(null, 204);
    }

    private function importBookFromGoogle(string $externalId, HttpClientInterface $httpClient): ?Book
    {
        $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;
        try {
            $resp = $httpClient->request(
                'GET',
                'https://www.googleapis.com/books/v1/volumes/' . urlencode($externalId),
                ['query' => $apiKey ? ['key' => $apiKey] : [], 'headers' => ['Accept' => 'application/json']]
            );
            if ($resp->getStatusCode() !== 200) return null;
            $item = $resp->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $vi    = $item['volumeInfo'] ?? [];
        $links = $vi['imageLinks'] ?? [];
        $isbn10 = $isbn13 = null;
        foreach ($vi['industryIdentifiers'] ?? [] as $id) {
            if ($id['type'] === 'ISBN_10') $isbn10 = $id['identifier'] ?? null;
            if ($id['type'] === 'ISBN_13') $isbn13 = $id['identifier'] ?? null;
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
        $book->setPageCount(isset($vi['pageCount']) ? (int)$vi['pageCount'] : null);
        $book->setCategories($vi['categories'] ?? []);
        $book->setCoverUrl($links['thumbnail'] ?? ($links['smallThumbnail'] ?? null));
        $book->setIsbn10($isbn10);
        $book->setIsbn13($isbn13);

        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    private function serialize(ReadingProgress $p): array
    {
        $book = $p->getBook();
        return [
            'id'          => $p->getId(),
            'mode'        => $p->getMode(),
            'currentPage' => $p->getCurrentPage(),
            'totalPages'  => $p->getTotalPages() ?? $book->getPageCount(),
            'percent'     => $p->getPercent(),
            'computed'    => $p->getComputedPercent(),
            'startedAt'   => $p->getStartedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt'   => $p->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'book'        => [
                'id'         => $book->getId(),
                'externalId' => $book->getExternalId(),
                'title'      => $book->getTitle(),
                'authors'    => $book->getAuthors() ?? [],
                'coverUrl'   => $book->getCoverUrl(),
                'pageCount'  => $book->getPageCount(),
            ],
        ];
    }
}
