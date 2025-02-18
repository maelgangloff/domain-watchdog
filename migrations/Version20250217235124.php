<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217235124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Avoid that an entity can have the same handle on the same tld';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E28446850F7084E918020D9 ON entity (tld_id, handle)');
        $this->addSql('ALTER TABLE entity_event ALTER entity_uid SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_E28446850F7084E918020D9');
        $this->addSql('ALTER TABLE entity_event ALTER entity_uid DROP NOT NULL');
    }
}
