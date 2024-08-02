<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240802232844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('COMMENT ON COLUMN connector.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE watch_list ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE watch_list ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('COMMENT ON COLUMN watch_list.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE connector ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE watch_list ALTER created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector DROP created_at');
        $this->addSql('ALTER TABLE watch_list DROP name');
        $this->addSql('ALTER TABLE watch_list DROP created_at');
    }
}
