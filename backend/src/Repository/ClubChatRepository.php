<?php

namespace App\Repository;

use App\Entity\Club;
use App\Entity\ClubChat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubChat>
 */
class ClubChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubChat::class);
    }

    /**
     * Returns all chats for a club with createdBy eagerly loaded (avoids N+1).
     *
     * @return ClubChat[]
     */
    public function findByClubWithCreator(Club $club): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.createdBy', 'u')
            ->addSelect('u')
            ->where('c.club = :club')
            ->setParameter('club', $club)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
