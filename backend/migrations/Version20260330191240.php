<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330191240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY `FK_B8EE3872A9B56E94`');
        $this->addSql('ALTER TABLE club CHANGE current_book_since current_book_since DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_b8ee3872a9b56e94 ON club');
        $this->addSql('CREATE INDEX IDX_B8EE3872BCAF3604 ON club (current_book_id)');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT `FK_B8EE3872A9B56E94` FOREIGN KEY (current_book_id) REFERENCES book (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD shelves_public TINYINT NOT NULL, ADD clubs_public TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE3872BCAF3604');
        $this->addSql('ALTER TABLE club CHANGE current_book_since current_book_since DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_b8ee3872bcaf3604 ON club');
        $this->addSql('CREATE INDEX IDX_B8EE3872A9B56E94 ON club (current_book_id)');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE3872BCAF3604 FOREIGN KEY (current_book_id) REFERENCES book (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user DROP shelves_public, DROP clubs_public');
    }
}
