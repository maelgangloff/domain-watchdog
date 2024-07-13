<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240713135242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domain (ldh_name VARCHAR(255) NOT NULL, handle VARCHAR(255) NOT NULL, status CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(ldh_name))');
        $this->addSql('CREATE TABLE domain_nameservers (domain_ldh_name VARCHAR(255) NOT NULL, nameserver_ldh_name VARCHAR(255) NOT NULL, PRIMARY KEY(domain_ldh_name, nameserver_ldh_name), CONSTRAINT FK_B6E6B63AAF923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6E6B63AA6496BFE FOREIGN KEY (nameserver_ldh_name) REFERENCES nameserver (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B6E6B63AAF923913 ON domain_nameservers (domain_ldh_name)');
        $this->addSql('CREATE INDEX IDX_B6E6B63AA6496BFE ON domain_nameservers (nameserver_ldh_name)');
        $this->addSql('CREATE TABLE domain_entity (domain_id VARCHAR(255) NOT NULL, entity_id INTEGER NOT NULL, roles CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(domain_id, entity_id), CONSTRAINT FK_614B48A1115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_614B48A181257D5D FOREIGN KEY (entity_id) REFERENCES entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_614B48A1115F0EE5 ON domain_entity (domain_id)');
        $this->addSql('CREATE INDEX IDX_614B48A181257D5D ON domain_entity (entity_id)');
        $this->addSql('CREATE TABLE domain_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, domain_id VARCHAR(255) NOT NULL, "action" VARCHAR(255) NOT NULL, date DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_E8D52271115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E8D52271115F0EE5 ON domain_event (domain_id)');
        $this->addSql('CREATE TABLE entity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, handle VARCHAR(255) NOT NULL, tld VARCHAR(255) NOT NULL, j_card CLOB NOT NULL --(DC2Type:json)
        )');
        $this->addSql('CREATE TABLE entity_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entity_id INTEGER NOT NULL, "action" VARCHAR(255) NOT NULL, date DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_975A3F5E81257D5D FOREIGN KEY (entity_id) REFERENCES entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_975A3F5E81257D5D ON entity_event (entity_id)');
        $this->addSql('CREATE TABLE nameserver (ldh_name VARCHAR(255) NOT NULL, PRIMARY KEY(ldh_name))');
        $this->addSql('CREATE TABLE nameserver_entity (nameserver_id VARCHAR(255) NOT NULL, entity_id INTEGER NOT NULL, roles CLOB NOT NULL --(DC2Type:simple_array)
        , status CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(nameserver_id, entity_id), CONSTRAINT FK_A269AFB41A555619 FOREIGN KEY (nameserver_id) REFERENCES nameserver (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A269AFB481257D5D FOREIGN KEY (entity_id) REFERENCES entity (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A269AFB41A555619 ON nameserver_entity (nameserver_id)');
        $this->addSql('CREATE INDEX IDX_A269AFB481257D5D ON nameserver_entity (entity_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TABLE watch_list (token VARCHAR(36) NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY(token), CONSTRAINT FK_152B584BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_152B584BA76ED395 ON watch_list (user_id)');
        $this->addSql('CREATE TABLE watch_lists_domains (watch_list_token VARCHAR(36) NOT NULL, domain_ldh_name VARCHAR(255) NOT NULL, PRIMARY KEY(watch_list_token, domain_ldh_name), CONSTRAINT FK_F693E1D0D52D7AA6 FOREIGN KEY (watch_list_token) REFERENCES watch_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F693E1D0AF923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F693E1D0D52D7AA6 ON watch_lists_domains (watch_list_token)');
        $this->addSql('CREATE INDEX IDX_F693E1D0AF923913 ON watch_lists_domains (domain_ldh_name)');
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
        $this->addSql('DROP TABLE domain_nameservers');
        $this->addSql('DROP TABLE domain_entity');
        $this->addSql('DROP TABLE domain_event');
        $this->addSql('DROP TABLE entity');
        $this->addSql('DROP TABLE entity_event');
        $this->addSql('DROP TABLE nameserver');
        $this->addSql('DROP TABLE nameserver_entity');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE watch_list');
        $this->addSql('DROP TABLE watch_lists_domains');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
