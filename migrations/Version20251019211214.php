<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019211214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tracked_epp_status on watchlist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE watch_list ADD tracked_epp_status JSONB DEFAULT '[]'::jsonb");
        $this->addSql('ALTER TABLE watch_list ALTER tracked_epp_status DROP DEFAULT ');
        $this->addSql('ALTER TABLE watch_list ALTER tracked_epp_status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE watch_list DROP tracked_epp_status');
    }
}
