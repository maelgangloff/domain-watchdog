<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240720210058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE watch_list_trigger (event VARCHAR(255) NOT NULL, "action" VARCHAR(255) NOT NULL, watch_list_id VARCHAR(36) NOT NULL, PRIMARY KEY(event, watch_list_id, "action"), CONSTRAINT FK_CF857A4CC4508918 FOREIGN KEY (watch_list_id) REFERENCES watch_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CF857A4CC4508918 ON watch_list_trigger (watch_list_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE watch_list_trigger');
    }
}
