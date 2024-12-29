<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241229155050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a date column on domain_status to better identify periods';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_status RENAME COLUMN date TO created_at');
        $this->addSql('ALTER TABLE domain_status ADD date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NOW() NOT NULL');
        $this->addSql('UPDATE domain_status SET date = created_at');
        $this->addSql('ALTER TABLE domain_status ALTER COLUMN date DROP DEFAULT');

        $this->addSql('COMMENT ON COLUMN domain_status.date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_status DROP COLUMN date');
        $this->addSql('ALTER TABLE domain_status RENAME COLUMN created_at TO date');
    }
}
