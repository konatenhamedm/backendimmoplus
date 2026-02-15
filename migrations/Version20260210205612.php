<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210205612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE _admin_param_config_app (id INT AUTO_INCREMENT NOT NULL, logo_id INT DEFAULT NULL, favicon_id INT DEFAULT NULL, image_login_id INT DEFAULT NULL, logo_login_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, main_color_admin VARCHAR(255) NOT NULL, default_color_admin VARCHAR(255) NOT NULL, main_color_login VARCHAR(255) NOT NULL, default_color_login VARCHAR(255) NOT NULL, INDEX IDX_EE0159A1F98F144A (logo_id), INDEX IDX_EE0159A1D78119FD (favicon_id), INDEX IDX_EE0159A1D3426EF5 (image_login_id), INDEX IDX_EE0159A1C83BB8B (logo_login_id), INDEX IDX_EE0159A1A4AEAFEA (entreprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE versmt_proprio (id INT AUTO_INCREMENT NOT NULL, proprio_id INT DEFAULT NULL, type_versement_id INT DEFAULT NULL, locataire_id INT DEFAULT NULL, maison_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, date_versement DATETIME NOT NULL, montant NUMERIC(9, 0) NOT NULL, numero VARCHAR(255) NOT NULL, INDEX IDX_9EB3136B6B82600 (proprio_id), INDEX IDX_9EB3136B5CFC3767 (type_versement_id), INDEX IDX_9EB3136BD8A38199 (locataire_id), INDEX IDX_9EB3136B9D67D8AF (maison_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE _admin_param_config_app ADD CONSTRAINT FK_EE0159A1F98F144A FOREIGN KEY (logo_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _admin_param_config_app ADD CONSTRAINT FK_EE0159A1D78119FD FOREIGN KEY (favicon_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _admin_param_config_app ADD CONSTRAINT FK_EE0159A1D3426EF5 FOREIGN KEY (image_login_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _admin_param_config_app ADD CONSTRAINT FK_EE0159A1C83BB8B FOREIGN KEY (logo_login_id) REFERENCES param_fichier (id)');
        $this->addSql('ALTER TABLE _admin_param_config_app ADD CONSTRAINT FK_EE0159A1A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE versmt_proprio ADD CONSTRAINT FK_9EB3136B6B82600 FOREIGN KEY (proprio_id) REFERENCES proprio (id)');
        $this->addSql('ALTER TABLE versmt_proprio ADD CONSTRAINT FK_9EB3136B5CFC3767 FOREIGN KEY (type_versement_id) REFERENCES type_versements (id)');
        $this->addSql('ALTER TABLE versmt_proprio ADD CONSTRAINT FK_9EB3136BD8A38199 FOREIGN KEY (locataire_id) REFERENCES locataire (id)');
        $this->addSql('ALTER TABLE versmt_proprio ADD CONSTRAINT FK_9EB3136B9D67D8AF FOREIGN KEY (maison_id) REFERENCES maison (id)');
        $this->addSql('ALTER TABLE appartement CHANGE lib_appart libelle_appartement VARCHAR(255) NOT NULL, CHANGE nbre_pieces nombre_pieces NUMERIC(9, 0) NOT NULL, CHANGE num_etage numero_etage INT NOT NULL, CHANGE oqp est_occupe INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat_location ADD montant_caution NUMERIC(9, 0) DEFAULT NULL, ADD montant_avance NUMERIC(9, 0) DEFAULT NULL, ADD montant_loyer NUMERIC(9, 0) DEFAULT NULL, ADD autres_infos VARCHAR(255) DEFAULT NULL, ADD montant_loyer_precedent NUMERIC(9, 0) DEFAULT NULL, ADD montant_loyer_initial NUMERIC(9, 0) DEFAULT NULL, ADD montant_loyer_actuel NUMERIC(9, 0) DEFAULT NULL, ADD montant_arriere NUMERIC(9, 0) DEFAULT NULL, ADD statut_location VARCHAR(255) DEFAULT NULL, ADD total_verse VARCHAR(255) DEFAULT NULL, DROP mnt_caution, DROP mnt_avance, DROP mnt_loyer, DROP autre_infos, DROP mnt_loyer_prec, DROP mnt_loyer_ini, DROP mnt_loyer_actu, DROP mnt_arriere, DROP statut_loc, DROP tot_verse, CHANGE nb_mois_caution nombre_mois_caution NUMERIC(9, 1) NOT NULL, CHANGE nb_mois_avance nombre_mois_avance NUMERIC(10, 1) DEFAULT NULL, CHANGE date_proch_vers date_prochain_versement DATETIME DEFAULT NULL, CHANGE fraisanex frais_annexes NUMERIC(9, 0) NOT NULL');
        $this->addSql('ALTER TABLE locataire ADD nom_prenoms VARCHAR(255) NOT NULL, ADD lieu_naissance VARCHAR(255) NOT NULL, ADD nombre_enfants VARCHAR(255) DEFAULT NULL, ADD nombre_personnes_charge VARCHAR(255) DEFAULT NULL, ADD nom_prenom_conjointe VARCHAR(255) DEFAULT NULL, ADD profession_conjointe VARCHAR(255) DEFAULT NULL, ADD ethnie_conjointe VARCHAR(255) DEFAULT NULL, ADD contact_conjointe VARCHAR(255) DEFAULT NULL, ADD numero_piece VARCHAR(255) NOT NULL, DROP nprenoms, DROP lieu_naiss, DROP nb_enfts, DROP nb_pers_chge, DROP npconjointe, DROP prof_conj, DROP ethnie_conj, DROP contact_conj, DROP numpiece, CHANGE date_naiss date_naissance DATETIME NOT NULL');
        $this->addSql('ALTER TABLE maison CHANGE lib_maison libelle_maison VARCHAR(255) NOT NULL, CHANGE tfoncier titre_foncier VARCHAR(255) DEFAULT NULL, CHANGE mnt_com montant_commission NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE proprio ADD adresse VARCHAR(255) NOT NULL, ADD numero_cni VARCHAR(255) NOT NULL, ADD whatsapp VARCHAR(255) DEFAULT NULL, ADD lieu_naissance VARCHAR(255) NOT NULL, ADD profession VARCHAR(255) NOT NULL, ADD representant_nom_prenoms VARCHAR(255) DEFAULT NULL, ADD representant_contacts VARCHAR(255) DEFAULT NULL, ADD representant_email VARCHAR(255) DEFAULT NULL, ADD representant_adresse VARCHAR(255) DEFAULT NULL, ADD representant_nom_pere VARCHAR(255) DEFAULT NULL, ADD representant_nom_mere VARCHAR(255) DEFAULT NULL, ADD representant_whatsapp VARCHAR(255) DEFAULT NULL, ADD representant_date_naissance DATETIME DEFAULT NULL, ADD representant_lieu_naissance VARCHAR(255) DEFAULT NULL, ADD representant_profession VARCHAR(255) DEFAULT NULL, ADD representant_date_cni DATETIME DEFAULT NULL, ADD representant_numero_cni VARCHAR(255) DEFAULT NULL, DROP addresse, DROP num_cni, DROP whats_app, DROP lieu_naiss, DROP prefession, DROP nom_prenoms_r, DROP contacts_r, DROP email_r, DROP adresse_r, DROP nom_prere_r, DROP nom_mere_r, DROP whats_app_r, DROP date_naiss_r, DROP lieu_naiss_r, DROP profession_r, DROP date_cni_r, DROP num_cni_r, CHANGE date_naiss date_naissance DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE _admin_param_config_app DROP FOREIGN KEY FK_EE0159A1F98F144A');
        $this->addSql('ALTER TABLE _admin_param_config_app DROP FOREIGN KEY FK_EE0159A1D78119FD');
        $this->addSql('ALTER TABLE _admin_param_config_app DROP FOREIGN KEY FK_EE0159A1D3426EF5');
        $this->addSql('ALTER TABLE _admin_param_config_app DROP FOREIGN KEY FK_EE0159A1C83BB8B');
        $this->addSql('ALTER TABLE _admin_param_config_app DROP FOREIGN KEY FK_EE0159A1A4AEAFEA');
        $this->addSql('ALTER TABLE versmt_proprio DROP FOREIGN KEY FK_9EB3136B6B82600');
        $this->addSql('ALTER TABLE versmt_proprio DROP FOREIGN KEY FK_9EB3136B5CFC3767');
        $this->addSql('ALTER TABLE versmt_proprio DROP FOREIGN KEY FK_9EB3136BD8A38199');
        $this->addSql('ALTER TABLE versmt_proprio DROP FOREIGN KEY FK_9EB3136B9D67D8AF');
        $this->addSql('DROP TABLE _admin_param_config_app');
        $this->addSql('DROP TABLE versmt_proprio');
        $this->addSql('ALTER TABLE appartement CHANGE libelle_appartement lib_appart VARCHAR(255) NOT NULL, CHANGE nombre_pieces nbre_pieces NUMERIC(9, 0) NOT NULL, CHANGE numero_etage num_etage INT NOT NULL, CHANGE est_occupe oqp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat_location ADD mnt_caution NUMERIC(9, 0) DEFAULT NULL, ADD mnt_avance NUMERIC(9, 0) DEFAULT NULL, ADD mnt_loyer NUMERIC(9, 0) DEFAULT NULL, ADD autre_infos VARCHAR(255) DEFAULT NULL, ADD mnt_loyer_prec NUMERIC(9, 0) DEFAULT NULL, ADD mnt_loyer_ini NUMERIC(9, 0) DEFAULT NULL, ADD mnt_loyer_actu NUMERIC(9, 0) DEFAULT NULL, ADD mnt_arriere NUMERIC(9, 0) DEFAULT NULL, ADD statut_loc VARCHAR(255) DEFAULT NULL, ADD tot_verse VARCHAR(255) DEFAULT NULL, DROP montant_caution, DROP montant_avance, DROP montant_loyer, DROP autres_infos, DROP montant_loyer_precedent, DROP montant_loyer_initial, DROP montant_loyer_actuel, DROP montant_arriere, DROP statut_location, DROP total_verse, CHANGE nombre_mois_caution nb_mois_caution NUMERIC(9, 1) NOT NULL, CHANGE nombre_mois_avance nb_mois_avance NUMERIC(10, 1) DEFAULT NULL, CHANGE date_prochain_versement date_proch_vers DATETIME DEFAULT NULL, CHANGE frais_annexes fraisanex NUMERIC(9, 0) NOT NULL');
        $this->addSql('ALTER TABLE locataire ADD nprenoms VARCHAR(255) NOT NULL, ADD lieu_naiss VARCHAR(255) NOT NULL, ADD nb_enfts VARCHAR(255) DEFAULT NULL, ADD nb_pers_chge VARCHAR(255) DEFAULT NULL, ADD npconjointe VARCHAR(255) DEFAULT NULL, ADD prof_conj VARCHAR(255) DEFAULT NULL, ADD ethnie_conj VARCHAR(255) DEFAULT NULL, ADD contact_conj VARCHAR(255) DEFAULT NULL, ADD numpiece VARCHAR(255) NOT NULL, DROP nom_prenoms, DROP lieu_naissance, DROP nombre_enfants, DROP nombre_personnes_charge, DROP nom_prenom_conjointe, DROP profession_conjointe, DROP ethnie_conjointe, DROP contact_conjointe, DROP numero_piece, CHANGE date_naissance date_naiss DATETIME NOT NULL');
        $this->addSql('ALTER TABLE maison CHANGE libelle_maison lib_maison VARCHAR(255) NOT NULL, CHANGE titre_foncier tfoncier VARCHAR(255) DEFAULT NULL, CHANGE montant_commission mnt_com NUMERIC(10, 0) NOT NULL');
        $this->addSql('ALTER TABLE proprio ADD addresse VARCHAR(255) NOT NULL, ADD num_cni VARCHAR(255) NOT NULL, ADD whats_app VARCHAR(255) DEFAULT NULL, ADD lieu_naiss VARCHAR(255) NOT NULL, ADD prefession VARCHAR(255) NOT NULL, ADD nom_prenoms_r VARCHAR(255) DEFAULT NULL, ADD contacts_r VARCHAR(255) DEFAULT NULL, ADD email_r VARCHAR(255) DEFAULT NULL, ADD adresse_r VARCHAR(255) DEFAULT NULL, ADD nom_prere_r VARCHAR(255) DEFAULT NULL, ADD nom_mere_r VARCHAR(255) DEFAULT NULL, ADD whats_app_r VARCHAR(255) DEFAULT NULL, ADD date_naiss_r DATETIME DEFAULT NULL, ADD lieu_naiss_r VARCHAR(255) DEFAULT NULL, ADD profession_r VARCHAR(255) DEFAULT NULL, ADD date_cni_r DATETIME DEFAULT NULL, ADD num_cni_r VARCHAR(255) DEFAULT NULL, DROP adresse, DROP numero_cni, DROP whatsapp, DROP lieu_naissance, DROP profession, DROP representant_nom_prenoms, DROP representant_contacts, DROP representant_email, DROP representant_adresse, DROP representant_nom_pere, DROP representant_nom_mere, DROP representant_whatsapp, DROP representant_date_naissance, DROP representant_lieu_naissance, DROP representant_profession, DROP representant_date_cni, DROP representant_numero_cni, CHANGE date_naissance date_naiss DATETIME NOT NULL');
    }
}
