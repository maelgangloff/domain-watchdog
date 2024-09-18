<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240904155605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_entity ADD deleted BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE domain_entity DROP updated_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_entity ADD updated_at DATE NOT NULL');
        $this->addSql('ALTER TABLE domain_entity DROP deleted');
        $this->addSql('COMMENT ON COLUMN domain_entity.updated_at IS \'(DC2Type:date_immutable)\'');
    }
}
