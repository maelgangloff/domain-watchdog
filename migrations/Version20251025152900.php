<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025152900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename WatchList to Watchlist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT fk_f693e1d0d52d7aa6');
        $this->addSql('ALTER TABLE watch_list DROP CONSTRAINT fk_152b584b4d085745');
        $this->addSql('ALTER TABLE watch_list DROP CONSTRAINT fk_152b584ba76ed395');

        $this->addSql('ALTER TABLE watch_list RENAME TO watchlist');
        $this->addSql('ALTER INDEX idx_152b584ba76ed395 RENAME TO IDX_340388D3A76ED395');
        $this->addSql('ALTER INDEX idx_152b584b4d085745 RENAME TO IDX_340388D34D085745');

        $this->addSql('ALTER TABLE watchlist ADD CONSTRAINT FK_340388D3A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watchlist ADD CONSTRAINT FK_340388D34D085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT FK_F693E1D0D52D7AA6 FOREIGN KEY (watch_list_token) REFERENCES watchlist (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT fk_f693e1d0af923913');
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT fk_f693e1d0d52d7aa6');

        $this->addSql('ALTER TABLE watch_lists_domains RENAME TO watchlist_domains');
        $this->addSql('ALTER INDEX idx_f693e1d0af923913 RENAME TO IDX_196DE762AF923913');
        $this->addSql('ALTER INDEX idx_f693e1d0d52d7aa6 RENAME TO IDX_196DE762F1E43AD7');

        $this->addSql('ALTER TABLE watchlist_domains RENAME COLUMN watch_list_token TO watchlist_token');

        $this->addSql('COMMENT ON COLUMN watchlist_domains.watchlist_token IS \'(DC2Type:uuid)\'');

        $this->addSql('ALTER TABLE watchlist_domains ADD CONSTRAINT FK_196DE762F1E43AD7 FOREIGN KEY (watchlist_token) REFERENCES watchlist (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watchlist_domains ADD CONSTRAINT FK_196DE762AF923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE watch_lists_domains DROP CONSTRAINT FK_F693E1D0D52D7AA6');
        $this->addSql('ALTER TABLE watchlist DROP CONSTRAINT FK_340388D3A76ED395');
        $this->addSql('ALTER TABLE watchlist DROP CONSTRAINT FK_340388D34D085745');

        $this->addSql('ALTER TABLE watchlist RENAME TO watch_list');
        $this->addSql('ALTER INDEX IDX_340388D3A76ED395 RENAME TO idx_152b584ba76ed395');
        $this->addSql('ALTER INDEX IDX_340388D34D085745 RENAME TO idx_152b584b4d085745');

        $this->addSql('ALTER TABLE watch_list ADD CONSTRAINT fk_152b584ba76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_list ADD CONSTRAINT fk_152b584b4d085745 FOREIGN KEY (connector_id) REFERENCES connector (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT fk_f693e1d0d52d7aa6 FOREIGN KEY (watch_list_token) REFERENCES watch_list (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE watchlist_domains DROP CONSTRAINT FK_196DE762F1E43AD7');
        $this->addSql('ALTER TABLE watchlist_domains DROP CONSTRAINT FK_196DE762AF923913');

        $this->addSql('ALTER TABLE watchlist_domains RENAME COLUMN watchlist_token TO watch_list_token');

        $this->addSql('ALTER TABLE watchlist_domains RENAME TO watch_lists_domains');
        $this->addSql('ALTER INDEX IDX_196DE762AF923913 RENAME TO idx_f693e1d0af923913');
        $this->addSql('ALTER INDEX IDX_196DE762F1E43AD7 RENAME TO idx_f693e1d0d52d7aa6');

        $this->addSql('COMMENT ON COLUMN watch_lists_domains.watch_list_token IS \'(DC2Type:uuid)\'');

        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT fk_f693e1d0af923913 FOREIGN KEY (domain_ldh_name) REFERENCES domain (ldh_name) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watch_lists_domains ADD CONSTRAINT fk_f693e1d0d52d7aa6 FOREIGN KEY (watch_list_token) REFERENCES watchlist (token) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
