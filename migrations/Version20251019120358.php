<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019120358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lowercase on columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE domain
SET status = (
    SELECT jsonb_agg(lower(value::text)::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(status) = 'array' THEN status
            WHEN jsonb_typeof(status) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(status)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
)");

        $this->addSql("UPDATE domain_status
SET add_status = (
    SELECT jsonb_agg(lower(value::text)::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(add_status) = 'array' THEN add_status
            WHEN jsonb_typeof(add_status) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(add_status)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
), delete_status = (
    SELECT jsonb_agg(lower(value::text)::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(delete_status) = 'array' THEN delete_status
            WHEN jsonb_typeof(delete_status) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(delete_status)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
)");

        $this->addSql("UPDATE domain_entity
SET roles = (
    SELECT jsonb_agg(lower(value::text)::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(roles) = 'array' THEN roles
            WHEN jsonb_typeof(roles) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(roles)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
)");

        $this->addSql("UPDATE nameserver_entity
SET roles = (
    SELECT jsonb_agg(lower(value::text)::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(roles) = 'array' THEN roles
            WHEN jsonb_typeof(roles) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(roles)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
), status = (
    SELECT jsonb_agg(lower(value::text)::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(status) = 'array' THEN status
            WHEN jsonb_typeof(status) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(status)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
)");

        $this->addSql('UPDATE domain_event SET action = lower(action)');
        $this->addSql('UPDATE entity_event SET action = lower(action)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
