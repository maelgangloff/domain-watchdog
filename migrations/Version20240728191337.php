<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240728191337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE connector (provider VARCHAR(255) NOT NULL, user_id INT NOT NULL, auth_data JSON NOT NULL, PRIMARY KEY(provider, user_id))');
        $this->addSql('CREATE INDEX IDX_148C456EA76ED395 ON connector (user_id)');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE connector DROP CONSTRAINT FK_148C456EA76ED395');
        $this->addSql('DROP TABLE connector');
    }
}
