<?php

namespace App\Entity;

use App\Repository\BookReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookReviewRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_book_review', columns: ['user_id', 'book_id'])]
class BookReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Book $book;

    #[ORM\Column]
    private int $rating;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Book $book, int $rating, ?string $content = null)
    {
        $this->user      = $user;
        $this->book      = $book;
        $this->rating    = $rating;
        $this->content   = $content;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getBook(): Book { return $this->book; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): void { $this->rating = $rating; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): void { $this->content = $content; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
