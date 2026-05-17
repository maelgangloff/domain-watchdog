<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517203154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add domain_purchase columns';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E74A76ED395');
        $this->addSql('ALTER TABLE domain_purchase ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD exception_class VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD exception_message VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE domain_purchase ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E74A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_72999E74115F0EE5D2E041DAEE8CBF73A76ED395 ON domain_purchase (domain_id, domain_updated_at, domain_ordered_at, user_id)');

        $this->addSql('UPDATE domain_purchase SET type = \'success\' WHERE domain_ordered_at IS NOT NULL');
        $this->addSql('UPDATE domain_purchase SET type = \'failure\' WHERE domain_ordered_at IS NULL');

        $this->addSql('ALTER TABLE domain_purchase ALTER type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT fk_72999e74a76ed395');
        $this->addSql('DROP INDEX UNIQ_72999E74115F0EE5D2E041DAEE8CBF73A76ED395');
        $this->addSql('ALTER TABLE domain_purchase DROP type');
        $this->addSql('ALTER TABLE domain_purchase DROP exception_class');
        $this->addSql('ALTER TABLE domain_purchase DROP reason');
        $this->addSql('ALTER TABLE domain_purchase DROP exception_message');
        $this->addSql('ALTER TABLE domain_purchase ALTER user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT fk_72999e74a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
