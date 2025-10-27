<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016193639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert domain status to JSONB';
    }

    public function up(Schema $schema): void
    {
        // domain.status
        $this->addSql("ALTER TABLE domain ADD status_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE domain
        SET status_jsonb = to_jsonb(string_to_array(status, ','))
        WHERE status IS NOT NULL AND status <> ''
    ");
        $this->addSql('ALTER TABLE domain DROP COLUMN status');
        $this->addSql('ALTER TABLE domain RENAME COLUMN status_jsonb TO status');
        $this->addSql('COMMENT ON COLUMN domain.status IS NULL');

        // domain_entity.roles
        $this->addSql("ALTER TABLE domain_entity ADD roles_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE domain_entity
        SET roles_jsonb = to_jsonb(string_to_array(roles, ','))
        WHERE roles IS NOT NULL AND roles <> ''
    ");
        $this->addSql('ALTER TABLE domain_entity DROP COLUMN roles');
        $this->addSql('ALTER TABLE domain_entity RENAME COLUMN roles_jsonb TO roles');
        $this->addSql('COMMENT ON COLUMN domain_entity.roles IS NULL');

        // nameserver_entity.roles
        $this->addSql("ALTER TABLE nameserver_entity ADD roles_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE nameserver_entity
        SET roles_jsonb = to_jsonb(string_to_array(roles, ','))
        WHERE roles IS NOT NULL AND roles <> ''
    ");
        $this->addSql('ALTER TABLE nameserver_entity DROP COLUMN roles');
        $this->addSql('ALTER TABLE nameserver_entity RENAME COLUMN roles_jsonb TO roles');
        $this->addSql('COMMENT ON COLUMN nameserver_entity.roles IS NULL');

        // nameserver_entity.status
        $this->addSql("ALTER TABLE nameserver_entity ADD status_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE nameserver_entity
        SET status_jsonb = to_jsonb(string_to_array(status, ','))
        WHERE status IS NOT NULL AND status <> ''
    ");
        $this->addSql('ALTER TABLE nameserver_entity DROP COLUMN status');
        $this->addSql('ALTER TABLE nameserver_entity RENAME COLUMN status_jsonb TO status');
        $this->addSql('COMMENT ON COLUMN nameserver_entity.status IS NULL');

        // domain_status.add_status
        $this->addSql("ALTER TABLE domain_status ADD add_status_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE domain_status
        SET add_status_jsonb = to_jsonb(string_to_array(add_status, ','))
        WHERE add_status IS NOT NULL AND add_status <> ''
    ");
        $this->addSql('ALTER TABLE domain_status DROP COLUMN add_status');
        $this->addSql('ALTER TABLE domain_status RENAME COLUMN add_status_jsonb TO add_status');
        $this->addSql('COMMENT ON COLUMN domain_status.add_status IS NULL');

        // domain_status.delete_status
        $this->addSql("ALTER TABLE domain_status ADD delete_status_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE domain_status
        SET delete_status_jsonb = to_jsonb(string_to_array(delete_status, ','))
        WHERE delete_status IS NOT NULL AND delete_status <> ''
    ");
        $this->addSql('ALTER TABLE domain_status DROP COLUMN delete_status');
        $this->addSql('ALTER TABLE domain_status RENAME COLUMN delete_status_jsonb TO delete_status');
        $this->addSql('COMMENT ON COLUMN domain_status.delete_status IS NULL');

        // watch_list.webhook_dsn
        $this->addSql("ALTER TABLE watch_list ADD webhook_dsn_jsonb JSONB DEFAULT '[]'::jsonb");
        $this->addSql("
        UPDATE watch_list
        SET webhook_dsn_jsonb = to_jsonb(string_to_array(webhook_dsn, ','))
        WHERE webhook_dsn IS NOT NULL AND webhook_dsn <> ''
    ");
        $this->addSql('ALTER TABLE watch_list DROP COLUMN webhook_dsn');
        $this->addSql('ALTER TABLE watch_list RENAME COLUMN webhook_dsn_jsonb TO webhook_dsn');
        $this->addSql('COMMENT ON COLUMN watch_list.webhook_dsn IS NULL');

        $this->addSql('ALTER TABLE domain ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE domain_entity ALTER roles DROP DEFAULT');
        $this->addSql('ALTER TABLE domain_entity ALTER roles SET NOT NULL');
        $this->addSql('ALTER TABLE domain_status ALTER add_status DROP DEFAULT');
        $this->addSql('ALTER TABLE domain_status ALTER delete_status DROP DEFAULT');
        $this->addSql('ALTER TABLE nameserver_entity ALTER roles DROP DEFAULT');
        $this->addSql('ALTER TABLE nameserver_entity ALTER roles SET NOT NULL');
        $this->addSql('ALTER TABLE nameserver_entity ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE nameserver_entity ALTER status SET NOT NULL');
        $this->addSql('ALTER TABLE watch_list ALTER webhook_dsn DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // domain.status
        $this->addSql('ALTER TABLE domain ADD status_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE domain
        SET status_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(status)
        ), ',')
        WHERE status IS NOT NULL
    ");
        $this->addSql('ALTER TABLE domain DROP COLUMN status');
        $this->addSql('ALTER TABLE domain RENAME COLUMN status_text TO status');
        $this->addSql('COMMENT ON COLUMN domain.status IS NULL');

        // domain_entity.roles
        $this->addSql('ALTER TABLE domain_entity ADD roles_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE domain_entity
        SET roles_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(roles)
        ), ',')
        WHERE roles IS NOT NULL
    ");
        $this->addSql('ALTER TABLE domain_entity DROP COLUMN roles');
        $this->addSql('ALTER TABLE domain_entity RENAME COLUMN roles_text TO roles');
        $this->addSql('COMMENT ON COLUMN domain_entity.roles IS NULL');

        // nameserver_entity.roles
        $this->addSql('ALTER TABLE nameserver_entity ADD roles_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE nameserver_entity
        SET roles_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(roles)
        ), ',')
        WHERE roles IS NOT NULL
    ");
        $this->addSql('ALTER TABLE nameserver_entity DROP COLUMN roles');
        $this->addSql('ALTER TABLE nameserver_entity RENAME COLUMN roles_text TO roles');
        $this->addSql('COMMENT ON COLUMN nameserver_entity.roles IS NULL');

        // nameserver_entity.status
        $this->addSql('ALTER TABLE nameserver_entity ADD status_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE nameserver_entity
        SET status_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(status)
        ), ',')
        WHERE status IS NOT NULL
    ");
        $this->addSql('ALTER TABLE nameserver_entity DROP COLUMN status');
        $this->addSql('ALTER TABLE nameserver_entity RENAME COLUMN status_text TO status');
        $this->addSql('COMMENT ON COLUMN nameserver_entity.status IS NULL');

        // domain_status.add_status
        $this->addSql('ALTER TABLE domain_status ADD add_status_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE domain_status
        SET add_status_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(add_status)
        ), ',')
        WHERE add_status IS NOT NULL
    ");
        $this->addSql('ALTER TABLE domain_status DROP COLUMN add_status');
        $this->addSql('ALTER TABLE domain_status RENAME COLUMN add_status_text TO add_status');
        $this->addSql('COMMENT ON COLUMN domain_status.add_status IS NULL');

        // domain_status.delete_status
        $this->addSql('ALTER TABLE domain_status ADD delete_status_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE domain_status
        SET delete_status_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(delete_status)
        ), ',')
        WHERE delete_status IS NOT NULL
    ");
        $this->addSql('ALTER TABLE domain_status DROP COLUMN delete_status');
        $this->addSql('ALTER TABLE domain_status RENAME COLUMN delete_status_text TO delete_status');
        $this->addSql('COMMENT ON COLUMN domain_status.delete_status IS NULL');

        // watch_list.webhook_dsn
        $this->addSql('ALTER TABLE watch_list ADD webhook_dsn_text TEXT DEFAULT NULL');
        $this->addSql("
        UPDATE watch_list
        SET webhook_dsn_text = array_to_string(ARRAY(
            SELECT jsonb_array_elements_text(webhook_dsn)
        ), ',')
        WHERE webhook_dsn IS NOT NULL
    ");
        $this->addSql('ALTER TABLE watch_list DROP COLUMN webhook_dsn');
        $this->addSql('ALTER TABLE watch_list RENAME COLUMN webhook_dsn_text TO webhook_dsn');
        $this->addSql('COMMENT ON COLUMN watch_list.webhook_dsn IS NULL');
    }
}
