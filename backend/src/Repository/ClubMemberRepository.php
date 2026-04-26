<?php

namespace App\Repository;

use App\Entity\Club;
use App\Entity\ClubMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubMember>
 */
class ClubMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubMember::class);
    }

    /**
     * Returns all members of a club with user eagerly loaded (avoids N+1).
     *
     * @return ClubMember[]
     */
    public function findMembersWithUser(Club $club): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->addSelect('u')
            ->where('m.club = :club')
            ->setParameter('club', $club)
            ->orderBy('m.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a map of clubId => memberCount for a list of clubs.
     * Single query — avoids N+1 when listing clubs.
     *
     * @param  Club[] $clubs
     * @return array<int, int>
     */
    public function getMemberCountsForClubs(array $clubs): array
    {
        if (empty($clubs)) {
            return [];
        }

        $rows = $this->createQueryBuilder('m')
            ->select('IDENTITY(m.club) AS clubId, COUNT(m.id) AS cnt')
            ->where('m.club IN (:clubs)')
            ->setParameter('clubs', $clubs)
            ->groupBy('m.club')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['clubId']] = (int) $row['cnt'];
        }

        return $map;
    }

    /**
     * Returns a map of clubId => ClubMember for a given user and list of clubs.
     * Single query — avoids N+1 when listing clubs.
     *
     * @param  Club[] $clubs
     * @return array<int, ClubMember>
     */
    public function getMembershipsMapForUser(User $user, array $clubs): array
    {
        if (empty($clubs)) {
            return [];
        }

        $memberships = $this->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.club IN (:clubs)')
            ->setParameter('user', $user)
            ->setParameter('clubs', $clubs)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($memberships as $m) {
            $map[$m->getClub()->getId()] = $m;
        }

        return $map;
    }

    /**
     * COUNT query — avoids loading the full members collection just to count.
     */
    public function countByClub(Club $club): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.club = :club')
            ->setParameter('club', $club)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
