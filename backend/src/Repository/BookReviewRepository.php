<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\BookReview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookReview::class);
    }

    /** @return BookReview[] */
    public function findByBook(Book $book): array
    {
        return $this->findBy(['book' => $book], ['createdAt' => 'DESC']);
    }

    public function findOneByUserAndBook(User $user, Book $book): ?BookReview
    {
        return $this->findOneBy(['user' => $user, 'book' => $book]);
    }

    public function getStats(Book $book): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avg, COUNT(r.id) as cnt')
            ->andWhere('r.book = :book')
            ->setParameter('book', $book)
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => (int) $result['cnt'] > 0 ? round((float) $result['avg'], 1) : null,
            'count'   => (int) $result['cnt'],
        ];
    }
}
