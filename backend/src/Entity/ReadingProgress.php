<?php

namespace App\Entity;

use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReadingProgressRepository::class)]
#[ORM\Table(
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'unique_user_book_progress', columns: ['user_id', 'book_id'])
    ]
)]
class ReadingProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Book $book = null;

    /** 'pages' | 'percent' */
    #[ORM\Column(length: 10)]
    private string $mode = 'percent';

    /** Current page (used when mode = 'pages') */
    #[ORM\Column(nullable: true)]
    private ?int $currentPage = null;

    /** Total pages override (user can set when book has no pageCount) */
    #[ORM\Column(nullable: true)]
    private ?int $totalPages = null;

    /** Percentage 0-100 (used when mode = 'percent') */
    #[ORM\Column(nullable: true)]
    private ?int $percent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getBook(): ?Book { return $this->book; }
    public function setBook(?Book $book): static { $this->book = $book; return $this; }

    public function getMode(): string { return $this->mode; }
    public function setMode(string $mode): static { $this->mode = $mode; return $this; }

    public function getCurrentPage(): ?int { return $this->currentPage; }
    public function setCurrentPage(?int $p): static { $this->currentPage = $p; return $this; }

    public function getTotalPages(): ?int { return $this->totalPages; }
    public function setTotalPages(?int $p): static { $this->totalPages = $p; return $this; }

    public function getPercent(): ?int { return $this->percent; }
    public function setPercent(?int $p): static { $this->percent = $p; return $this; }

    public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
    public function setStartedAt(\DateTimeImmutable $d): static { $this->startedAt = $d; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $d): static { $this->updatedAt = $d; return $this; }

    public function getComputedPercent(): int
    {
        if ($this->mode === 'percent') {
            return $this->percent ?? 0;
        }
        $total = $this->totalPages ?? $this->book?->getPageCount();
        if (!$total || $total <= 0) return 0;
        return (int) round(($this->currentPage ?? 0) / $total * 100);
    }
}
