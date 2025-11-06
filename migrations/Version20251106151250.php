<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106151250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert status object to array';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE domain_status
SET add_status = (
    SELECT jsonb_agg(value::text::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(add_status) = 'array' THEN add_status
            WHEN jsonb_typeof(add_status) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(add_status)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
), delete_status = (
    SELECT jsonb_agg(value::text::jsonb)
    FROM jsonb_array_elements(
        CASE
            WHEN jsonb_typeof(delete_status) = 'array' THEN delete_status
            WHEN jsonb_typeof(delete_status) = 'object' THEN to_jsonb(array(SELECT jsonb_array_elements_text(jsonb_agg(value)) FROM jsonb_each_text(delete_status)))
            ELSE '[]'::jsonb
        END
    ) AS t(value)
)");
    }

    public function down(Schema $schema): void
    {
    }
}
