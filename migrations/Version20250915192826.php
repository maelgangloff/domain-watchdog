<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915192826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move ICANN accreditation to table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE icann_accreditation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE icann_accreditation (id INT NOT NULL, registrar_name VARCHAR(255) DEFAULT NULL, rdap_base_url VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, updated DATE DEFAULT NULL, date DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN icann_accreditation.updated IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN icann_accreditation.date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE entity ADD icann_accreditation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entity DROP icann_registrar_name');
        $this->addSql('ALTER TABLE entity DROP icann_rdap_base_url');
        $this->addSql('ALTER TABLE entity DROP icann_status');
        $this->addSql('ALTER TABLE entity DROP icann_updated');
        $this->addSql('ALTER TABLE entity DROP icann_date');

        $this->addSql('DELETE FROM domain_entity de USING entity e WHERE de.entity_uid = e.id AND e.tld_id IS NULL');
        $this->addSql('DELETE FROM entity_event ee USING entity e WHERE ee.entity_uid = e.id AND e.tld_id IS NULL');
        $this->addSql('DELETE FROM nameserver_entity ne USING entity e WHERE ne.entity_uid = e.id AND e.tld_id IS NULL');
        $this->addSql('DELETE FROM entity e WHERE e.tld_id IS NULL;');

        $this->addSql('ALTER TABLE entity ALTER tld_id SET NOT NULL');
        $this->addSql('ALTER TABLE entity ADD CONSTRAINT FK_E284468D77C9FEB FOREIGN KEY (icann_accreditation_id) REFERENCES icann_accreditation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E284468D77C9FEB ON entity (icann_accreditation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity DROP CONSTRAINT FK_E284468D77C9FEB');
        $this->addSql('DROP SEQUENCE icann_accreditation_id_seq CASCADE');
        $this->addSql('DROP TABLE icann_accreditation');
        $this->addSql('DROP INDEX IDX_E284468D77C9FEB');
        $this->addSql('ALTER TABLE entity ADD icann_registrar_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD icann_rdap_base_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD icann_status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD icann_updated DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD icann_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity DROP icann_accreditation_id');
        $this->addSql('ALTER TABLE entity ALTER tld_id DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN entity.icann_updated IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN entity.icann_date IS \'(DC2Type:date_immutable)\'');
    }
}
