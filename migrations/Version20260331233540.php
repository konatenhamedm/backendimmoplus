<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331233540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE depenses ADD scan_id INT DEFAULT NULL, DROP scan');
        $this->addSql('ALTER TABLE depenses ADD CONSTRAINT FK_EE350ECB2827AAD3 FOREIGN KEY (scan_id) REFERENCES param_fichier (id)');
        $this->addSql('CREATE INDEX IDX_EE350ECB2827AAD3 ON depenses (scan_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE depenses DROP FOREIGN KEY FK_EE350ECB2827AAD3');
        $this->addSql('DROP INDEX IDX_EE350ECB2827AAD3 ON depenses');
        $this->addSql('ALTER TABLE depenses ADD scan VARCHAR(255) DEFAULT NULL, DROP scan_id');
    }
}
