<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241207125955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_entity ALTER deleted DROP DEFAULT');
        $this->addSql('ALTER TABLE domain_event ALTER deleted DROP DEFAULT');
        $this->addSql('ALTER TABLE entity_event ALTER deleted DROP DEFAULT');

        $this->addSql('
        DELETE FROM domain_event
        WHERE id NOT IN (
            SELECT MIN(id)
            FROM domain_event
            GROUP BY action, date, domain_id
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E8D5227147CC8C92AA9E377A115F0EE5 ON domain_event (action, date, domain_id)');

        $this->addSql('
        DELETE FROM entity_event
        WHERE id NOT IN (
            SELECT MIN(id)
            FROM entity_event
            GROUP BY action, date, entity_id
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_975A3F5E47CC8C92AA9E377A81257D5D ON entity_event (action, date, entity_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_975A3F5E47CC8C92AA9E377A81257D5D');
        $this->addSql('DROP INDEX UNIQ_E8D5227147CC8C92AA9E377A115F0EE5');
        $this->addSql('ALTER TABLE entity_event ALTER deleted SET DEFAULT false');
        $this->addSql('ALTER TABLE domain_event ALTER deleted SET DEFAULT false');
        $this->addSql('ALTER TABLE domain_entity ALTER deleted SET DEFAULT false');
    }
}
