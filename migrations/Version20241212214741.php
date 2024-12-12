<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241212214741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE domain_status_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE domain_status (id INT NOT NULL, domain_id VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, add_status TEXT NOT NULL, delete_status TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_146369D5115F0EE5 ON domain_status (domain_id)');
        $this->addSql('COMMENT ON COLUMN domain_status.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN domain_status.add_status IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN domain_status.delete_status IS \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE domain_status ADD CONSTRAINT FK_146369D5115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE domain_status_id_seq CASCADE');
        $this->addSql('ALTER TABLE domain_status DROP CONSTRAINT FK_146369D5115F0EE5');
        $this->addSql('DROP TABLE domain_status');
    }
}
