<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004101245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter les nouvelles colonnes sans contrainte NOT NULL
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "user" ADD verified_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".verified_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE "user" SET created_at = TO_TIMESTAMP(0)');
        $this->addSql('UPDATE "user" SET verified_at = TO_TIMESTAMP(0) WHERE is_verified = true');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN created_at SET NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP is_verified');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD is_verified BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE "user" DROP created_at');
        $this->addSql('ALTER TABLE "user" DROP verified_at');
    }
}
