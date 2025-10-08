<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251008094821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column on tld table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tld ADD deleted_at DATE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN tld.deleted_at IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tld DROP deleted_at');
    }
}
