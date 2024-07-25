<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240725140219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain ADD COLUMN deleted BOOLEAN DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__domain AS SELECT ldh_name, tld_id, handle, status, created_at, updated_at FROM domain');
        $this->addSql('DROP TABLE domain');
        $this->addSql('CREATE TABLE domain (ldh_name VARCHAR(255) NOT NULL, tld_id VARCHAR(63) NOT NULL, handle VARCHAR(255) DEFAULT NULL, status CLOB DEFAULT NULL --(DC2Type:simple_array)
        , created_at DATE NOT NULL --(DC2Type:date_immutable)
        , updated_at DATE NOT NULL --(DC2Type:date_immutable)
        , PRIMARY KEY(ldh_name), CONSTRAINT FK_A7A91E0B50F7084E FOREIGN KEY (tld_id) REFERENCES tld (tld) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO domain (ldh_name, tld_id, handle, status, created_at, updated_at) SELECT ldh_name, tld_id, handle, status, created_at, updated_at FROM __temp__domain');
        $this->addSql('DROP TABLE __temp__domain');
        $this->addSql('CREATE INDEX IDX_A7A91E0B50F7084E ON domain (tld_id)');
    }
}
