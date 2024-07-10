<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240710215553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domain (ldhname VARCHAR(255) NOT NULL, handle VARCHAR(255) NOT NULL, status CLOB NOT NULL --(DC2Type:array)
        , whois_status VARCHAR(255) NOT NULL, PRIMARY KEY(ldhname))');
        $this->addSql('CREATE TABLE domain_entity (domain_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles VARCHAR(255) NOT NULL, PRIMARY KEY(domain_id, entity_id), CONSTRAINT FK_614B48A1115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldhname) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_614B48A181257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_614B48A1115F0EE5 ON domain_entity (domain_id)');
        $this->addSql('CREATE INDEX IDX_614B48A181257D5D ON domain_entity (entity_id)');
        $this->addSql('CREATE TABLE entity (handle VARCHAR(255) NOT NULL, PRIMARY KEY(handle))');
        $this->addSql('CREATE TABLE event ("action" VARCHAR(255) NOT NULL, domain_id VARCHAR(255) NOT NULL, date DATE NOT NULL --(DC2Type:date_immutable)
        , PRIMARY KEY("action", domain_id), CONSTRAINT FK_3BAE0AA7115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldhname) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7115F0EE5 ON event (domain_id)');
        $this->addSql('CREATE TABLE nameserver (handle VARCHAR(255) NOT NULL, ldhname VARCHAR(255) NOT NULL, status CLOB NOT NULL --(DC2Type:array)
        , PRIMARY KEY(handle))');
        $this->addSql('CREATE TABLE nameserver_entity (nameserver_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:array)
        , status CLOB NOT NULL --(DC2Type:array)
        , PRIMARY KEY(nameserver_id, entity_id), CONSTRAINT FK_A269AFB41A555619 FOREIGN KEY (nameserver_id) REFERENCES nameserver (handle) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A269AFB481257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A269AFB41A555619 ON nameserver_entity (nameserver_id)');
        $this->addSql('CREATE INDEX IDX_A269AFB481257D5D ON nameserver_entity (entity_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE domain');
        $this->addSql('DROP TABLE domain_entity');
        $this->addSql('DROP TABLE entity');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE nameserver');
        $this->addSql('DROP TABLE nameserver_entity');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
