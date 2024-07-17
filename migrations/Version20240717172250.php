<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717172250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain ADD COLUMN created_at DATE NOT NULL');
        $this->addSql('ALTER TABLE domain ADD COLUMN updated_at DATE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__domain AS SELECT ldh_name, handle, status FROM domain');
        $this->addSql('DROP TABLE domain');
        $this->addSql('CREATE TABLE domain (ldh_name VARCHAR(255) NOT NULL, handle VARCHAR(255) NOT NULL, status CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(ldh_name))');
        $this->addSql('INSERT INTO domain (ldh_name, handle, status) SELECT ldh_name, handle, status FROM __temp__domain');
        $this->addSql('DROP TABLE __temp__domain');
    }
}
