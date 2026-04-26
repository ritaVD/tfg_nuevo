<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reading_progress table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reading_progress (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            mode VARCHAR(10) NOT NULL DEFAULT \'percent\',
            current_page INT DEFAULT NULL,
            total_pages INT DEFAULT NULL,
            percent INT DEFAULT NULL,
            started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_reading_user (user_id),
            INDEX IDX_reading_book (book_id),
            UNIQUE INDEX unique_user_book_progress (user_id, book_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE reading_progress
            ADD CONSTRAINT FK_reading_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_reading_book FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reading_progress DROP FOREIGN KEY FK_reading_user');
        $this->addSql('ALTER TABLE reading_progress DROP FOREIGN KEY FK_reading_book');
        $this->addSql('DROP TABLE reading_progress');
    }
}
