<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make display_name NOT NULL and unique; fill missing values with email prefix';
    }

    public function up(Schema $schema): void
    {
        // Fill any NULL display_names with a unique fallback derived from the email
        $this->addSql("UPDATE user SET display_name = CONCAT('user_', id) WHERE display_name IS NULL OR display_name = ''");

        // Make the column NOT NULL and add a unique index
        $this->addSql('ALTER TABLE user CHANGE display_name display_name VARCHAR(80) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DISPLAY_NAME ON user (display_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DISPLAY_NAME ON user');
        $this->addSql('ALTER TABLE user CHANGE display_name display_name VARCHAR(80) DEFAULT NULL');
    }
}
