<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add currentBook and currentBookSince to club table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club ADD current_book_id INT DEFAULT NULL, ADD current_book_since DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE3872A9B56E94 FOREIGN KEY (current_book_id) REFERENCES book (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B8EE3872A9B56E94 ON club (current_book_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE3872A9B56E94');
        $this->addSql('DROP INDEX IDX_B8EE3872A9B56E94 ON club');
        $this->addSql('ALTER TABLE club DROP current_book_id, DROP current_book_since');
    }
}
