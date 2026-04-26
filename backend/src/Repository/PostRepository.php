<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /** Posts de un usuario concreto, más recientes primero */
    public function findByUser(User $user, int $limit = 30): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Feed: posts propios + posts de usuarios seguidos (aceptados).
     * Ordenado por fecha descendente.
     */
    public function findFeed(User $me, int $limit = 40): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('App\Entity\Follow', 'f', 'WITH', 'f.follower = :me AND f.following = p.user AND f.status = :accepted')
            ->andWhere('p.user = :me OR f.id IS NOT NULL')
            ->setParameter('me', $me)
            ->setParameter('accepted', 'accepted')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
