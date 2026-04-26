<?php

namespace App\Controller\Api;

use App\Entity\Book;
use App\Entity\BookReview;
use App\Repository\BookRepository;
use App\Repository\BookReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/books/{externalId}/reviews', name: 'api_book_reviews_', requirements: ['externalId' => '[^/]+'])]
class BookReviewApiController extends AbstractController
{
    // -------------------------------------------------------
    // GET /api/books/{externalId}/reviews
    // -------------------------------------------------------
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        string $externalId,
        BookRepository $bookRepo,
        BookReviewRepository $reviewRepo
    ): JsonResponse {
        $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

        if (!$book) {
            return $this->json(['stats' => ['average' => null, 'count' => 0], 'myRating' => null, 'reviews' => []]);
        }

        $me    = $this->getUser();
        $stats = $reviewRepo->getStats($book);

        $myRating = null;
        if ($me) {
            $myReview = $reviewRepo->findOneByUserAndBook($me, $book);
            if ($myReview) {
                $myRating = ['id' => $myReview->getId(), 'rating' => $myReview->getRating(), 'content' => $myReview->getContent()];
            }
        }

        $reviews = array_map(fn(BookReview $r) => $this->serializeReview($r), $reviewRepo->findByBook($book));

        return $this->json(['stats' => $stats, 'myRating' => $myRating, 'reviews' => $reviews]);
    }

    // -------------------------------------------------------
    // POST /api/books/{externalId}/reviews  →  crear o actualizar mi reseña
    // Body JSON: { "rating": 1-5, "content": "..." }
    // -------------------------------------------------------
    #[Route('', name: 'upsert', methods: ['POST'])]
    public function upsert(
        string $externalId,
        Request $request,
        BookRepository $bookRepo,
        BookReviewRepository $reviewRepo,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data    = json_decode($request->getContent(), true) ?? [];
        $rating  = (int) ($data['rating'] ?? 0);
        $content = trim((string) ($data['content'] ?? '')) ?: null;

        if ($rating < 1 || $rating > 5) {
            return $this->json(['error' => 'rating debe ser entre 1 y 5'], 400);
        }

        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

        if (!$book) {
            $book = $this->importBookFromGoogle($externalId, $httpClient, $em);
            if (!$book) {
                return $this->json(['error' => 'Libro no encontrado en Google Books'], 404);
            }
        }

        $review = $reviewRepo->findOneByUserAndBook($me, $book);

        if ($review) {
            $review->setRating($rating);
            $review->setContent($content);
        } else {
            $review = new BookReview($me, $book, $rating, $content);
            $em->persist($review);
        }

        $em->flush();

        $stats = $reviewRepo->getStats($book);

        return $this->json([
            'review' => $this->serializeReview($review),
            'stats'  => $stats,
        ], 201);
    }

    // -------------------------------------------------------
    // DELETE /api/books/{externalId}/reviews
    // -------------------------------------------------------
    #[Route('', name: 'delete', methods: ['DELETE'])]
    public function delete(
        string $externalId,
        BookRepository $bookRepo,
        BookReviewRepository $reviewRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $me */
        $me   = $this->getUser();
        $book = $bookRepo->findOneBy(['externalId' => $externalId, 'externalSource' => 'google_books']);

        if (!$book) {
            return $this->json(['error' => 'Libro no encontrado'], 404);
        }

        $review = $reviewRepo->findOneByUserAndBook($me, $book);
        if (!$review) {
            return $this->json(['error' => 'No tienes una reseña para este libro'], 404);
        }

        $em->remove($review);
        $em->flush();

        $stats = $reviewRepo->getStats($book);

        return $this->json(['stats' => $stats]);
    }

    // -------------------------------------------------------

    private function serializeReview(BookReview $r): array
    {
        $u = $r->getUser();
        return [
            'id'        => $r->getId(),
            'rating'    => $r->getRating(),
            'content'   => $r->getContent(),
            'createdAt' => $r->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'user'      => [
                'id'          => $u->getId(),
                'displayName' => $u->getDisplayName() ?? $u->getEmail(),
                'avatar'      => $u->getAvatar(),
            ],
        ];
    }

    private function importBookFromGoogle(string $externalId, HttpClientInterface $httpClient, EntityManagerInterface $em): ?Book
    {
        $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;
        $query  = $apiKey ? ['key' => $apiKey] : [];

        try {
            $resp = $httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes/' . urlencode($externalId), [
                'query' => $query, 'headers' => ['Accept' => 'application/json'],
            ]);
            if ($resp->getStatusCode() !== 200) return null;
            $item = $resp->toArray(false);
        } catch (\Throwable) {
            return null;
        }

        $vi    = $item['volumeInfo'] ?? [];
        $links = $vi['imageLinks'] ?? [];

        $isbn10 = $isbn13 = null;
        foreach ($vi['industryIdentifiers'] ?? [] as $id) {
            if (($id['type'] ?? '') === 'ISBN_10') $isbn10 = $id['identifier'] ?? null;
            if (($id['type'] ?? '') === 'ISBN_13') $isbn13 = $id['identifier'] ?? null;
        }

        $book = new Book();
        $book->setExternalSource('google_books');
        $book->setExternalId($item['id'] ?? $externalId);
        $book->setTitle($vi['title'] ?? 'Sin título');
        $book->setAuthors($vi['authors'] ?? []);
        $book->setPublisher($vi['publisher'] ?? null);
        $book->setPublishedDate($vi['publishedDate'] ?? null);
        $book->setLanguage($vi['language'] ?? null);
        $book->setPageCount($vi['pageCount'] ?? null);
        $book->setCategories($vi['categories'] ?? []);
        $book->setDescription($vi['description'] ?? null);
        $book->setCoverUrl($links['thumbnail'] ?? ($links['smallThumbnail'] ?? null));
        $book->setIsbn10($isbn10);
        $book->setIsbn13($isbn13);

        $em->persist($book);
        $em->flush();

        return $book;
    }
}
