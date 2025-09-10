<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250910201456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename IANA columns';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity ADD iana_registrar_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD iana_rdap_base_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD iana_status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD iana_updated DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD iana_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity DROP registrar_name_iana');
        $this->addSql('ALTER TABLE entity DROP rdap_base_url_iana');
        $this->addSql('ALTER TABLE entity DROP status_iana');
        $this->addSql('ALTER TABLE entity DROP updated_iana');
        $this->addSql('ALTER TABLE entity DROP date_iana');
        $this->addSql('COMMENT ON COLUMN entity.iana_updated IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN entity.iana_date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE entity ADD registrar_name_iana VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD rdap_base_url_iana VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD status_iana VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD updated_iana DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD date_iana DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity DROP iana_registrar_name');
        $this->addSql('ALTER TABLE entity DROP iana_rdap_base_url');
        $this->addSql('ALTER TABLE entity DROP iana_status');
        $this->addSql('ALTER TABLE entity DROP iana_updated');
        $this->addSql('ALTER TABLE entity DROP iana_date');
        $this->addSql('COMMENT ON COLUMN entity.updated_iana IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN entity.date_iana IS \'(DC2Type:date_immutable)\'');
    }
}
