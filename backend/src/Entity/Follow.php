<?php

namespace App\Entity;

use App\Repository\FollowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FollowRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_follow', columns: ['follower_id', 'following_id'])]
class Follow
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $follower;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $following;

    #[ORM\Column(length: 10, options: ['default' => 'accepted'])]
    private string $status = self::STATUS_ACCEPTED;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $follower, User $following, string $status = self::STATUS_ACCEPTED)
    {
        $this->follower  = $follower;
        $this->following = $following;
        $this->status    = $status;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getFollower(): User { return $this->follower; }
    public function getFollowing(): User { return $this->following; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isPending(): bool  { return $this->status === self::STATUS_PENDING; }
    public function isAccepted(): bool { return $this->status === self::STATUS_ACCEPTED; }

    public function accept(): void { $this->status = self::STATUS_ACCEPTED; }
}
