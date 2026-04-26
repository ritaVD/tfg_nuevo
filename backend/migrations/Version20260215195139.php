<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215195139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, external_source VARCHAR(255) DEFAULT NULL, external_id VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, authors JSON DEFAULT NULL, isbn10 VARCHAR(255) DEFAULT NULL, isbn13 VARCHAR(255) DEFAULT NULL, cover_url LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, published_date VARCHAR(255) DEFAULT NULL, language VARCHAR(255) DEFAULT NULL, page_count INT DEFAULT NULL, categories JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE club (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, visibility VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_B8EE38727E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE club_chat (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, is_open TINYINT NOT NULL, created_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, club_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_155384A61190A32 (club_id), INDEX IDX_155384AB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE club_chat_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, chat_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_800754E61A9A7125 (chat_id), INDEX IDX_800754E6A76ED395 (user_id), INDEX idx_chat_created_at (chat_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE club_join_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, requested_at DATETIME NOT NULL, resolved_at DATETIME DEFAULT NULL, club_id INT NOT NULL, user_id INT NOT NULL, resolved_by_id INT DEFAULT NULL, INDEX IDX_93864C0F61190A32 (club_id), INDEX IDX_93864C0FA76ED395 (user_id), INDEX IDX_93864C0F6713A32B (resolved_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE club_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(10) NOT NULL, joined_at DATETIME NOT NULL, club_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_552B46F261190A32 (club_id), INDEX IDX_552B46F2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE shelf (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, order_index INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_A5475BE3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE shelf_book (id INT AUTO_INCREMENT NOT NULL, order_index INT NOT NULL, status VARCHAR(20) DEFAULT NULL, added_at DATETIME NOT NULL, shelf_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_431D356F7C12FBC0 (shelf_id), INDEX IDX_431D356F16A2B381 (book_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE38727E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE club_chat ADD CONSTRAINT FK_155384A61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_chat ADD CONSTRAINT FK_155384AB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE club_chat_message ADD CONSTRAINT FK_800754E61A9A7125 FOREIGN KEY (chat_id) REFERENCES club_chat (id)');
        $this->addSql('ALTER TABLE club_chat_message ADD CONSTRAINT FK_800754E6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE club_join_request ADD CONSTRAINT FK_93864C0F61190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_join_request ADD CONSTRAINT FK_93864C0FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE club_join_request ADD CONSTRAINT FK_93864C0F6713A32B FOREIGN KEY (resolved_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE club_member ADD CONSTRAINT FK_552B46F261190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_member ADD CONSTRAINT FK_552B46F2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE shelf ADD CONSTRAINT FK_A5475BE3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE shelf_book ADD CONSTRAINT FK_431D356F7C12FBC0 FOREIGN KEY (shelf_id) REFERENCES shelf (id)');
        $this->addSql('ALTER TABLE shelf_book ADD CONSTRAINT FK_431D356F16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club DROP FOREIGN KEY FK_B8EE38727E3C61F9');
        $this->addSql('ALTER TABLE club_chat DROP FOREIGN KEY FK_155384A61190A32');
        $this->addSql('ALTER TABLE club_chat DROP FOREIGN KEY FK_155384AB03A8386');
        $this->addSql('ALTER TABLE club_chat_message DROP FOREIGN KEY FK_800754E61A9A7125');
        $this->addSql('ALTER TABLE club_chat_message DROP FOREIGN KEY FK_800754E6A76ED395');
        $this->addSql('ALTER TABLE club_join_request DROP FOREIGN KEY FK_93864C0F61190A32');
        $this->addSql('ALTER TABLE club_join_request DROP FOREIGN KEY FK_93864C0FA76ED395');
        $this->addSql('ALTER TABLE club_join_request DROP FOREIGN KEY FK_93864C0F6713A32B');
        $this->addSql('ALTER TABLE club_member DROP FOREIGN KEY FK_552B46F261190A32');
        $this->addSql('ALTER TABLE club_member DROP FOREIGN KEY FK_552B46F2A76ED395');
        $this->addSql('ALTER TABLE shelf DROP FOREIGN KEY FK_A5475BE3A76ED395');
        $this->addSql('ALTER TABLE shelf_book DROP FOREIGN KEY FK_431D356F7C12FBC0');
        $this->addSql('ALTER TABLE shelf_book DROP FOREIGN KEY FK_431D356F16A2B381');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE club');
        $this->addSql('DROP TABLE club_chat');
        $this->addSql('DROP TABLE club_chat_message');
        $this->addSql('DROP TABLE club_join_request');
        $this->addSql('DROP TABLE club_member');
        $this->addSql('DROP TABLE shelf');
        $this->addSql('DROP TABLE shelf_book');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
