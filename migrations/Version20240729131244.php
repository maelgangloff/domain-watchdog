<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240729131244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE connector (id UUID NOT NULL, user_id INT NOT NULL, auth_data JSON NOT NULL, provider VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_148C456EA76ED395 ON connector (user_id)');
        $this->addSql('COMMENT ON COLUMN connector.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list_trigger ADD connector_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN watch_list_trigger.connector_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT FK_CF857A4C4D085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CF857A4C4D085745 ON watch_list_trigger (connector_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT FK_CF857A4C4D085745');
        $this->addSql('ALTER TABLE connector DROP CONSTRAINT FK_148C456EA76ED395');
        $this->addSql('DROP TABLE connector');
        $this->addSql('DROP INDEX IDX_CF857A4C4D085745');
        $this->addSql('ALTER TABLE watch_list_trigger DROP connector_id');
    }
}
