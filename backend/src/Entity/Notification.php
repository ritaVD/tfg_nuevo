<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification
{
    const TYPE_FOLLOW          = 'follow';           // alguien te sigue (cuenta pública)
    const TYPE_FOLLOW_REQUEST  = 'follow_request';   // alguien solicita seguirte (cuenta privada)
    const TYPE_FOLLOW_ACCEPTED = 'follow_accepted';  // aceptaron tu solicitud de follow
    const TYPE_LIKE            = 'like';
    const TYPE_COMMENT         = 'comment';
    const TYPE_CLUB_REQUEST    = 'club_request';     // alguien pide unirse a tu club (admin)
    const TYPE_CLUB_APPROVED   = 'club_approved';    // tu solicitud de club fue aceptada
    const TYPE_CLUB_REJECTED   = 'club_rejected';    // tu solicitud de club fue rechazada

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    /** Quien recibe la notificación */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    /** Quien la generó */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $actor;

    #[ORM\Column(length: 30)]
    private string $type;

    /** Para tipos like/comment */
    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Post $post = null;

    /** Para tipos club_* */
    #[ORM\ManyToOne(targetEntity: Club::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Club $club = null;

    /** ID auxiliar: Follow.id para follow_request, ClubJoinRequest.id para club_request */
    #[ORM\Column(nullable: true)]
    private ?int $refId = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        User $recipient,
        User $actor,
        string $type,
        ?Post $post = null,
        ?Club $club = null,
        ?int $refId = null,
    ) {
        $this->recipient = $recipient;
        $this->actor     = $actor;
        $this->type      = $type;
        $this->post      = $post;
        $this->club      = $club;
        $this->refId     = $refId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getRecipient(): User { return $this->recipient; }
    public function getActor(): User { return $this->actor; }
    public function getType(): string { return $this->type; }
    public function getPost(): ?Post { return $this->post; }
    public function getClub(): ?Club { return $this->club; }
    public function getRefId(): ?int { return $this->refId; }
    public function isRead(): bool { return $this->isRead; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function markRead(): void { $this->isRead = true; }
}
