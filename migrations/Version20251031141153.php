<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031141153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update domain_purchase cascade';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E74115F0EE5');
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E744D085745');
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E74A76ED395');
        $this->addSql('ALTER TABLE domain_purchase ALTER domain_id DROP NOT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E74115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E744D085745 FOREIGN KEY (connector_id) REFERENCES connector (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E74A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT fk_72999e74115f0ee5');
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT fk_72999e744d085745');
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT fk_72999e74a76ed395');
        $this->addSql('ALTER TABLE domain_purchase ALTER domain_id SET NOT NULL');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT fk_72999e74115f0ee5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT fk_72999e744d085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT fk_72999e74a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
