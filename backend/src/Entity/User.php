<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_DISPLAY_NAME', fields: ['displayName'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['displayName'], message: 'Este nombre de usuario ya está en uso')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Shelf>
     */
    #[ORM\OneToMany(targetEntity: Shelf::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $shelves;

    /**
     * @var Collection<int, Club>
     */
    #[ORM\OneToMany(targetEntity: Club::class, mappedBy: 'owner')]
    private Collection $clubs;

    /**
     * @var Collection<int, ClubMember>
     */
    #[ORM\OneToMany(targetEntity: ClubMember::class, mappedBy: 'user')]
    private Collection $clubMemberships;

    /**
     * @var Collection<int, ClubJoinRequest>
     */
    #[ORM\OneToMany(targetEntity: ClubJoinRequest::class, mappedBy: 'user')]
    private Collection $clubJoinRequests;

    /**
     * @var Collection<int, ClubJoinRequest>
     */
    #[ORM\OneToMany(targetEntity: ClubJoinRequest::class, mappedBy: 'resolvedBy')]
    private Collection $resolvedJoinRequests;

    /**
     * @var Collection<int, ClubChat>
     */
    #[ORM\OneToMany(targetEntity: ClubChat::class, mappedBy: 'createdBy')]
    private Collection $createdChats;

    /**
     * @var Collection<int, ClubChatMessage>
     */
    #[ORM\OneToMany(targetEntity: ClubChatMessage::class, mappedBy: 'user')]
    private Collection $chatMessages;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 80, nullable: false, unique: true)]
    private ?string $displayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column]
    private bool $shelvesPublic = true;

    #[ORM\Column]
    private bool $clubsPublic = true;

    #[ORM\Column]
    private bool $isPrivate = false;

    #[ORM\Column]
    private bool $isBanned = false;

    public function __construct()
    {
        $this->shelves = new ArrayCollection();
        $this->clubs = new ArrayCollection();
        $this->clubMemberships = new ArrayCollection();
        $this->clubJoinRequests = new ArrayCollection();
        $this->resolvedJoinRequests = new ArrayCollection();
        $this->createdChats = new ArrayCollection();
        $this->chatMessages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Shelf>
     */
    public function getShelves(): Collection
    {
        return $this->shelves;
    }

    public function addShelf(Shelf $shelf): static
    {
        if (!$this->shelves->contains($shelf)) {
            $this->shelves->add($shelf);
            $shelf->setUser($this);
        }

        return $this;
    }

    public function removeShelf(Shelf $shelf): static
    {
        if ($this->shelves->removeElement($shelf)) {
            // set the owning side to null (unless already changed)
            if ($shelf->getUser() === $this) {
                $shelf->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Club>
     */
    public function getClubs(): Collection
    {
        return $this->clubs;
    }

    public function addClub(Club $club): static
    {
        if (!$this->clubs->contains($club)) {
            $this->clubs->add($club);
            $club->setOwner($this);
        }

        return $this;
    }

    public function removeClub(Club $club): static
    {
        if ($this->clubs->removeElement($club)) {
            // set the owning side to null (unless already changed)
            if ($club->getOwner() === $this) {
                $club->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubMember>
     */
    public function getClubMemberships(): Collection
    {
        return $this->clubMemberships;
    }

    public function addClubMembership(ClubMember $clubMembership): static
    {
        if (!$this->clubMemberships->contains($clubMembership)) {
            $this->clubMemberships->add($clubMembership);
            $clubMembership->setUser($this);
        }

        return $this;
    }

    public function removeClubMembership(ClubMember $clubMembership): static
    {
        if ($this->clubMemberships->removeElement($clubMembership)) {
            // set the owning side to null (unless already changed)
            if ($clubMembership->getUser() === $this) {
                $clubMembership->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubJoinRequest>
     */
    public function getClubJoinRequests(): Collection
    {
        return $this->clubJoinRequests;
    }

    public function addClubJoinRequest(ClubJoinRequest $clubJoinRequest): static
    {
        if (!$this->clubJoinRequests->contains($clubJoinRequest)) {
            $this->clubJoinRequests->add($clubJoinRequest);
            $clubJoinRequest->setUser($this);
        }

        return $this;
    }

    public function removeClubJoinRequest(ClubJoinRequest $clubJoinRequest): static
    {
        if ($this->clubJoinRequests->removeElement($clubJoinRequest)) {
            // set the owning side to null (unless already changed)
            if ($clubJoinRequest->getUser() === $this) {
                $clubJoinRequest->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubJoinRequest>
     */
    public function getResolvedJoinRequests(): Collection
    {
        return $this->resolvedJoinRequests;
    }

    public function addResolvedJoinRequest(ClubJoinRequest $resolvedJoinRequest): static
    {
        if (!$this->resolvedJoinRequests->contains($resolvedJoinRequest)) {
            $this->resolvedJoinRequests->add($resolvedJoinRequest);
            $resolvedJoinRequest->setResolvedBy($this);
        }

        return $this;
    }

    public function removeResolvedJoinRequest(ClubJoinRequest $resolvedJoinRequest): static
    {
        if ($this->resolvedJoinRequests->removeElement($resolvedJoinRequest)) {
            // set the owning side to null (unless already changed)
            if ($resolvedJoinRequest->getResolvedBy() === $this) {
                $resolvedJoinRequest->setResolvedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubChat>
     */
    public function getCreatedChats(): Collection
    {
        return $this->createdChats;
    }

    public function addCreatedChat(ClubChat $createdChat): static
    {
        if (!$this->createdChats->contains($createdChat)) {
            $this->createdChats->add($createdChat);
            $createdChat->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedChat(ClubChat $createdChat): static
    {
        if ($this->createdChats->removeElement($createdChat)) {
            // set the owning side to null (unless already changed)
            if ($createdChat->getCreatedBy() === $this) {
                $createdChat->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ClubChatMessage>
     */
    public function getChatMessages(): Collection
    {
        return $this->chatMessages;
    }

    public function addChatMessage(ClubChatMessage $chatMessage): static
    {
        if (!$this->chatMessages->contains($chatMessage)) {
            $this->chatMessages->add($chatMessage);
            $chatMessage->setUser($this);
        }

        return $this;
    }

    public function removeChatMessage(ClubChatMessage $chatMessage): static
    {
        if ($this->chatMessages->removeElement($chatMessage)) {
            // set the owning side to null (unless already changed)
            if ($chatMessage->getUser() === $this) {
                $chatMessage->setUser(null);
            }
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function isShelvesPublic(): bool
    {
        return $this->shelvesPublic;
    }

    public function setShelvesPublic(bool $shelvesPublic): static
    {
        $this->shelvesPublic = $shelvesPublic;

        return $this;
    }

    public function isClubsPublic(): bool
    {
        return $this->clubsPublic;
    }

    public function setClubsPublic(bool $clubsPublic): static
    {
        $this->clubsPublic = $clubsPublic;

        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function setIsBanned(bool $isBanned): static
    {
        $this->isBanned = $isBanned;

        return $this;
    }
}
