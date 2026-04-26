<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\PostComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostComment::class);
    }

    /** @return PostComment[] */
    public function findByPost(Post $post): array
    {
        return $this->findBy(['post' => $post], ['createdAt' => 'ASC']);
    }
}
