<?php

namespace App\Controller\Api;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/books', name: 'api_books_')]
class BookExternalApiController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private BookRepository $bookRepository,
    ) {}

    /**
     * Búsqueda avanzada (B): texto + filtros + paginación + orden + idioma + tipo + disponibilidad
     *
     * Ejemplos:
     *  /api/books/search?q=potter
     *  /api/books/search?title=dune&author=herbert
     *  /api/books/search?isbn=9788408172179
     *  /api/books/search?subject=fantasy&lang=es&orderBy=newest&printType=books
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        // --------- 1) Leer filtros (todos opcionales) ----------
        $q       = trim((string) $request->query->get('q', ''));
        $title   = trim((string) $request->query->get('title', ''));
        $author  = trim((string) $request->query->get('author', ''));
        $isbn    = preg_replace('/[^0-9Xx]/', '', (string) $request->query->get('isbn', ''));
        $subject = trim((string) $request->query->get('subject', ''));
        $publisher = trim((string) $request->query->get('publisher', ''));

        // Paginación
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = (int) $request->query->get('limit', 20);
        $limit = min(max($limit, 1), 40); // Google Books maxResults permite hasta 40
        $startIndex = ($page - 1) * $limit;

        // Filtros Google Books (opcionales)
        $orderBy = (string) $request->query->get('orderBy', 'relevance'); // relevance|newest
        if (!in_array($orderBy, ['relevance', 'newest'], true)) $orderBy = 'relevance';

        $lang = trim((string) $request->query->get('lang', '')); // ej: es, en (langRestrict)
        // Default books (not magazines) so results are cleaner
        $printType = (string) $request->query->get('printType', 'books');
        if (!in_array($printType, ['all', 'books', 'magazines'], true)) $printType = 'books';

        $filter = (string) $request->query->get('filter', ''); // partial|full|free-ebooks|paid-ebooks|ebooks
        $allowedFilter = ['partial','full','free-ebooks','paid-ebooks','ebooks',''];
        if (!in_array($filter, $allowedFilter, true)) $filter = '';

        // --------- 2) Construir q de Google Books ----------
        $parts = [];

        if ($q !== '')         $parts[] = $q;
        if ($title !== '')     $parts[] = 'intitle:' . $title;
        if ($author !== '')    $parts[] = 'inauthor:' . $author;
        if ($publisher !== '') $parts[] = 'inpublisher:' . $publisher;
        if ($subject !== '')   $parts[] = 'subject:' . $subject;
        if ($isbn !== '')      $parts[] = 'isbn:' . $isbn;

        if (count($parts) === 0) {
            return $this->json([
                'error' => 'Debes enviar al menos un filtro: q, title, author, isbn, subject o publisher'
            ], 400);
        }

        // Unimos con espacio — el HTTP client se encarga del URL encoding correcto
        $googleQ = implode(' ', $parts);

        // --------- 3) Llamada a Google Books ----------
        $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;

        // Fetch up to 2× the requested limit (capped at 40) so the re-ranker has
        // enough candidates without burning the full daily quota on every search.
        $fetchCount = min($limit * 2, 40);

        $query = [
            'q'          => $googleQ,
            'startIndex' => $startIndex,
            'maxResults' => $fetchCount,
            'orderBy'    => $orderBy,
            'printType'  => $printType,
        ];

        if ($lang !== '')   $query['langRestrict'] = $lang;
        if ($filter !== '') $query['filter'] = $filter;
        if ($apiKey)        $query['key'] = $apiKey;

        try {
            $resp = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
                'query'   => $query,
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 15,
            ]);

            if ($resp->getStatusCode() === 429) {
                return $this->buildFallbackResponse($q ?: $title ?: $author, $page, $limit, 'rate_limited');
            }

            if ($resp->getStatusCode() >= 400) {
                return $this->buildFallbackResponse($q ?: $title ?: $author, $page, $limit);
            }

            $raw = $resp->toArray(false);
        } catch (\Throwable) {
            return $this->buildFallbackResponse($q ?: $title ?: $author, $page, $limit);
        }

        // --------- 4) Normalizar ----------
        $items      = $raw['items'] ?? [];
        $totalItems = (int) ($raw['totalItems'] ?? 0);

        $items = array_slice($items, 0, $limit);

        $results = array_map(static function ($item) {
            $vi       = $item['volumeInfo'] ?? [];
            $links    = $vi['imageLinks'] ?? [];
            $industry = $vi['industryIdentifiers'] ?? [];

            $isbn10 = null;
            $isbn13 = null;
            foreach ($industry as $id) {
                if (($id['type'] ?? '') === 'ISBN_10') $isbn10 = $id['identifier'] ?? null;
                if (($id['type'] ?? '') === 'ISBN_13') $isbn13 = $id['identifier'] ?? null;
            }

            return [
                'externalId'    => $item['id'] ?? null,
                'title'         => $vi['title'] ?? null,
                'subtitle'      => $vi['subtitle'] ?? null,
                'authors'       => $vi['authors'] ?? [],
                'publisher'     => $vi['publisher'] ?? null,
                'publishedDate' => $vi['publishedDate'] ?? null,
                'categories'    => $vi['categories'] ?? [],
                'language'      => $vi['language'] ?? null,
                'description'   => $vi['description'] ?? null,
                'pageCount'     => $vi['pageCount'] ?? null,
                'averageRating' => $vi['averageRating'] ?? null,
                'ratingsCount'  => $vi['ratingsCount'] ?? null,
                'thumbnail'     => self::https($links['thumbnail'] ?? ($links['smallThumbnail'] ?? null)),
                'previewLink'   => $vi['previewLink'] ?? null,
                'infoLink'      => $vi['infoLink'] ?? null,
                'isbn10'        => $isbn10,
                'isbn13'        => $isbn13,
            ];
        }, $items);

        return $this->json([
            'page'    => $page,
            'limit'   => $limit,
            'total'   => $totalItems,
            'results' => $results,
        ]);
    }

    private function buildFallbackResponse(string $keyword, int $page, int $limit, string $reason = 'unavailable'): JsonResponse
    {
        $books = $this->bookRepository->searchFallback($keyword, $limit);

        $results = array_map(static function ($book) {
            return [
                'externalId'    => $book->getExternalId(),
                'title'         => $book->getTitle(),
                'subtitle'      => null,
                'authors'       => $book->getAuthors() ?? [],
                'publisher'     => $book->getPublisher(),
                'publishedDate' => $book->getPublishedDate(),
                'categories'    => $book->getCategories() ?? [],
                'language'      => $book->getLanguage(),
                'description'   => $book->getDescription(),
                'pageCount'     => $book->getPageCount(),
                'averageRating' => null,
                'ratingsCount'  => null,
                'thumbnail'     => self::https($book->getCoverUrl()),
                'previewLink'   => null,
                'infoLink'      => null,
                'isbn10'        => $book->getIsbn10(),
                'isbn13'        => $book->getIsbn13(),
            ];
        }, $books);

        return $this->json([
            'page'       => $page,
            'limit'      => $limit,
            'totalItems' => count($results),
            'results'    => $results,
            'fallback'   => true,
            'fallbackReason' => $reason,
        ]);
    }

    /**
     * GET /api/books/{externalId}  →  detalle de un volumen de Google Books
     *
     * Ejemplo: /api/books/zyTCAlFPjgYC
     */
    #[Route('/{externalId}', name: 'detail', methods: ['GET'])]
    public function detail(string $externalId): JsonResponse
    {
        $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? null;

        $query = [];
        if ($apiKey) {
            $query['key'] = $apiKey;
        }

        try {
            $resp = $this->httpClient->request(
                'GET',
                'https://www.googleapis.com/books/v1/volumes/' . urlencode($externalId),
                [
                    'query' => $query,
                    'headers' => ['Accept' => 'application/json'],
                ]
            );

            if ($resp->getStatusCode() === 404) {
                return $this->json(['error' => 'Libro no encontrado en Google Books'], 404);
            }

            if ($resp->getStatusCode() >= 400) {
                return $this->json([
                    'error' => 'Google Books error',
                    'status' => $resp->getStatusCode(),
                ], 502);
            }

            $item = $resp->toArray(false);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'No se pudo contactar con Google Books',
                'details' => $e->getMessage(),
            ], 502);
        }

        $vi     = $item['volumeInfo'] ?? [];
        $links  = $vi['imageLinks'] ?? [];
        $industry = $vi['industryIdentifiers'] ?? [];

        $isbn10 = null;
        $isbn13 = null;
        foreach ($industry as $id) {
            if (($id['type'] ?? '') === 'ISBN_10') $isbn10 = $id['identifier'] ?? null;
            if (($id['type'] ?? '') === 'ISBN_13') $isbn13 = $id['identifier'] ?? null;
        }

        return $this->json([
            'externalId'    => $item['id'] ?? null,
            'title'         => $vi['title'] ?? null,
            'subtitle'      => $vi['subtitle'] ?? null,
            'authors'       => $vi['authors'] ?? [],
            'publisher'     => $vi['publisher'] ?? null,
            'publishedDate' => $vi['publishedDate'] ?? null,
            'categories'    => $vi['categories'] ?? [],
            'language'      => $vi['language'] ?? null,
            'description'   => $vi['description'] ?? null,
            'pageCount'     => $vi['pageCount'] ?? null,
            'averageRating' => $vi['averageRating'] ?? null,
            'ratingsCount'  => $vi['ratingsCount'] ?? null,
            'thumbnail'     => self::https($links['thumbnail'] ?? ($links['smallThumbnail'] ?? null)),
            'previewLink'   => $vi['previewLink'] ?? null,
            'infoLink'      => $vi['infoLink'] ?? null,
            'isbn10'        => $isbn10,
            'isbn13'        => $isbn13,
        ]);
    }

    private static function https(?string $url): ?string
    {
        if ($url === null) return null;
        return str_starts_with($url, 'http://') ? 'https://' . substr($url, 7) : $url;
    }

}
