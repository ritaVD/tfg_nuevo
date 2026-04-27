<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create post, post_like and post_comment tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE post (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_post_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE post_like (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_post_like_post (post_id),
            INDEX IDX_post_like_user (user_id),
            UNIQUE INDEX uq_post_like (post_id, user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE post_comment (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_post_comment_post (post_id),
            INDEX IDX_post_comment_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE post
            ADD CONSTRAINT FK_post_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE post_like
            ADD CONSTRAINT FK_post_like_post FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_post_like_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE post_comment
            ADD CONSTRAINT FK_post_comment_post FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_post_comment_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_like DROP FOREIGN KEY FK_post_like_post');
        $this->addSql('ALTER TABLE post_like DROP FOREIGN KEY FK_post_like_user');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_post_comment_post');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_post_comment_user');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_post_user');
        $this->addSql('DROP TABLE post_comment');
        $this->addSql('DROP TABLE post_like');
        $this->addSql('DROP TABLE post');
    }
}
