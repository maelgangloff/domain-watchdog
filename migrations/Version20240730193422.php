<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240730193422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE watch_list ADD connector_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN watch_list.connector_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE watch_list ADD CONSTRAINT FK_152B584B4D085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_152B584B4D085745 ON watch_list (connector_id)');
        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT fk_cf857a4c4d085745');
        $this->addSql('DROP INDEX idx_cf857a4c4d085745');
        $this->addSql('ALTER TABLE watch_list_trigger DROP connector_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE watch_list_trigger ADD connector_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN watch_list_trigger.connector_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT fk_cf857a4c4d085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_cf857a4c4d085745 ON watch_list_trigger (connector_id)');
        $this->addSql('ALTER TABLE watch_list DROP CONSTRAINT FK_152B584B4D085745');
        $this->addSql('DROP INDEX IDX_152B584B4D085745');
        $this->addSql('ALTER TABLE watch_list DROP connector_id');
    }
}
