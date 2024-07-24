<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240724160636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tld ADD COLUMN type VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__tld AS SELECT tld, contract_terminated, date_of_contract_signature, delegation_date, registry_operator, removal_date, specification13 FROM tld');
        $this->addSql('DROP TABLE tld');
        $this->addSql('CREATE TABLE tld (tld VARCHAR(63) NOT NULL, contract_terminated BOOLEAN DEFAULT NULL, date_of_contract_signature DATE DEFAULT NULL --(DC2Type:date_immutable)
        , delegation_date DATE DEFAULT NULL --(DC2Type:date_immutable)
        , registry_operator VARCHAR(255) DEFAULT NULL, removal_date DATE DEFAULT NULL --(DC2Type:date_immutable)
        , specification13 BOOLEAN DEFAULT NULL, PRIMARY KEY(tld))');
        $this->addSql('INSERT INTO tld (tld, contract_terminated, date_of_contract_signature, delegation_date, registry_operator, removal_date, specification13) SELECT tld, contract_terminated, date_of_contract_signature, delegation_date, registry_operator, removal_date, specification13 FROM __temp__tld');
        $this->addSql('DROP TABLE __temp__tld');
    }
}
