<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240712133612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity ADD COLUMN j_card CLOB NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entity AS SELECT handle FROM entity');
        $this->addSql('DROP TABLE entity');
        $this->addSql('CREATE TABLE entity (handle VARCHAR(255) NOT NULL, PRIMARY KEY(handle))');
        $this->addSql('INSERT INTO entity (handle) SELECT handle FROM __temp__entity');
        $this->addSql('DROP TABLE __temp__entity');
    }
}
