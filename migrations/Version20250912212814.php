<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912212814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename IANA columns to ICANN';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entity RENAME COLUMN iana_registrar_name TO icann_registrar_name');
        $this->addSql('ALTER TABLE entity RENAME COLUMN iana_rdap_base_url TO icann_rdap_base_url');
        $this->addSql('ALTER TABLE entity RENAME COLUMN iana_status TO icann_status');
        $this->addSql('ALTER TABLE entity RENAME COLUMN iana_updated TO icann_updated');
        $this->addSql('ALTER TABLE entity RENAME COLUMN iana_date TO icann_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entity RENAME COLUMN icann_registrar_name TO iana_registrar_name');
        $this->addSql('ALTER TABLE entity RENAME COLUMN icann_rdap_base_url TO iana_rdap_base_url');
        $this->addSql('ALTER TABLE entity RENAME COLUMN icann_status TO iana_status');
        $this->addSql('ALTER TABLE entity RENAME COLUMN icann_updated TO iana_updated');
        $this->addSql('ALTER TABLE entity RENAME COLUMN icann_date TO iana_date');
    }
}
