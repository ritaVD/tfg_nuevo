<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /** @return Book[] */
    public function searchFallback(string $keyword, int $limit = 20): array
    {
        $like = '%' . $keyword . '%';
        return $this->createQueryBuilder('b')
            ->where('b.title LIKE :kw OR b.authors LIKE :kw')
            ->andWhere('b.externalId IS NOT NULL')
            ->setParameter('kw', $like)
            ->orderBy('b.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
