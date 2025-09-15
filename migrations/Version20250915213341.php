<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915213341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'deleted_at on domain_entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_entity ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE domain_entity SET deleted_at = NOW() WHERE deleted IS TRUE');
        $this->addSql('ALTER TABLE domain_entity DROP deleted');
        $this->addSql('COMMENT ON COLUMN domain_entity.deleted_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_entity ADD deleted BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE domain_entity DROP deleted_at');
    }
}
