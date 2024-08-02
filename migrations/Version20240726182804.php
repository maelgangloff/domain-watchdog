<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240726182804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domain (ldh_name VARCHAR(255) NOT NULL, tld_id VARCHAR(63) NOT NULL, handle VARCHAR(255) DEFAULT NULL, status TEXT DEFAULT NULL, created_at DATE NOT NULL, updated_at DATE NOT NULL, deleted BOOLEAN NOT NULL, PRIMARY KEY(ldh_name))');
        $this->addSql('CREATE INDEX IDX_A7A91E0B50F7084E ON domain (tld_id)');
        $this->addSql('COMMENT ON COLUMN domain.status IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN domain.created_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN domain.updated_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE domain_nameservers (domain_ldh_name VARCHAR(255) NOT NULL, nameserver_ldh_name VARCHAR(255) NOT NULL, PRIMARY KEY(domain_ldh_name, nameserver_ldh_name))');
        $this->addSql('CREATE INDEX IDX_B6E6B63AAF923913 ON domain_nameservers (domain_ldh_name)');
        $this->addSql('CREATE INDEX IDX_B6E6B63AA6496BFE ON domain_nameservers (nameserver_ldh_name)');
        $this->addSql('CREATE TABLE domain_entity (domain_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles TEXT NOT NULL, PRIMARY KEY(domain_id, entity_id))');
        $this->addSql('CREATE INDEX IDX_614B48A1115F0EE5 ON domain_entity (domain_id)');
        $this->addSql('CREATE INDEX IDX_614B48A181257D5D ON domain_entity (entity_id)');
        $this->addSql('COMMENT ON COLUMN domain_entity.roles IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE domain_event (id INT NOT NULL, domain_id VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E8D52271115F0EE5 ON domain_event (domain_id)');
        $this->addSql('COMMENT ON COLUMN domain_event.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE entity (handle VARCHAR(255) NOT NULL, j_card JSON NOT NULL, PRIMARY KEY(handle))');
        $this->addSql('CREATE TABLE entity_event (id INT NOT NULL, entity_id VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_975A3F5E81257D5D ON entity_event (entity_id)');
        $this->addSql('COMMENT ON COLUMN entity_event.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE nameserver (ldh_name VARCHAR(255) NOT NULL, PRIMARY KEY(ldh_name))');
        $this->addSql('CREATE TABLE nameserver_entity (nameserver_id VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, roles TEXT NOT NULL, status TEXT NOT NULL, PRIMARY KEY(nameserver_id, entity_id))');
        $this->addSql('CREATE INDEX IDX_A269AFB41A555619 ON nameserver_entity (nameserver_id)');
        $this->addSql('CREATE INDEX IDX_A269AFB481257D5D ON nameserver_entity (entity_id)');
        $this->addSql('COMMENT ON COLUMN nameserver_entity.roles IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN nameserver_entity.status IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE rdap_server (url VARCHAR(255) NOT NULL, tld_id VARCHAR(63) NOT NULL, updated_at DATE NOT NULL, PRIMARY KEY(url, tld_id))');
        $this->addSql('CREATE INDEX IDX_CCBF17A850F7084E ON rdap_server (tld_id)');
        $this->addSql('COMMENT ON COLUMN rdap_server.updated_at IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE tld (tld VARCHAR(63) NOT NULL, contract_terminated BOOLEAN DEFAULT NULL, date_of_contract_signature DATE DEFAULT NULL, delegation_date DATE DEFAULT NULL, registry_operator VARCHAR(255) DEFAULT NULL, removal_date DATE DEFAULT NULL, specification13 BOOLEAN DEFAULT NULL, type VARCHAR(10) NOT NULL, PRIMARY KEY(tld))');
        $this->addSql('COMMENT ON COLUMN tld.date_of_contract_signature IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tld.delegation_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tld.removal_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE watch_list (token UUID NOT NULL, user_id INT NOT NULL, PRIMARY KEY(token))');
        $this->addSql('CREATE INDEX IDX_152B584BA76ED395 ON watch_list (user_id)');
        $this->addSql('COMMENT ON COLUMN watch_list.token IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE watch_lists_domains (watch_list_token UUID NOT NULL, domain_ldh_name VARCHAR(255) NOT NULL, PRIMARY KEY(watch_list_token, domain_ldh_name))');
        $this->addSql('CREATE INDEX IDX_F693E1D0D52D7AA6 ON watch_lists_domains (watch_list_token)');
        $this->addSql('CREATE INDEX IDX_F693E1D0AF923913 ON watch_lists_domains (domain_ldh_name)');
        $this->addSql('COMMENT ON COLUMN watch_lists_domains.watch_list_token IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE watch_list_trigger (event VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, watch_list_id UUID NOT NULL, PRIMARY KEY(event, watch_list_id, action))');
        $this->addSql('CREATE INDEX IDX_CF857A4CC4508918 ON watch_list_trigger (watch_list_id)');
        $this->addSql('COMMENT ON COLUMN watch_list_trigger.watch_list_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE domain ADD CONSTRAINT FK_A7A91E0B50F7084E FOREIGN KEY (tld_id) REFERENCES tld (tld) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_nameservers ADD CONSTRAINT FK_B6E6B63AAF923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_nameservers ADD CONSTRAINT FK_B6E6B63AA6496BFE FOREIGN KEY (nameserver_ldh_name) REFERENCES nameserver (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_entity ADD CONSTRAINT FK_614B48A1115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_entity ADD CONSTRAINT FK_614B48A181257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_event ADD CONSTRAINT FK_E8D52271115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entity_event ADD CONSTRAINT FK_975A3F5E81257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE nameserver_entity ADD CONSTRAINT FK_A269AFB41A555619 FOREIGN KEY (nameserver_id) REFERENCES nameserver (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE nameserver_entity ADD CONSTRAINT FK_A269AFB481257D5D FOREIGN KEY (entity_id) REFERENCES entity (handle) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE rdap_server ADD CONSTRAINT FK_CCBF17A850F7084E FOREIGN KEY (tld_id) REFERENCES tld (tld) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list ADD CONSTRAINT FK_152B584BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT FK_F693E1D0D52D7AA6 FOREIGN KEY (watch_list_token) REFERENCES watch_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT FK_F693E1D0AF923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT FK_CF857A4CC4508918 FOREIGN KEY (watch_list_id) REFERENCES watch_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain DROP CONSTRAINT FK_A7A91E0B50F7084E');
        $this->addSql('ALTER TABLE domain_nameservers DROP CONSTRAINT FK_B6E6B63AAF923913');
        $this->addSql('ALTER TABLE domain_nameservers DROP CONSTRAINT FK_B6E6B63AA6496BFE');
        $this->addSql('ALTER TABLE domain_entity DROP CONSTRAINT FK_614B48A1115F0EE5');
        $this->addSql('ALTER TABLE domain_entity DROP CONSTRAINT FK_614B48A181257D5D');
        $this->addSql('ALTER TABLE domain_event DROP CONSTRAINT FK_E8D52271115F0EE5');
        $this->addSql('ALTER TABLE entity_event DROP CONSTRAINT FK_975A3F5E81257D5D');
        $this->addSql('ALTER TABLE nameserver_entity DROP CONSTRAINT FK_A269AFB41A555619');
        $this->addSql('ALTER TABLE nameserver_entity DROP CONSTRAINT FK_A269AFB481257D5D');
        $this->addSql('ALTER TABLE rdap_server DROP CONSTRAINT FK_CCBF17A850F7084E');
        $this->addSql('ALTER TABLE watch_list DROP CONSTRAINT FK_152B584BA76ED395');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT FK_F693E1D0D52D7AA6');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT FK_F693E1D0AF923913');
        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT FK_CF857A4CC4508918');
        $this->addSql('DROP TABLE domain');
        $this->addSql('DROP TABLE domain_nameservers');
        $this->addSql('DROP TABLE domain_entity');
        $this->addSql('DROP TABLE domain_event');
        $this->addSql('DROP TABLE entity');
        $this->addSql('DROP TABLE entity_event');
        $this->addSql('DROP TABLE nameserver');
        $this->addSql('DROP TABLE nameserver_entity');
        $this->addSql('DROP TABLE rdap_server');
        $this->addSql('DROP TABLE tld');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE watch_list');
        $this->addSql('DROP TABLE watch_lists_domains');
        $this->addSql('DROP TABLE watch_list_trigger');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
