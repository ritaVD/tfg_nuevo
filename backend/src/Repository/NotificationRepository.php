<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return Notification[] Últimas 72 horas */
    public function findForUser(User $user, int $limit = 30): array
    {
        $since = new \DateTimeImmutable('-72 hours');

        return $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return Notification[] Historial completo sin límite temporal */
    public function findAllForUser(User $user, int $limit = 100): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Elimina notificaciones previas de follow del mismo actor (evita duplicados por follow/unfollow) */
    public function deleteFollowNotifications(User $recipient, User $actor): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.recipient = :recipient AND n.actor = :actor AND n.type IN (:types)')
            ->setParameter('recipient', $recipient)
            ->setParameter('actor', $actor)
            ->setParameter('types', [Notification::TYPE_FOLLOW, Notification::TYPE_FOLLOW_REQUEST])
            ->getQuery()
            ->execute();
    }

    /** Elimina notificaciones por tipo y refId (tras procesar solicitudes) */
    public function deleteByRefIdAndType(User $recipient, string $type, int $refId): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.recipient = :recipient AND n.type = :type AND n.refId = :refId')
            ->setParameter('recipient', $recipient)
            ->setParameter('type', $type)
            ->setParameter('refId', $refId)
            ->getQuery()
            ->execute();
    }

    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipient = :user AND n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllRead(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 'true')
            ->where('n.recipient = :user AND n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
