<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table for database-backed session storage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS sessions (
                sess_id VARBINARY(128) NOT NULL PRIMARY KEY,
                sess_data BLOB NOT NULL,
                sess_lifetime INT UNSIGNED NOT NULL,
                sess_time INT UNSIGNED NOT NULL
            ) COLLATE utf8mb4_bin ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sessions');
    }
}
