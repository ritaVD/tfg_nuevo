<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure is_banned column exists on user table (idempotent, MySQL-compatible)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('user')->hasColumn('is_banned')) {
            $this->addSql('ALTER TABLE `user` ADD is_banned TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('user')->hasColumn('is_banned')) {
            $this->addSql('ALTER TABLE `user` DROP COLUMN is_banned');
        }
    }
}
