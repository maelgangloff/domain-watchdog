<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251023000555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_e28446850f7084e918020d9');
        $this->addSql('ALTER TABLE entity ADD from_accredited_registrar_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD CONSTRAINT FK_E2844687CB19E6A FOREIGN KEY (from_accredited_registrar_id) REFERENCES icann_accreditation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E2844687CB19E6A ON entity (from_accredited_registrar_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E28446850F7084E918020D97CB19E6A ON entity (tld_id, handle, from_accredited_registrar_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity DROP CONSTRAINT FK_E2844687CB19E6A');
        $this->addSql('DROP INDEX IDX_E2844687CB19E6A');
        $this->addSql('DROP INDEX UNIQ_E28446850F7084E918020D97CB19E6A');
        $this->addSql('DELETE FROM domain_entity WHERE entity_uid IN (SELECT id FROM entity WHERE from_accredited_registrar_id IS NOT NULL)');
        $this->addSql('DELETE FROM entity_event WHERE entity_uid IN (SELECT id FROM entity WHERE from_accredited_registrar_id IS NOT NULL)');
        $this->addSql('DELETE FROM entity WHERE from_accredited_registrar_id IS NOT NULL');
        $this->addSql('ALTER TABLE entity DROP from_accredited_registrar_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_e28446850f7084e918020d9 ON entity (tld_id, handle)');
    }
}
