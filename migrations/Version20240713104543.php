<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240713104543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__nameserver AS SELECT ldh_name, handle FROM nameserver');
        $this->addSql('DROP TABLE nameserver');
        $this->addSql('CREATE TABLE nameserver (ldh_name VARCHAR(255) NOT NULL, handle VARCHAR(255) DEFAULT NULL, PRIMARY KEY(ldh_name))');
        $this->addSql('INSERT INTO nameserver (ldh_name, handle) SELECT ldh_name, handle FROM __temp__nameserver');
        $this->addSql('DROP TABLE __temp__nameserver');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__nameserver AS SELECT ldh_name, handle FROM nameserver');
        $this->addSql('DROP TABLE nameserver');
        $this->addSql('CREATE TABLE nameserver (ldh_name VARCHAR(255) NOT NULL, handle VARCHAR(255) NOT NULL, PRIMARY KEY(ldh_name))');
        $this->addSql('INSERT INTO nameserver (ldh_name, handle) SELECT ldh_name, handle FROM __temp__nameserver');
        $this->addSql('DROP TABLE __temp__nameserver');
    }
}
