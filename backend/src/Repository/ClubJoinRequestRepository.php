<?php

namespace App\Repository;

use App\Entity\Club;
use App\Entity\ClubJoinRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubJoinRequest>
 */
class ClubJoinRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubJoinRequest::class);
    }

    /**
     * Returns all pending requests for a club with user eagerly loaded (avoids N+1).
     *
     * @return ClubJoinRequest[]
     */
    public function findPendingWithUser(Club $club): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->where('r.club = :club')
            ->andWhere('r.status = :status')
            ->setParameter('club', $club)
            ->setParameter('status', 'pending')
            ->orderBy('r.requestedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
