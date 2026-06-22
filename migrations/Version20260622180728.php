<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260622180728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site ADD pays_id INT DEFAULT NULL, ADD ville_id INT DEFAULT NULL, ADD latitude VARCHAR(255) DEFAULT NULL, ADD longitude VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id)');
        $this->addSql('CREATE INDEX IDX_694309E4A6E44244 ON site (pays_id)');
        $this->addSql('CREATE INDEX IDX_694309E4A73F0036 ON site (ville_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4A6E44244');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4A73F0036');
        $this->addSql('DROP INDEX IDX_694309E4A6E44244 ON site');
        $this->addSql('DROP INDEX IDX_694309E4A73F0036 ON site');
        $this->addSql('ALTER TABLE site DROP pays_id, DROP ville_id, DROP latitude, DROP longitude');
    }
}
