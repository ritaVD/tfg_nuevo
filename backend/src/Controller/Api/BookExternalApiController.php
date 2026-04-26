<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/books', name: 'api_books_')]
class BookExternalApiController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient) {}

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

        // Always fetch the maximum allowed (40) so we have enough candidates
        // to filter and re-rank by popularity before returning only $limit items.
        $query = [
            'q'          => $googleQ,
            'startIndex' => $startIndex,
            'maxResults' => 40,
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
            ]);

            if ($resp->getStatusCode() >= 400) {
                return $this->json([
                    'error'   => 'Google Books error',
                    'status'  => $resp->getStatusCode(),
                    'details' => $resp->getContent(false),
                ], 502);
            }

            $raw = $resp->toArray(false);
        } catch (\Throwable $e) {
            return $this->json([
                'error'   => 'No se pudo contactar con Google Books',
                'details' => $e->getMessage(),
            ], 502);
        }

        // --------- 4) Normalizar y filtrar por popularidad ----------
        $items      = $raw['items'] ?? [];
        $totalItems = (int) ($raw['totalItems'] ?? 0);

        // Step 1 — keep only items that have a cover image.
        // Books without a thumbnail are almost always obscure/incomplete entries.
        $items = array_values(array_filter($items, static function (array $item): bool {
            $links = $item['volumeInfo']['imageLinks'] ?? [];
            return !empty($links['thumbnail']) || !empty($links['smallThumbnail']);
        }));

        // Step 2 — separate rated books from unrated ones, then sort each group.
        $rated   = [];
        $unrated = [];
        foreach ($items as $item) {
            if (!empty($item['volumeInfo']['ratingsCount'])) {
                $rated[] = $item;
            } else {
                $unrated[] = $item;
            }
        }

        // Rated: sort by score = ratingsCount × averageRating (desc)
        usort($rated, static function (array $a, array $b): int {
            $viA   = $a['volumeInfo'] ?? [];
            $viB   = $b['volumeInfo'] ?? [];
            $scoreA = (int)($viA['ratingsCount'] ?? 0) * (float)($viA['averageRating'] ?? 0);
            $scoreB = (int)($viB['ratingsCount'] ?? 0) * (float)($viB['averageRating'] ?? 0);
            return $scoreB <=> $scoreA;
        });

        // Unrated: sort by page count desc (longer = more likely a real book)
        usort($unrated, static function (array $a, array $b): int {
            return ($b['volumeInfo']['pageCount'] ?? 0) <=> ($a['volumeInfo']['pageCount'] ?? 0);
        });

        // Step 3 — rated books always come first; slice to requested limit
        $items = array_slice(array_merge($rated, $unrated), 0, $limit);

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
                'thumbnail'     => $links['thumbnail'] ?? ($links['smallThumbnail'] ?? null),
                'previewLink'   => $vi['previewLink'] ?? null,
                'infoLink'      => $vi['infoLink'] ?? null,
                'isbn10'        => $isbn10,
                'isbn13'        => $isbn13,
            ];
        }, $items);

        return $this->json([
            'page'       => $page,
            'limit'      => $limit,
            'totalItems' => $totalItems,
            'results'    => $results,
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
            'thumbnail'     => $links['thumbnail'] ?? ($links['smallThumbnail'] ?? null),
            'previewLink'   => $vi['previewLink'] ?? null,
            'infoLink'      => $vi['infoLink'] ?? null,
            'isbn10'        => $isbn10,
            'isbn13'        => $isbn13,
        ]);
    }

}
