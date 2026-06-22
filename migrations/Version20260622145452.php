<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260622145452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_terrain (id INT AUTO_INCREMENT NOT NULL, agence_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenoms VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, piece_identite VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_9A4B17CCD725330D (agence_id), INDEX IDX_9A4B17CCA4AEAFEA (entreprise_id), INDEX IDX_9A4B17CCB03A8386 (created_by_id), INDEX IDX_9A4B17CC896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demarche_administrative (id INT AUTO_INCREMENT NOT NULL, vente_terrain_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, statut_global VARCHAR(255) NOT NULL, frais_estimes VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_B26F228E1EEE77B6 (vente_terrain_id), INDEX IDX_B26F228EB03A8386 (created_by_id), INDEX IDX_B26F228E896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document_vente_terrain (id INT AUTO_INCREMENT NOT NULL, fichier_id INT NOT NULL, vente_terrain_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_B0D36BDAF915CFE (fichier_id), INDEX IDX_B0D36BDA1EEE77B6 (vente_terrain_id), INDEX IDX_B0D36BDAB03A8386 (created_by_id), INDEX IDX_B0D36BDA896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE echancier_terrain (id INT AUTO_INCREMENT NOT NULL, vente_terrain_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, date_prevue DATETIME NOT NULL, montant VARCHAR(255) NOT NULL, etat VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_728F1D181EEE77B6 (vente_terrain_id), INDEX IDX_728F1D18B03A8386 (created_by_id), INDEX IDX_728F1D18896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE etape_demarche (id INT AUTO_INCREMENT NOT NULL, demarche_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, nom_etape VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, date_validation DATETIME DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_F2345BFAC404C6DD (demarche_id), INDEX IDX_F2345BFAB03A8386 (created_by_id), INDEX IDX_F2345BFA896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vente_terrain (id INT AUTO_INCREMENT NOT NULL, terrain_id INT NOT NULL, client_id INT NOT NULL, agence_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, prix_vente VARCHAR(255) NOT NULL, apport_initial VARCHAR(255) NOT NULL, reste_apayer VARCHAR(255) NOT NULL, date_vente DATETIME NOT NULL, etat VARCHAR(255) NOT NULL, type_vente VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_977CCF518A2D8B41 (terrain_id), INDEX IDX_977CCF5119EB6921 (client_id), INDEX IDX_977CCF51D725330D (agence_id), INDEX IDX_977CCF51A4AEAFEA (entreprise_id), INDEX IDX_977CCF51B03A8386 (created_by_id), INDEX IDX_977CCF51896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE versement_terrain (id INT AUTO_INCREMENT NOT NULL, vente_terrain_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, montant VARCHAR(255) NOT NULL, date_versement DATETIME NOT NULL, mode_paiement VARCHAR(255) DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_4E7CD09A1EEE77B6 (vente_terrain_id), INDEX IDX_4E7CD09AB03A8386 (created_by_id), INDEX IDX_4E7CD09A896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_terrain ADD CONSTRAINT FK_9A4B17CCD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('ALTER TABLE client_terrain ADD CONSTRAINT FK_9A4B17CCA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE client_terrain ADD CONSTRAINT FK_9A4B17CCB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE client_terrain ADD CONSTRAINT FK_9A4B17CC896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE demarche_administrative ADD CONSTRAINT FK_B26F228E1EEE77B6 FOREIGN KEY (vente_terrain_id) REFERENCES vente_terrain (id)');
        $this->addSql('ALTER TABLE demarche_administrative ADD CONSTRAINT FK_B26F228EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE demarche_administrative ADD CONSTRAINT FK_B26F228E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE document_vente_terrain ADD CONSTRAINT FK_B0D36BDAF915CFE FOREIGN KEY (fichier_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE document_vente_terrain ADD CONSTRAINT FK_B0D36BDA1EEE77B6 FOREIGN KEY (vente_terrain_id) REFERENCES vente_terrain (id)');
        $this->addSql('ALTER TABLE document_vente_terrain ADD CONSTRAINT FK_B0D36BDAB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE document_vente_terrain ADD CONSTRAINT FK_B0D36BDA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE echancier_terrain ADD CONSTRAINT FK_728F1D181EEE77B6 FOREIGN KEY (vente_terrain_id) REFERENCES vente_terrain (id)');
        $this->addSql('ALTER TABLE echancier_terrain ADD CONSTRAINT FK_728F1D18B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE echancier_terrain ADD CONSTRAINT FK_728F1D18896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE etape_demarche ADD CONSTRAINT FK_F2345BFAC404C6DD FOREIGN KEY (demarche_id) REFERENCES demarche_administrative (id)');
        $this->addSql('ALTER TABLE etape_demarche ADD CONSTRAINT FK_F2345BFAB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE etape_demarche ADD CONSTRAINT FK_F2345BFA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE vente_terrain ADD CONSTRAINT FK_977CCF518A2D8B41 FOREIGN KEY (terrain_id) REFERENCES terrain (id)');
        $this->addSql('ALTER TABLE vente_terrain ADD CONSTRAINT FK_977CCF5119EB6921 FOREIGN KEY (client_id) REFERENCES client_terrain (id)');
        $this->addSql('ALTER TABLE vente_terrain ADD CONSTRAINT FK_977CCF51D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('ALTER TABLE vente_terrain ADD CONSTRAINT FK_977CCF51A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE vente_terrain ADD CONSTRAINT FK_977CCF51B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE vente_terrain ADD CONSTRAINT FK_977CCF51896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE versement_terrain ADD CONSTRAINT FK_4E7CD09A1EEE77B6 FOREIGN KEY (vente_terrain_id) REFERENCES vente_terrain (id)');
        $this->addSql('ALTER TABLE versement_terrain ADD CONSTRAINT FK_4E7CD09AB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE versement_terrain ADD CONSTRAINT FK_4E7CD09A896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE contrat_location ADD is_avance_consommee TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE locataire ADD telWhatsapp VARCHAR(255) DEFAULT NULL, ADD employeur VARCHAR(255) DEFAULT NULL, ADD ressourcesExactes VARCHAR(255) DEFAULT NULL, ADD animalCompagnie VARCHAR(255) DEFAULT NULL, ADD emailConjoint VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE module_abonnement ADD montant_reel VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD agence_id INT DEFAULT NULL, ADD entreprise_id INT DEFAULT NULL, ADD plan_lotissement_id INT DEFAULT NULL, ADD superficie_totale VARCHAR(255) DEFAULT NULL, ADD situation_geographique LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4484724C9 FOREIGN KEY (plan_lotissement_id) REFERENCES param_fichier (id)');
        $this->addSql('CREATE INDEX IDX_694309E4D725330D ON site (agence_id)');
        $this->addSql('CREATE INDEX IDX_694309E4A4AEAFEA ON site (entreprise_id)');
        $this->addSql('CREATE INDEX IDX_694309E4484724C9 ON site (plan_lotissement_id)');
        $this->addSql('ALTER TABLE terrain ADD agence_id INT DEFAULT NULL, ADD entreprise_id INT DEFAULT NULL, ADD plan_topographique_id INT DEFAULT NULL, ADD dimensions VARCHAR(255) DEFAULT NULL, DROP nomcl, DROP telcl, DROP localisation_client');
        $this->addSql('ALTER TABLE terrain ADD CONSTRAINT FK_C87653B1D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('ALTER TABLE terrain ADD CONSTRAINT FK_C87653B1A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE terrain ADD CONSTRAINT FK_C87653B1C87D6249 FOREIGN KEY (plan_topographique_id) REFERENCES param_fichier (id)');
        $this->addSql('CREATE INDEX IDX_C87653B1D725330D ON terrain (agence_id)');
        $this->addSql('CREATE INDEX IDX_C87653B1A4AEAFEA ON terrain (entreprise_id)');
        $this->addSql('CREATE INDEX IDX_C87653B1C87D6249 ON terrain (plan_topographique_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_terrain DROP FOREIGN KEY FK_9A4B17CCD725330D');
        $this->addSql('ALTER TABLE client_terrain DROP FOREIGN KEY FK_9A4B17CCA4AEAFEA');
        $this->addSql('ALTER TABLE client_terrain DROP FOREIGN KEY FK_9A4B17CCB03A8386');
        $this->addSql('ALTER TABLE client_terrain DROP FOREIGN KEY FK_9A4B17CC896DBBDE');
        $this->addSql('ALTER TABLE demarche_administrative DROP FOREIGN KEY FK_B26F228E1EEE77B6');
        $this->addSql('ALTER TABLE demarche_administrative DROP FOREIGN KEY FK_B26F228EB03A8386');
        $this->addSql('ALTER TABLE demarche_administrative DROP FOREIGN KEY FK_B26F228E896DBBDE');
        $this->addSql('ALTER TABLE document_vente_terrain DROP FOREIGN KEY FK_B0D36BDAF915CFE');
        $this->addSql('ALTER TABLE document_vente_terrain DROP FOREIGN KEY FK_B0D36BDA1EEE77B6');
        $this->addSql('ALTER TABLE document_vente_terrain DROP FOREIGN KEY FK_B0D36BDAB03A8386');
        $this->addSql('ALTER TABLE document_vente_terrain DROP FOREIGN KEY FK_B0D36BDA896DBBDE');
        $this->addSql('ALTER TABLE echancier_terrain DROP FOREIGN KEY FK_728F1D181EEE77B6');
        $this->addSql('ALTER TABLE echancier_terrain DROP FOREIGN KEY FK_728F1D18B03A8386');
        $this->addSql('ALTER TABLE echancier_terrain DROP FOREIGN KEY FK_728F1D18896DBBDE');
        $this->addSql('ALTER TABLE etape_demarche DROP FOREIGN KEY FK_F2345BFAC404C6DD');
        $this->addSql('ALTER TABLE etape_demarche DROP FOREIGN KEY FK_F2345BFAB03A8386');
        $this->addSql('ALTER TABLE etape_demarche DROP FOREIGN KEY FK_F2345BFA896DBBDE');
        $this->addSql('ALTER TABLE vente_terrain DROP FOREIGN KEY FK_977CCF518A2D8B41');
        $this->addSql('ALTER TABLE vente_terrain DROP FOREIGN KEY FK_977CCF5119EB6921');
        $this->addSql('ALTER TABLE vente_terrain DROP FOREIGN KEY FK_977CCF51D725330D');
        $this->addSql('ALTER TABLE vente_terrain DROP FOREIGN KEY FK_977CCF51A4AEAFEA');
        $this->addSql('ALTER TABLE vente_terrain DROP FOREIGN KEY FK_977CCF51B03A8386');
        $this->addSql('ALTER TABLE vente_terrain DROP FOREIGN KEY FK_977CCF51896DBBDE');
        $this->addSql('ALTER TABLE versement_terrain DROP FOREIGN KEY FK_4E7CD09A1EEE77B6');
        $this->addSql('ALTER TABLE versement_terrain DROP FOREIGN KEY FK_4E7CD09AB03A8386');
        $this->addSql('ALTER TABLE versement_terrain DROP FOREIGN KEY FK_4E7CD09A896DBBDE');
        $this->addSql('DROP TABLE client_terrain');
        $this->addSql('DROP TABLE demarche_administrative');
        $this->addSql('DROP TABLE document_vente_terrain');
        $this->addSql('DROP TABLE echancier_terrain');
        $this->addSql('DROP TABLE etape_demarche');
        $this->addSql('DROP TABLE vente_terrain');
        $this->addSql('DROP TABLE versement_terrain');
        $this->addSql('ALTER TABLE terrain DROP FOREIGN KEY FK_C87653B1D725330D');
        $this->addSql('ALTER TABLE terrain DROP FOREIGN KEY FK_C87653B1A4AEAFEA');
        $this->addSql('ALTER TABLE terrain DROP FOREIGN KEY FK_C87653B1C87D6249');
        $this->addSql('DROP INDEX IDX_C87653B1D725330D ON terrain');
        $this->addSql('DROP INDEX IDX_C87653B1A4AEAFEA ON terrain');
        $this->addSql('DROP INDEX IDX_C87653B1C87D6249 ON terrain');
        $this->addSql('ALTER TABLE terrain ADD telcl VARCHAR(255) DEFAULT NULL, ADD localisation_client VARCHAR(255) DEFAULT NULL, DROP agence_id, DROP entreprise_id, DROP plan_topographique_id, CHANGE dimensions nomcl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4D725330D');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4A4AEAFEA');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4484724C9');
        $this->addSql('DROP INDEX IDX_694309E4D725330D ON site');
        $this->addSql('DROP INDEX IDX_694309E4A4AEAFEA ON site');
        $this->addSql('DROP INDEX IDX_694309E4484724C9 ON site');
        $this->addSql('ALTER TABLE site DROP agence_id, DROP entreprise_id, DROP plan_lotissement_id, DROP superficie_totale, DROP situation_geographique');
        $this->addSql('ALTER TABLE locataire DROP telWhatsapp, DROP employeur, DROP ressourcesExactes, DROP animalCompagnie, DROP emailConjoint');
        $this->addSql('ALTER TABLE contrat_location DROP is_avance_consommee');
        $this->addSql('ALTER TABLE module_abonnement DROP montant_reel');
    }
}
