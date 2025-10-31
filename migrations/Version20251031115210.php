<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031115210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add domain_purchase';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE domain_purchase (id UUID NOT NULL, domain_id VARCHAR(255) NOT NULL, connector_id UUID DEFAULT NULL, user_id INT DEFAULT NULL, domain_updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, domain_ordered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, connector_provider VARCHAR(255) NOT NULL, domain_deleted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_72999E74115F0EE5 ON domain_purchase (domain_id)');
        $this->addSql('CREATE INDEX IDX_72999E744D085745 ON domain_purchase (connector_id)');
        $this->addSql('CREATE INDEX IDX_72999E74A76ED395 ON domain_purchase (user_id)');
        $this->addSql('COMMENT ON COLUMN domain_purchase.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN domain_purchase.connector_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN domain_purchase.domain_updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN domain_purchase.domain_ordered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN domain_purchase.domain_deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E74115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E744D085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE domain_purchase ADD CONSTRAINT FK_72999E74A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watchlist ALTER enabled DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E74115F0EE5');
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E744D085745');
        $this->addSql('ALTER TABLE domain_purchase DROP CONSTRAINT FK_72999E74A76ED395');
        $this->addSql('DROP TABLE domain_purchase');
        $this->addSql('ALTER TABLE watchlist ALTER enabled SET DEFAULT true');
    }
}
