<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251108171723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add j_card_fn and j_card_org';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE entity ADD j_card_fn VARCHAR(255) GENERATED ALWAYS AS (UPPER(jsonb_path_query_first(j_card, \'$[1]?(@[0] == "fn")[3]\') #>> \'{}\')) STORED');
        $this->addSql('ALTER TABLE entity ADD j_card_org VARCHAR(255) GENERATED ALWAYS AS (UPPER(jsonb_path_query_first(j_card, \'$[1]?(@[0] == "org")[3]\') #>> \'{}\')) STORED');
        $this->addSql('CREATE INDEX entity_j_card_fn_idx ON entity (j_card_fn)');
        $this->addSql('CREATE INDEX entity_j_card_org_idx ON entity (j_card_org)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX entity_j_card_fn_idx');
        $this->addSql('DROP INDEX entity_j_card_org_idx');
        $this->addSql('ALTER TABLE entity DROP j_card_fn');
        $this->addSql('ALTER TABLE entity DROP j_card_org');
    }
}
