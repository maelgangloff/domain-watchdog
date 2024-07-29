<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240729093649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE watch_list_trigger ADD connector_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT FK_CF857A4C4D085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CF857A4C4D085745 ON watch_list_trigger (connector_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT FK_CF857A4C4D085745');
        $this->addSql('DROP INDEX IDX_CF857A4C4D085745');
        $this->addSql('ALTER TABLE watch_list_trigger DROP connector_id');
    }
}
