<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240806222018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE connector DROP CONSTRAINT FK_148C456EA76ED395');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT FK_148C456EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list DROP CONSTRAINT FK_152B584BA76ED395');
        $this->addSql('ALTER TABLE watch_list ADD CONSTRAINT FK_152B584BA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT FK_F693E1D0D52D7AA6');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT FK_F693E1D0AF923913');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT FK_F693E1D0D52D7AA6 FOREIGN KEY (watch_list_token) REFERENCES watch_list (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT FK_F693E1D0AF923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT FK_CF857A4CC4508918');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT FK_CF857A4CC4508918 FOREIGN KEY (watch_list_id) REFERENCES watch_list (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE watch_list_trigger DROP CONSTRAINT fk_cf857a4cc4508918');
        $this->addSql('ALTER TABLE watch_list_trigger ADD CONSTRAINT fk_cf857a4cc4508918 FOREIGN KEY (watch_list_id) REFERENCES watch_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list DROP CONSTRAINT fk_152b584ba76ed395');
        $this->addSql('ALTER TABLE watch_list ADD CONSTRAINT fk_152b584ba76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE connector DROP CONSTRAINT fk_148c456ea76ed395');
        $this->addSql('ALTER TABLE connector ADD CONSTRAINT fk_148c456ea76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT fk_f693e1d0d52d7aa6');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT fk_f693e1d0af923913');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT fk_f693e1d0d52d7aa6 FOREIGN KEY (watch_list_token) REFERENCES watch_list (token) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT fk_f693e1d0af923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
