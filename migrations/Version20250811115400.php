<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\RDAPService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811115400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove blacklisted entities from database';
    }

    public function up(Schema $schema): void
    {
        $handlesList = implode(
            ', ',
            array_map(fn ($h) => "'".addslashes($h)."'", RDAPService::ENTITY_HANDLE_BLACKLIST)
        );

        $this->addSql("
            DELETE FROM entity_event
            WHERE entity_uid IN (
                SELECT id FROM entity WHERE handle IN ($handlesList)
            )
        ");

        $this->addSql("
            DELETE FROM domain_entity
            WHERE entity_uid IN (
                SELECT id FROM entity WHERE handle IN ($handlesList)
            )
        ");

        $this->addSql("
            DELETE FROM nameserver_entity
            WHERE entity_uid IN (
                SELECT id FROM entity WHERE handle IN ($handlesList)
            )
        ");

        $this->addSql("
            DELETE FROM entity
            WHERE handle IN ($handlesList)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->write('Cannot restore deleted entities');
    }
}
