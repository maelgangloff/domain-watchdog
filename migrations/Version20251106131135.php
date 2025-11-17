<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106131135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX domain_entity_roles_idx ON domain_entity USING GIN (roles)');
        $this->addSql('CREATE INDEX domain_event_action_idx ON domain_event (action)');
        $this->addSql('CREATE INDEX entity_event_action_idx ON entity_event (action)');
        $this->addSql('CREATE INDEX icann_accreditation_status_idx ON icann_accreditation (status)');
        $this->addSql('CREATE INDEX tld_type_idx ON tld (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX domain_entity_roles_idx');
        $this->addSql('DROP INDEX icann_accreditation_status_idx');
        $this->addSql('DROP INDEX tld_type_idx');
        $this->addSql('DROP INDEX domain_event_action_idx');
        $this->addSql('DROP INDEX entity_event_action_idx');
    }
}
