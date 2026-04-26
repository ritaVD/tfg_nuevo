<?php
namespace App\Controller\Api;

use App\Entity\Book;
use App\Entity\Shelf;
use App\Entity\ShelfBook;
use App\Repository\BookRepository;
use App\Repository\ShelfBookRepository;
use App\Repository\ShelfRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/shelves')]
class ShelfApiController extends AbstractController
{
    // -------------------------------------------------------
    // GET /api/shelves  →  estanterías del usuario actual
    // -------------------------------------------------------
    #[Route('', methods: ['GET'])]
    public function list(ShelfRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelves = $repo->findBy(['user' => $this->getUser()]);

        return $this->json(array_map(fn($s) => [
            'id' => $s->getId(),
            'name' => $s->getName()
        ], $shelves));
    }

    // -------------------------------------------------------
    // POST /api/shelves  →  crear estantería
    // Body JSON: { "name": "..." }
    // -------------------------------------------------------
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Name required'], 400);
        }

        $shelf = new Shelf();
        $shelf->setName($data['name']);
        $shelf->setUser($this->getUser());
        $shelf->setOrderIndex(0);
        $shelf->setCreatedAt(new \DateTimeImmutable());
        $shelf->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($shelf);
        $em->flush();

        return $this->json([
            'id' => $shelf->getId(),
            'name' => $shelf->getName()
        ], 201);
    }

    // -------------------------------------------------------
    // PATCH /api/shelves/{id}  →  renombrar estantería
    // Body JSON: { "name": "..." }
    // -------------------------------------------------------
    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function rename(int $id, Request $request, ShelfRepository $shelfRepo, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'name es obligatorio'], 400);
        }

        $shelf->setName($name);
        $shelf->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json(['id' => $shelf->getId(), 'name' => $shelf->getName()]);
    }

    // -------------------------------------------------------
    // DELETE /api/shelves/{id}  →  eliminar estantería (y sus libros)
    // -------------------------------------------------------
    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, ShelfRepository $shelfRepo, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería no encontrada'], 404);
        }

        $em->remove($shelf);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // GET /api/shelves/{id}/books  →  libros de una estantería
    // -------------------------------------------------------
    #[Route('/{id}/books', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function listBooks(int $id, ShelfRepository $shelfRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería no encontrada'], 404);
        }

        $books = array_map(fn(ShelfBook $sb) => [
            'id'         => $sb->getId(),
            'status'     => $sb->getStatus(),
            'orderIndex' => $sb->getOrderIndex(),
            'addedAt'    => $sb->getAddedAt()?->format(\DateTimeInterface::ATOM),
            'book'       => $this->serializeBook($sb->getBook()),
        ], $shelf->getShelfBooks()->toArray());

        return $this->json($books);
    }

    // -------------------------------------------------------
    // POST /api/shelves/{id}/books  →  añadir libro a estantería
    // Body JSON: { "externalId": "zyTCAlFPjgYC", "status": "want_to_read" }
    // Si el libro no existe en la BD se importa automáticamente desde Google Books.
    // -------------------------------------------------------
    #[Route('/{id}/books', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addBook(
        int $id,
        Request $request,
        ShelfRepository $shelfRepo,
        BookRepository $bookRepo,
        ShelfBookRepository $shelfBookRepo,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería no encontrada'], 404);
        }

        $data       = json_decode($request->getContent(), true) ?? [];
        $externalId = trim((string) ($data['externalId'] ?? ''));
        $status     = (string) ($data['status'] ?? 'want_to_read');

        if ($externalId === '') {
            return $this->json(['error' => 'externalId es obligatorio'], 400);
        }

        $allowedStatuses = ['want_to_read', 'reading', 'read'];
        if (!in_array($status, $allowedStatuses, true)) {
            return $this->json(['error' => 'status debe ser: ' . implode(', ', $allowedStatuses)], 400);
        }

        // Buscar el libro en BD o importarlo desde Google Books
        $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

        if (!$book) {
            $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
            if ($book === null) {
                return $this->json(['error' => 'No se encontró el libro en Google Books'], 404);
            }
        }

        // Comprobar si ya está en esta estantería
        $existing = $shelfBookRepo->findOneBy(['shelf' => $shelf, 'book' => $book]);
        if ($existing) {
            return $this->json(['error' => 'El libro ya está en esta estantería'], 409);
        }

        $maxOrder = count($shelf->getShelfBooks());

        $shelfBook = new ShelfBook();
        $shelfBook->setShelf($shelf);
        $shelfBook->setBook($book);
        $shelfBook->setStatus($status);
        $shelfBook->setOrderIndex($maxOrder);
        $shelfBook->setAddedAt(new \DateTimeImmutable());

        $em->persist($shelfBook);
        $em->flush();

        return $this->json([
            'id'         => $shelfBook->getId(),
            'status'     => $shelfBook->getStatus(),
            'orderIndex' => $shelfBook->getOrderIndex(),
            'addedAt'    => $shelfBook->getAddedAt()->format(\DateTimeInterface::ATOM),
            'book'       => $this->serializeBook($book),
        ], 201);
    }

    // -------------------------------------------------------
    // PATCH /api/shelves/{id}/books/{bookId}  →  actualizar estado
    // Body JSON: { "status": "reading" }
    // -------------------------------------------------------
    #[Route('/{id}/books/{bookId}', requirements: ['id' => '\d+', 'bookId' => '\d+'], methods: ['PATCH'])]
    public function updateBookStatus(
        int $id,
        int $bookId,
        Request $request,
        ShelfRepository $shelfRepo,
        ShelfBookRepository $shelfBookRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería no encontrada'], 404);
        }

        $shelfBook = $shelfBookRepo->find($bookId);
        if (!$shelfBook || $shelfBook->getShelf() !== $shelf) {
            return $this->json(['error' => 'Entrada no encontrada'], 404);
        }

        $data   = json_decode($request->getContent(), true) ?? [];
        $status = (string) ($data['status'] ?? '');

        $allowedStatuses = ['want_to_read', 'reading', 'read'];
        if (!in_array($status, $allowedStatuses, true)) {
            return $this->json(['error' => 'status debe ser: ' . implode(', ', $allowedStatuses)], 400);
        }

        $shelfBook->setStatus($status);
        $em->flush();

        return $this->json([
            'id'     => $shelfBook->getId(),
            'status' => $shelfBook->getStatus(),
            'book'   => $this->serializeBook($shelfBook->getBook()),
        ]);
    }

    // -------------------------------------------------------
    // GET /api/shelves/full  →  todas las estanterías del usuario con sus libros
    // -------------------------------------------------------
    #[Route('/full', methods: ['GET'])]
    public function listFull(ShelfRepository $shelfRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelves = $shelfRepo->findBy(['user' => $this->getUser()]);

        $data = array_map(fn(Shelf $shelf) => [
            'id'    => $shelf->getId(),
            'name'  => $shelf->getName(),
            'books' => array_map(fn(ShelfBook $sb) => [
                'id'         => $sb->getId(),
                'status'     => $sb->getStatus(),
                'orderIndex' => $sb->getOrderIndex(),
                'addedAt'    => $sb->getAddedAt()?->format(\DateTimeInterface::ATOM),
                'book'       => $this->serializeBook($sb->getBook()),
            ], $shelf->getShelfBooks()->toArray()),
        ], $shelves);

        return $this->json($data);
    }

    // -------------------------------------------------------
    // POST /api/shelves/{id}/books/{bookId}/move  →  mover libro a otra estantería
    // Body JSON: { "targetShelfId": 3 }
    // -------------------------------------------------------
    #[Route('/{id}/books/{bookId}/move', requirements: ['id' => '\d+', 'bookId' => '\d+'], methods: ['POST'])]
    public function moveBook(
        int $id,
        int $bookId,
        Request $request,
        ShelfRepository $shelfRepo,
        ShelfBookRepository $shelfBookRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería origen no encontrada'], 404);
        }

        $shelfBook = $shelfBookRepo->find($bookId);
        if (!$shelfBook || $shelfBook->getShelf() !== $shelf) {
            return $this->json(['error' => 'Entrada no encontrada'], 404);
        }

        $data          = json_decode($request->getContent(), true) ?? [];
        $targetShelfId = (int) ($data['targetShelfId'] ?? 0);

        if ($targetShelfId === 0) {
            return $this->json(['error' => 'targetShelfId es obligatorio'], 400);
        }

        if ($targetShelfId === $id) {
            return $this->json(['error' => 'La estantería destino es la misma que la origen'], 400);
        }

        $targetShelf = $shelfRepo->find($targetShelfId);
        if (!$targetShelf || $targetShelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería destino no encontrada'], 404);
        }

        // Comprobar que el libro no esté ya en la estantería destino
        $duplicate = $shelfBookRepo->findOneBy(['shelf' => $targetShelf, 'book' => $shelfBook->getBook()]);
        if ($duplicate) {
            return $this->json(['error' => 'El libro ya existe en la estantería destino'], 409);
        }

        $newOrder = count($targetShelf->getShelfBooks());

        $shelfBook->setShelf($targetShelf);
        $shelfBook->setOrderIndex($newOrder);
        $em->flush();

        return $this->json([
            'id'            => $shelfBook->getId(),
            'targetShelfId' => $targetShelf->getId(),
            'status'        => $shelfBook->getStatus(),
            'book'          => $this->serializeBook($shelfBook->getBook()),
        ]);
    }

    // -------------------------------------------------------
    // DELETE /api/shelves/{id}/books/{bookId}  →  quitar libro
    // -------------------------------------------------------
    #[Route('/{id}/books/{bookId}', requirements: ['id' => '\d+', 'bookId' => '\d+'], methods: ['DELETE'])]
    public function removeBook(
        int $id,
        int $bookId,
        ShelfRepository $shelfRepo,
        ShelfBookRepository $shelfBookRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $shelf = $shelfRepo->find($id);
        if (!$shelf || $shelf->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Estantería no encontrada'], 404);
        }

        $shelfBook = $shelfBookRepo->find($bookId);
        if (!$shelfBook || $shelfBook->getShelf() !== $shelf) {
            return $this->json(['error' => 'Entrada no encontrada'], 404);
        }

        $em->remove($shelfBook);
        $em->flush();

        return $this->json(null, 204);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    private function serializeBook(Book $book): array
    {
        return [
            'id'            => $book->getId(),
            'externalId'    => $book->getExternalId(),
            'title'         => $book->getTitle(),
            'authors'       => $book->getAuthors() ?? [],
            'publisher'     => $book->getPublisher(),
            'publishedDate' => $book->getPublishedDate(),
            'coverUrl'      => $book->getCoverUrl(),
            'description'   => $book->getDescription(),
            'pageCount'     => $book->getPageCount(),
            'categories'    => $book->getCategories() ?? [],
            'language'      => $book->getLanguage(),
            'isbn10'        => $book->getIsbn10(),
            'isbn13'        => $book->getIsbn13(),
        ];
    }

    private function importBookFromGoogle(string $externalId, HttpClientInterface $httpClient, EntityManagerInterface $em): ?Book
    {
        $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;

        $query = [];
        if ($apiKey) {
            $query['key'] = $apiKey;
        }

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

        $isbn10 = null;
        $isbn13 = null;
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
