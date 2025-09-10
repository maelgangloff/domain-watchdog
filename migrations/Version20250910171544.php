<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250910171544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns for IANA fields in the entity table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity ADD registrar_name_iana VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD rdap_base_url_iana VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD status_iana VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD updated_iana DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE entity ADD date_iana DATE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN entity.updated_iana IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN entity.date_iana IS \'(DC2Type:date_immutable)\'');

        $this->addSql("DELETE FROM domain_entity de USING entity e WHERE de.entity_uid = e.id AND e.handle ~ '^[0-9]+$'");
        $this->addSql("DELETE FROM entity_event ev USING entity e WHERE ev.entity_uid = e.id AND e.handle ~ '^[0-9]+$'");
        $this->addSql("DELETE FROM nameserver_entity ne USING entity e WHERE ne.entity_uid = e.id AND e.handle ~ '^[0-9]+$'");
        $this->addSql("DELETE FROM entity WHERE handle ~ '^[0-9]+$'");


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE entity DROP registrar_name_iana');
        $this->addSql('ALTER TABLE entity DROP rdap_base_url_iana');
        $this->addSql('ALTER TABLE entity DROP status_iana');
        $this->addSql('ALTER TABLE entity DROP updated_iana');
        $this->addSql('ALTER TABLE entity DROP date_iana');
    }
}
