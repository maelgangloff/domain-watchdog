<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240719164643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tld ADD COLUMN contract_terminated BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE tld ADD COLUMN date_of_contract_signature DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE tld ADD COLUMN delegation_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE tld ADD COLUMN registry_operator VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tld ADD COLUMN removal_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE tld ADD COLUMN specification13 BOOLEAN DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__tld AS SELECT tld FROM tld');
        $this->addSql('DROP TABLE tld');
        $this->addSql('CREATE TABLE tld (tld VARCHAR(63) NOT NULL, PRIMARY KEY(tld))');
        $this->addSql('INSERT INTO tld (tld) SELECT tld FROM __temp__tld');
        $this->addSql('DROP TABLE __temp__tld');
    }
}
