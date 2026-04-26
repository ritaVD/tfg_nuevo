<?php

namespace App\Entity;

use App\Repository\ShelfRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShelfRepository::class)]
class Shelf
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shelves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $orderIndex = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ShelfBook>
     */
    #[ORM\OneToMany(targetEntity: ShelfBook::class, mappedBy: 'shelf', orphanRemoval: true)]
    private Collection $shelfBooks;

    public function __construct()
    {
        $this->shelfBooks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): static
    {
        $this->orderIndex = $orderIndex;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ShelfBook>
     */
    public function getShelfBooks(): Collection
    {
        return $this->shelfBooks;
    }

    public function addShelfBook(ShelfBook $shelfBook): static
    {
        if (!$this->shelfBooks->contains($shelfBook)) {
            $this->shelfBooks->add($shelfBook);
            $shelfBook->setShelf($this);
        }

        return $this;
    }

    public function removeShelfBook(ShelfBook $shelfBook): static
    {
        if ($this->shelfBooks->removeElement($shelfBook)) {
            // set the owning side to null (unless already changed)
            if ($shelfBook->getShelf() === $this) {
                $shelfBook->setShelf(null);
            }
        }

        return $this;
    }
}
