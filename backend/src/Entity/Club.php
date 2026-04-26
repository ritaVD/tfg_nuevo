<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Book;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'clubs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    private ?string $visibility = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, ClubMember>
     */
    #[ORM\OneToMany(targetEntity: ClubMember::class, mappedBy: 'club', orphanRemoval: true)]
    private Collection $members;

    /**
     * @var Collection<int, ClubJoinRequest>
     */
    #[ORM\OneToMany(targetEntity: ClubJoinRequest::class, mappedBy: 'club', orphanRemoval: true)]
    private Collection $joinRequests;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Book $currentBook = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentBookSince = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentBookUntil = null;

    /**
     * @var Collection<int, ClubChat>
     */
    #[ORM\OneToMany(targetEntity: ClubChat::class, mappedBy: 'club', orphanRemoval: true)]
    private Collection $chats;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->joinRequests = new ArrayCollection();
        $this->chats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

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

    public function getCurrentBook(): ?Book
    {
        return $this->currentBook;
    }

    public function setCurrentBook(?Book $currentBook): static
    {
        $this->currentBook = $currentBook;

        return $this;
    }

    public function getCurrentBookSince(): ?\DateTimeImmutable
    {
        return $this->currentBookSince;
    }

    public function setCurrentBookSince(?\DateTimeImmutable $currentBookSince): static
    {
        $this->currentBookSince = $currentBookSince;

        return $this;
    }

    public function getCurrentBookUntil(): ?\DateTimeImmutable
    {
        return $this->currentBookUntil;
    }

    public function setCurrentBookUntil(?\DateTimeImmutable $currentBookUntil): static
    {
        $this->currentBookUntil = $currentBookUntil;

        return $this;
    }

    /**
     * @return Collection<int, ClubMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ClubMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setClub($this);
        }

        return $this;
    }

    public function removeMember(ClubMember $member): static
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getClub() === $this) {
                $member->setClub(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubJoinRequest>
     */
    public function getJoinRequests(): Collection
    {
        return $this->joinRequests;
    }

    public function addJoinRequest(ClubJoinRequest $joinRequest): static
    {
        if (!$this->joinRequests->contains($joinRequest)) {
            $this->joinRequests->add($joinRequest);
            $joinRequest->setClub($this);
        }

        return $this;
    }

    public function removeJoinRequest(ClubJoinRequest $joinRequest): static
    {
        if ($this->joinRequests->removeElement($joinRequest)) {
            // set the owning side to null (unless already changed)
            if ($joinRequest->getClub() === $this) {
                $joinRequest->setClub(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubChat>
     */
    public function getChats(): Collection
    {
        return $this->chats;
    }

    public function addChat(ClubChat $chat): static
    {
        if (!$this->chats->contains($chat)) {
            $this->chats->add($chat);
            $chat->setClub($this);
        }

        return $this;
    }

    public function removeChat(ClubChat $chat): static
    {
        if ($this->chats->removeElement($chat)) {
            // set the owning side to null (unless already changed)
            if ($chat->getClub() === $this) {
                $chat->setClub(null);
            }
        }

        return $this;
    }
}
