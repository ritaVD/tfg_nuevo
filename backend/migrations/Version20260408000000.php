<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260408000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create book_review table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE book_review (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            rating INT NOT NULL,
            content LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_book_review_user (user_id),
            INDEX IDX_book_review_book (book_id),
            UNIQUE INDEX unique_user_book_review (user_id, book_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE book_review
            ADD CONSTRAINT FK_book_review_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_book_review_book FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book_review DROP FOREIGN KEY FK_book_review_user');
        $this->addSql('ALTER TABLE book_review DROP FOREIGN KEY FK_book_review_book');
        $this->addSql('DROP TABLE book_review');
    }
}
