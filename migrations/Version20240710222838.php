<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240710222838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__domain_entity AS SELECT domain_id, entity_id, roles FROM domain_entity');
        $this->addSql('DROP TABLE domain_entity');
        $this->addSql('CREATE TABLE domain_entity (domain_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:array)
        , PRIMARY KEY(domain_id, entity_id), CONSTRAINT FK_614B48A1115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldhname) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_614B48A181257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO domain_entity (domain_id, entity_id, roles) SELECT domain_id, entity_id, roles FROM __temp__domain_entity');
        $this->addSql('DROP TABLE __temp__domain_entity');
        $this->addSql('CREATE INDEX IDX_614B48A181257D5D ON domain_entity (entity_id)');
        $this->addSql('CREATE INDEX IDX_614B48A1115F0EE5 ON domain_entity (domain_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__domain_entity AS SELECT domain_id, entity_id, roles FROM domain_entity');
        $this->addSql('DROP TABLE domain_entity');
        $this->addSql('CREATE TABLE domain_entity (domain_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles VARCHAR(255) NOT NULL, PRIMARY KEY(domain_id, entity_id), CONSTRAINT FK_614B48A1115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldhname) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_614B48A181257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO domain_entity (domain_id, entity_id, roles) SELECT domain_id, entity_id, roles FROM __temp__domain_entity');
        $this->addSql('DROP TABLE __temp__domain_entity');
        $this->addSql('CREATE INDEX IDX_614B48A1115F0EE5 ON domain_entity (domain_id)');
        $this->addSql('CREATE INDEX IDX_614B48A181257D5D ON domain_entity (entity_id)');
    }
}
