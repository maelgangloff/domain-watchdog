<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015165917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove watchlist_trigger and add tracked_events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE watch_list ADD tracked_events JSONB');

        $this->addSql("
        UPDATE watch_list wl
        SET tracked_events = sub.events::jsonb
        FROM (
            SELECT watch_list_id, json_agg(event) AS events
            FROM watch_list_trigger
            WHERE action = 'email'
            GROUP BY watch_list_id
        ) AS sub
        WHERE wl.token = sub.watch_list_id
    ");

        $this->addSql("UPDATE watch_list SET tracked_events = '[]' WHERE tracked_events IS NULL");

        $this->addSql('ALTER TABLE watch_list ALTER tracked_events SET NOT NULL');

        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT fk_cf857a4cc4508918');
        $this->addSql('DROP TABLE watch_list_trigger');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE watch_list_trigger (event VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, watch_list_id UUID NOT NULL, PRIMARY KEY(event, watch_list_id, action))');
        $this->addSql('CREATE INDEX idx_cf857a4cc4508918 ON watch_list_trigger (watch_list_id)');
        $this->addSql('COMMENT ON COLUMN watch_list_trigger.watch_list_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT fk_cf857a4cc4508918 FOREIGN KEY (watch_list_id) REFERENCES watch_list (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE watch_list DROP tracked_events');
    }
}
