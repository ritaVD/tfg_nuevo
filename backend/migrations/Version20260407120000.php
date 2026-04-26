<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table with follow and club notification types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            recipient_id INT NOT NULL,
            actor_id INT NOT NULL,
            post_id INT DEFAULT NULL,
            club_id INT DEFAULT NULL,
            type VARCHAR(30) NOT NULL,
            ref_id INT DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_notif_recipient (recipient_id),
            INDEX IDX_notif_actor (actor_id),
            INDEX IDX_notif_post (post_id),
            INDEX IDX_notif_club (club_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE notification
            ADD CONSTRAINT FK_notif_recipient FOREIGN KEY (recipient_id) REFERENCES `user` (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_notif_actor     FOREIGN KEY (actor_id)     REFERENCES `user` (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_notif_post      FOREIGN KEY (post_id)      REFERENCES post (id)   ON DELETE CASCADE,
            ADD CONSTRAINT FK_notif_club      FOREIGN KEY (club_id)      REFERENCES club (id)   ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_notif_recipient');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_notif_actor');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_notif_post');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_notif_club');
        $this->addSql('DROP TABLE notification');
    }
}
