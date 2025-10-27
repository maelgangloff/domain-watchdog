<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915200844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove auto increment on id';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE icann_accreditation_id_seq CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE icann_accreditation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
    }
}
