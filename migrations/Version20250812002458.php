<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250812002458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dns_key table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dns_key (algorithm INT NOT NULL, digest_type INT NOT NULL, key_tag BYTEA NOT NULL, digest BYTEA NOT NULL, domain_id VARCHAR(255) NOT NULL, PRIMARY KEY(algorithm, digest_type, key_tag, domain_id, digest))');
        $this->addSql('CREATE INDEX IDX_88A62EF2115F0EE5 ON dns_key (domain_id)');
        $this->addSql('ALTER TABLE dns_key ADD CONSTRAINT FK_88A62EF2115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dns_key DROP CONSTRAINT FK_88A62EF2115F0EE5');
        $this->addSql('DROP TABLE dns_key');
    }
}
