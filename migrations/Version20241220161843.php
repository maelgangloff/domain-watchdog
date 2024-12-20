<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241220161843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity ALTER COLUMN j_card TYPE JSONB USING j_card::JSONB;');
        $this->addSql('ALTER TABLE connector ALTER COLUMN auth_data TYPE JSONB USING auth_data::JSONB;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity ALTER COLUMN j_card TYPE JSON USING j_card::JSON;');
        $this->addSql('ALTER TABLE connector ALTER COLUMN auth_data TYPE JSON USING auth_data::JSON;');
    }
}
