<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240712105119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookmark_list (token VARCHAR(36) NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY(token), CONSTRAINT FK_A650C0C4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A650C0C4A76ED395 ON bookmark_list (user_id)');
        $this->addSql('CREATE TABLE bookmark_lists_domains (bookmark_token VARCHAR(36) NOT NULL, domain_handle VARCHAR(255) NOT NULL, PRIMARY KEY(bookmark_token, domain_handle), CONSTRAINT FK_C12B6400D0B0A7FC FOREIGN KEY (bookmark_token) REFERENCES bookmark_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C12B6400FEE32C10 FOREIGN KEY (domain_handle) REFERENCES domain (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C12B6400D0B0A7FC ON bookmark_lists_domains (bookmark_token)');
        $this->addSql('CREATE INDEX IDX_C12B6400FEE32C10 ON bookmark_lists_domains (domain_handle)');
        $this->addSql('CREATE TABLE domain (handle VARCHAR(255) NOT NULL, ldh_name VARCHAR(255) NOT NULL, whois_status VARCHAR(255) NOT NULL, status CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(handle))');
        $this->addSql('CREATE TABLE domain_nameservers (domain_handle VARCHAR(255) NOT NULL, nameserver_handle VARCHAR(255) NOT NULL, PRIMARY KEY(domain_handle, nameserver_handle), CONSTRAINT FK_B6E6B63AFEE32C10 FOREIGN KEY (domain_handle) REFERENCES domain (handle) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B6E6B63A3DC1CB05 FOREIGN KEY (nameserver_handle) REFERENCES nameserver (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B6E6B63AFEE32C10 ON domain_nameservers (domain_handle)');
        $this->addSql('CREATE INDEX IDX_B6E6B63A3DC1CB05 ON domain_nameservers (nameserver_handle)');
        $this->addSql('CREATE TABLE domain_entity (domain_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(domain_id, entity_id), CONSTRAINT FK_614B48A1115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (handle) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_614B48A181257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_614B48A1115F0EE5 ON domain_entity (domain_id)');
        $this->addSql('CREATE INDEX IDX_614B48A181257D5D ON domain_entity (entity_id)');
        $this->addSql('CREATE TABLE domain_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, domain_id VARCHAR(255) NOT NULL, "action" VARCHAR(255) NOT NULL, date DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_E8D52271115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E8D52271115F0EE5 ON domain_event (domain_id)');
        $this->addSql('CREATE TABLE entity (handle VARCHAR(255) NOT NULL, PRIMARY KEY(handle))');
        $this->addSql('CREATE TABLE entity_event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entity_id VARCHAR(255) NOT NULL, "action" VARCHAR(255) NOT NULL, date DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_975A3F5E81257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_975A3F5E81257D5D ON entity_event (entity_id)');
        $this->addSql('CREATE TABLE nameserver (handle VARCHAR(255) NOT NULL, ldh_name VARCHAR(255) NOT NULL, status CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(handle))');
        $this->addSql('CREATE TABLE nameserver_entity (nameserver_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:simple_array)
        , status CLOB NOT NULL --(DC2Type:simple_array)
        , PRIMARY KEY(nameserver_id, entity_id), CONSTRAINT FK_A269AFB41A555619 FOREIGN KEY (nameserver_id) REFERENCES nameserver (handle) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A269AFB481257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A269AFB41A555619 ON nameserver_entity (nameserver_id)');
        $this->addSql('CREATE INDEX IDX_A269AFB481257D5D ON nameserver_entity (entity_id)');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
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
        $this->addSql('DROP TABLE bookmark_list');
        $this->addSql('DROP TABLE bookmark_lists_domains');
        $this->addSql('DROP TABLE domain');
        $this->addSql('DROP TABLE domain_nameservers');
        $this->addSql('DROP TABLE domain_entity');
        $this->addSql('DROP TABLE domain_event');
        $this->addSql('DROP TABLE entity');
        $this->addSql('DROP TABLE entity_event');
        $this->addSql('DROP TABLE nameserver');
        $this->addSql('DROP TABLE nameserver_entity');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
