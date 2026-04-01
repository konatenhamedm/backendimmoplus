<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401160711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE _depense_residence (id INT AUTO_INCREMENT NOT NULL, residence_id INT NOT NULL, justificatif_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, montant INT NOT NULL, date_depense DATE NOT NULL, categorie VARCHAR(50) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_63EE723D8B225FBD (residence_id), INDEX IDX_63EE723D4B85A991 (justificatif_id), INDEX IDX_63EE723DB03A8386 (created_by_id), INDEX IDX_63EE723D896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _loyer_residence (id INT AUTO_INCREMENT NOT NULL, residence_id INT NOT NULL, scan_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, montant INT NOT NULL, date_paiement DATE NOT NULL, periode_label VARCHAR(100) DEFAULT NULL, periodicite VARCHAR(20) DEFAULT NULL, etat VARCHAR(20) DEFAULT \'EN_ATTENTE\' NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F16524DD8B225FBD (residence_id), INDEX IDX_F16524DD2827AAD3 (scan_id), INDEX IDX_F16524DDB03A8386 (created_by_id), INDEX IDX_F16524DD896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _reservation_residence (id INT AUTO_INCREMENT NOT NULL, residence_id INT NOT NULL, carte_identite_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, nom_locataire VARCHAR(100) NOT NULL, prenom_locataire VARCHAR(100) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, email VARCHAR(150) DEFAULT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, montant INT NOT NULL, etat VARCHAR(20) DEFAULT \'EN_ATTENTE\' NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4F6E3C118B225FBD (residence_id), INDEX IDX_4F6E3C11F0E59711 (carte_identite_id), INDEX IDX_4F6E3C11B03A8386 (created_by_id), INDEX IDX_4F6E3C11896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE _residence (id INT AUTO_INCREMENT NOT NULL, agence_id INT DEFAULT NULL, photo_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, montant_location INT NOT NULL, nombre_piece INT DEFAULT NULL, etat VARCHAR(50) DEFAULT \'DISPONIBLE\' NOT NULL, charge_loyer TINYINT(1) DEFAULT 0 NOT NULL, montant_loyer INT DEFAULT NULL, periodicite_loyer VARCHAR(20) DEFAULT NULL, nom_proprietaire VARCHAR(150) DEFAULT NULL, telephone_proprietaire VARCHAR(30) DEFAULT NULL, email_proprietaire VARCHAR(150) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4EBBF93ED725330D (agence_id), INDEX IDX_4EBBF93E7E9E4C8C (photo_id), INDEX IDX_4EBBF93EA4AEAFEA (entreprise_id), INDEX IDX_4EBBF93EB03A8386 (created_by_id), INDEX IDX_4EBBF93E896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE _depense_residence ADD CONSTRAINT FK_63EE723D8B225FBD FOREIGN KEY (residence_id) REFERENCES _residence (id)');
        $this->addSql('ALTER TABLE _depense_residence ADD CONSTRAINT FK_63EE723D4B85A991 FOREIGN KEY (justificatif_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _depense_residence ADD CONSTRAINT FK_63EE723DB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _depense_residence ADD CONSTRAINT FK_63EE723D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _loyer_residence ADD CONSTRAINT FK_F16524DD8B225FBD FOREIGN KEY (residence_id) REFERENCES _residence (id)');
        $this->addSql('ALTER TABLE _loyer_residence ADD CONSTRAINT FK_F16524DD2827AAD3 FOREIGN KEY (scan_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _loyer_residence ADD CONSTRAINT FK_F16524DDB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _loyer_residence ADD CONSTRAINT FK_F16524DD896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _reservation_residence ADD CONSTRAINT FK_4F6E3C118B225FBD FOREIGN KEY (residence_id) REFERENCES _residence (id)');
        $this->addSql('ALTER TABLE _reservation_residence ADD CONSTRAINT FK_4F6E3C11F0E59711 FOREIGN KEY (carte_identite_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _reservation_residence ADD CONSTRAINT FK_4F6E3C11B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _reservation_residence ADD CONSTRAINT FK_4F6E3C11896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _residence ADD CONSTRAINT FK_4EBBF93ED725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('ALTER TABLE _residence ADD CONSTRAINT FK_4EBBF93E7E9E4C8C FOREIGN KEY (photo_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _residence ADD CONSTRAINT FK_4EBBF93EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE _residence ADD CONSTRAINT FK_4EBBF93EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _residence ADD CONSTRAINT FK_4EBBF93E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE module_abonnement ADD max_residences INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE _depense_residence DROP FOREIGN KEY FK_63EE723D8B225FBD');
        $this->addSql('ALTER TABLE _depense_residence DROP FOREIGN KEY FK_63EE723D4B85A991');
        $this->addSql('ALTER TABLE _depense_residence DROP FOREIGN KEY FK_63EE723DB03A8386');
        $this->addSql('ALTER TABLE _depense_residence DROP FOREIGN KEY FK_63EE723D896DBBDE');
        $this->addSql('ALTER TABLE _loyer_residence DROP FOREIGN KEY FK_F16524DD8B225FBD');
        $this->addSql('ALTER TABLE _loyer_residence DROP FOREIGN KEY FK_F16524DD2827AAD3');
        $this->addSql('ALTER TABLE _loyer_residence DROP FOREIGN KEY FK_F16524DDB03A8386');
        $this->addSql('ALTER TABLE _loyer_residence DROP FOREIGN KEY FK_F16524DD896DBBDE');
        $this->addSql('ALTER TABLE _reservation_residence DROP FOREIGN KEY FK_4F6E3C118B225FBD');
        $this->addSql('ALTER TABLE _reservation_residence DROP FOREIGN KEY FK_4F6E3C11F0E59711');
        $this->addSql('ALTER TABLE _reservation_residence DROP FOREIGN KEY FK_4F6E3C11B03A8386');
        $this->addSql('ALTER TABLE _reservation_residence DROP FOREIGN KEY FK_4F6E3C11896DBBDE');
        $this->addSql('ALTER TABLE _residence DROP FOREIGN KEY FK_4EBBF93ED725330D');
        $this->addSql('ALTER TABLE _residence DROP FOREIGN KEY FK_4EBBF93E7E9E4C8C');
        $this->addSql('ALTER TABLE _residence DROP FOREIGN KEY FK_4EBBF93EA4AEAFEA');
        $this->addSql('ALTER TABLE _residence DROP FOREIGN KEY FK_4EBBF93EB03A8386');
        $this->addSql('ALTER TABLE _residence DROP FOREIGN KEY FK_4EBBF93E896DBBDE');
        $this->addSql('DROP TABLE _depense_residence');
        $this->addSql('DROP TABLE _loyer_residence');
        $this->addSql('DROP TABLE _reservation_residence');
        $this->addSql('DROP TABLE _residence');
        $this->addSql('ALTER TABLE module_abonnement DROP max_residences');
    }
}
