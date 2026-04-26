<?php

namespace App\Repository;

use App\Entity\Follow;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FollowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follow::class);
    }

    /** Any follow row (any status) between two users */
    public function findFollow(User $follower, User $following): ?Follow
    {
        return $this->findOneBy(['follower' => $follower, 'following' => $following]);
    }

    public function countFollowers(User $user): int
    {
        return $this->count(['following' => $user, 'status' => Follow::STATUS_ACCEPTED]);
    }

    public function countFollowing(User $user): int
    {
        return $this->count(['follower' => $user, 'status' => Follow::STATUS_ACCEPTED]);
    }

    /** @return Follow[] */
    public function findFollowers(User $user): array
    {
        return $this->findBy(
            ['following' => $user, 'status' => Follow::STATUS_ACCEPTED],
            ['createdAt' => 'DESC']
        );
    }

    /** @return Follow[] */
    public function findFollowing(User $user): array
    {
        return $this->findBy(
            ['follower' => $user, 'status' => Follow::STATUS_ACCEPTED],
            ['createdAt' => 'DESC']
        );
    }

    /** Incoming pending follow requests for a private account */
    public function findIncomingRequests(User $user): array
    {
        return $this->findBy(
            ['following' => $user, 'status' => Follow::STATUS_PENDING],
            ['createdAt' => 'DESC']
        );
    }

    public function countIncomingRequests(User $user): int
    {
        return $this->count(['following' => $user, 'status' => Follow::STATUS_PENDING]);
    }
}
