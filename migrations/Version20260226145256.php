<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226145256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE charge_appartement (id INT AUTO_INCREMENT NOT NULL, charge_proprio_id INT NOT NULL, appartement_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, montant NUMERIC(15, 2) NOT NULL, details LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_6852307C9E03A696 (charge_proprio_id), INDEX IDX_6852307CE1729BBA (appartement_id), INDEX IDX_6852307CB03A8386 (created_by_id), INDEX IDX_6852307C896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE charge_proprio (id INT AUTO_INCREMENT NOT NULL, proprio_id INT NOT NULL, maison_id INT DEFAULT NULL, scan_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, montant NUMERIC(12, 2) NOT NULL, date_charge DATETIME NOT NULL, details LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_2D1F07236B82600 (proprio_id), INDEX IDX_2D1F07239D67D8AF (maison_id), INDEX IDX_2D1F07232827AAD3 (scan_id), INDEX IDX_2D1F0723A4AEAFEA (entreprise_id), INDEX IDX_2D1F0723B03A8386 (created_by_id), INDEX IDX_2D1F0723896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE charge_appartement ADD CONSTRAINT FK_6852307C9E03A696 FOREIGN KEY (charge_proprio_id) REFERENCES charge_proprio (id)');
        $this->addSql('ALTER TABLE charge_appartement ADD CONSTRAINT FK_6852307CE1729BBA FOREIGN KEY (appartement_id) REFERENCES appartement (id)');
        $this->addSql('ALTER TABLE charge_appartement ADD CONSTRAINT FK_6852307CB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE charge_appartement ADD CONSTRAINT FK_6852307C896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE charge_proprio ADD CONSTRAINT FK_2D1F07236B82600 FOREIGN KEY (proprio_id) REFERENCES proprio (id)');
        $this->addSql('ALTER TABLE charge_proprio ADD CONSTRAINT FK_2D1F07239D67D8AF FOREIGN KEY (maison_id) REFERENCES maison (id)');
        $this->addSql('ALTER TABLE charge_proprio ADD CONSTRAINT FK_2D1F07232827AAD3 FOREIGN KEY (scan_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE charge_proprio ADD CONSTRAINT FK_2D1F0723A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE charge_proprio ADD CONSTRAINT FK_2D1F0723B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE charge_proprio ADD CONSTRAINT FK_2D1F0723896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE charge_appartement DROP FOREIGN KEY FK_6852307C9E03A696');
        $this->addSql('ALTER TABLE charge_appartement DROP FOREIGN KEY FK_6852307CE1729BBA');
        $this->addSql('ALTER TABLE charge_appartement DROP FOREIGN KEY FK_6852307CB03A8386');
        $this->addSql('ALTER TABLE charge_appartement DROP FOREIGN KEY FK_6852307C896DBBDE');
        $this->addSql('ALTER TABLE charge_proprio DROP FOREIGN KEY FK_2D1F07236B82600');
        $this->addSql('ALTER TABLE charge_proprio DROP FOREIGN KEY FK_2D1F07239D67D8AF');
        $this->addSql('ALTER TABLE charge_proprio DROP FOREIGN KEY FK_2D1F07232827AAD3');
        $this->addSql('ALTER TABLE charge_proprio DROP FOREIGN KEY FK_2D1F0723A4AEAFEA');
        $this->addSql('ALTER TABLE charge_proprio DROP FOREIGN KEY FK_2D1F0723B03A8386');
        $this->addSql('ALTER TABLE charge_proprio DROP FOREIGN KEY FK_2D1F0723896DBBDE');
        $this->addSql('DROP TABLE charge_appartement');
        $this->addSql('DROP TABLE charge_proprio');
    }
}
