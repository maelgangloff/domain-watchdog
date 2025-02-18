<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217234033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add id column on entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE entity_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE entity DROP CONSTRAINT entity_pkey CASCADE');
        $this->addSql('ALTER TABLE entity ADD id INT');
        $this->addSql('UPDATE entity SET id = nextval(\'entity_id_seq\') WHERE id IS NULL;');

        $this->addSql('ALTER TABLE entity ADD tld_id VARCHAR(63) DEFAULT NULL');

        $this->addSql('UPDATE entity e
            SET tld_id = (
                CASE 
                    WHEN e.handle ~ \'^[0-9]+$\' THEN NULL
                    ELSE (
                        SELECT d.tld_id
                        FROM domain_entity de
                        JOIN domain d ON de.domain_id = d.ldh_name
                        WHERE de.entity_id = e.handle
                        LIMIT 1
                    )
                END
            )
            WHERE EXISTS (
                SELECT 1 
                FROM domain_entity de 
                JOIN domain d ON de.domain_id = d.ldh_name
                WHERE de.entity_id = e.handle
            ) OR e.handle ~ \'^[0-9]+$\';
        ');

        $this->addSql('ALTER TABLE entity ADD CONSTRAINT FK_E28446850F7084E FOREIGN KEY (tld_id) REFERENCES tld (tld) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E28446850F7084E ON entity (tld_id)');
        $this->addSql('ALTER TABLE entity ADD PRIMARY KEY (id)');

        $this->addSql('DROP INDEX idx_614b48a181257d5d');
        $this->addSql('ALTER TABLE domain_entity DROP CONSTRAINT domain_entity_pkey');
        $this->addSql('ALTER TABLE domain_entity ADD entity_uid INT');

        $this->addSql('UPDATE domain_entity de
            SET entity_uid = (
                SELECT e.id
                FROM entity e
                WHERE e.handle = de.entity_id
            )
            WHERE de.entity_uid IS NULL;');

        $this->addSql('ALTER TABLE domain_entity DROP entity_id');
        $this->addSql('ALTER TABLE domain_entity ADD CONSTRAINT FK_614B48A12D1466A1 FOREIGN KEY (entity_uid) REFERENCES entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_614B48A12D1466A1 ON domain_entity (entity_uid)');
        $this->addSql('ALTER TABLE domain_entity ADD PRIMARY KEY (domain_id, entity_uid)');

        $this->addSql('DROP INDEX uniq_975a3f5e47cc8c92aa9e377a81257d5d');
        $this->addSql('DROP INDEX idx_975a3f5e81257d5d');
        $this->addSql('ALTER TABLE entity_event ADD entity_uid INT');

        $this->addSql('UPDATE entity_event ee
            SET entity_uid = (
                SELECT e.id
                FROM entity e
                WHERE e.handle = ee.entity_id
            )
            WHERE ee.entity_uid IS NULL;');

        $this->addSql('ALTER TABLE entity_event DROP entity_id');
        $this->addSql('ALTER TABLE entity_event ADD CONSTRAINT FK_975A3F5E2D1466A1 FOREIGN KEY (entity_uid) REFERENCES entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_975A3F5E2D1466A1 ON entity_event (entity_uid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_975A3F5E47CC8C92AA9E377A2D1466A1 ON entity_event (action, date, entity_uid)');

        $this->addSql('DROP INDEX idx_a269afb481257d5d');
        $this->addSql('ALTER TABLE nameserver_entity DROP CONSTRAINT nameserver_entity_pkey');
        $this->addSql('ALTER TABLE nameserver_entity ADD entity_uid INT');

        $this->addSql('UPDATE nameserver_entity ne
            SET entity_uid = (
                SELECT e.id
                FROM entity e
                WHERE e.handle = ne.entity_id
            )
            WHERE ne.entity_uid IS NULL;');

        $this->addSql('ALTER TABLE nameserver_entity DROP entity_id');
        $this->addSql('ALTER TABLE nameserver_entity ADD CONSTRAINT FK_A269AFB42D1466A1 FOREIGN KEY (entity_uid) REFERENCES entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A269AFB42D1466A1 ON nameserver_entity (entity_uid)');
        $this->addSql('ALTER TABLE nameserver_entity ADD PRIMARY KEY (nameserver_id, entity_uid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE entity_id_seq CASCADE');

        $this->addSql('ALTER TABLE entity DROP CONSTRAINT FK_E28446850F7084E');
        $this->addSql('DROP INDEX IDX_E28446850F7084E');

        $this->addSql('ALTER TABLE entity DROP id');
        $this->addSql('ALTER TABLE entity DROP tld_id');
        $this->addSql('ALTER TABLE entity ADD PRIMARY KEY (handle)');

        $this->addSql('ALTER TABLE nameserver_entity DROP CONSTRAINT FK_A269AFB42D1466A1');
        $this->addSql('DROP INDEX IDX_A269AFB42D1466A1');
        $this->addSql('DROP INDEX nameserver_entity_pkey');
        $this->addSql('ALTER TABLE nameserver_entity ADD entity_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE nameserver_entity DROP entity_uid');
        $this->addSql('ALTER TABLE nameserver_entity ADD CONSTRAINT fk_a269afb481257d5d FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_a269afb481257d5d ON nameserver_entity (entity_id)');
        $this->addSql('ALTER TABLE nameserver_entity ADD PRIMARY KEY (nameserver_id, entity_id)');

        $this->addSql('ALTER TABLE entity_event DROP CONSTRAINT FK_975A3F5E2D1466A1');
        $this->addSql('DROP INDEX IDX_975A3F5E2D1466A1');
        $this->addSql('DROP INDEX UNIQ_975A3F5E47CC8C92AA9E377A2D1466A1');
        $this->addSql('ALTER TABLE entity_event ADD entity_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE entity_event DROP entity_uid');
        $this->addSql('ALTER TABLE entity_event ADD CONSTRAINT fk_975a3f5e81257d5d FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_975a3f5e47cc8c92aa9e377a81257d5d ON entity_event (action, date, entity_id)');
        $this->addSql('CREATE INDEX idx_975a3f5e81257d5d ON entity_event (entity_id)');

        $this->addSql('ALTER TABLE domain_entity DROP CONSTRAINT FK_614B48A12D1466A1');
        $this->addSql('DROP INDEX IDX_614B48A12D1466A1');
        $this->addSql('DROP INDEX domain_entity_pkey');
        $this->addSql('ALTER TABLE domain_entity ADD entity_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE domain_entity DROP entity_uid');
        $this->addSql('ALTER TABLE domain_entity ADD CONSTRAINT fk_614b48a181257d5d FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_614b48a181257d5d ON domain_entity (entity_id)');
        $this->addSql('ALTER TABLE domain_entity ADD PRIMARY KEY (domain_id, entity_id)');
    }
}
