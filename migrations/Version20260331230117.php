<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331230117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE planning_recouvrement (id INT AUTO_INCREMENT NOT NULL, locataire_id INT DEFAULT NULL, agent_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, details LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, status VARCHAR(50) NOT NULL, location VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_6EA12BE3D8A38199 (locataire_id), INDEX IDX_6EA12BE33414710B (agent_id), INDEX IDX_6EA12BE3A4AEAFEA (entreprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE relance (id INT AUTO_INCREMENT NOT NULL, facture_id INT NOT NULL, agent_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, agence_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, observation LONGTEXT DEFAULT NULL, date_effective DATETIME NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_50BBC1267F2DEE08 (facture_id), INDEX IDX_50BBC1263414710B (agent_id), INDEX IDX_50BBC126A4AEAFEA (entreprise_id), INDEX IDX_50BBC126D725330D (agence_id), INDEX IDX_50BBC126B03A8386 (created_by_id), INDEX IDX_50BBC126896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE suivi_contact (id INT AUTO_INCREMENT NOT NULL, locataire_id INT NOT NULL, agent_id INT NOT NULL, entreprise_id INT DEFAULT NULL, type_contact VARCHAR(50) NOT NULL, status_action VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, date_contact DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_C9A5129DD8A38199 (locataire_id), INDEX IDX_C9A5129D3414710B (agent_id), INDEX IDX_C9A5129DA4AEAFEA (entreprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_depense (id INT AUTO_INCREMENT NOT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_1C24F8A2A4AEAFEA (entreprise_id), INDEX IDX_1C24F8A2B03A8386 (created_by_id), INDEX IDX_1C24F8A2896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE planning_recouvrement ADD CONSTRAINT FK_6EA12BE3D8A38199 FOREIGN KEY (locataire_id) REFERENCES locataire (id)');
        $this->addSql('ALTER TABLE planning_recouvrement ADD CONSTRAINT FK_6EA12BE33414710B FOREIGN KEY (agent_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE planning_recouvrement ADD CONSTRAINT FK_6EA12BE3A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC1267F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture_location (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC1263414710B FOREIGN KEY (agent_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE relance ADD CONSTRAINT FK_50BBC126896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE suivi_contact ADD CONSTRAINT FK_C9A5129DD8A38199 FOREIGN KEY (locataire_id) REFERENCES locataire (id)');
        $this->addSql('ALTER TABLE suivi_contact ADD CONSTRAINT FK_C9A5129D3414710B FOREIGN KEY (agent_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE suivi_contact ADD CONSTRAINT FK_C9A5129DA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE type_depense ADD CONSTRAINT FK_1C24F8A2A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE type_depense ADD CONSTRAINT FK_1C24F8A2B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE type_depense ADD CONSTRAINT FK_1C24F8A2896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE depenses ADD type_depense_id INT DEFAULT NULL, ADD entreprise_id INT DEFAULT NULL, CHANGE montant_ttc montant_ttc INT DEFAULT NULL, CHANGE date date VARCHAR(255) DEFAULT NULL, CHANGE details details LONGTEXT DEFAULT NULL, CHANGE scan scan VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE depenses ADD CONSTRAINT FK_EE350ECB5CDBC346 FOREIGN KEY (type_depense_id) REFERENCES type_depense (id)');
        $this->addSql('ALTER TABLE depenses ADD CONSTRAINT FK_EE350ECBA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('CREATE INDEX IDX_EE350ECB5CDBC346 ON depenses (type_depense_id)');
        $this->addSql('CREATE INDEX IDX_EE350ECBA4AEAFEA ON depenses (entreprise_id)');
        $this->addSql('ALTER TABLE facture_location ADD date_debut DATETIME DEFAULT NULL, ADD date_fin DATETIME DEFAULT NULL, ADD is_validated VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE module_abonnement ADD max_agences INT NOT NULL, ADD max_employes INT NOT NULL, ADD max_locataires_mobile_app INT NOT NULL');
        $this->addSql('ALTER TABLE quartier ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quartier ADD CONSTRAINT FK_FEE8962DD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_FEE8962DD725330D ON quartier (agence_id)');
        $this->addSql('ALTER TABLE transaction ADD payload LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE depenses DROP FOREIGN KEY FK_EE350ECB5CDBC346');
        $this->addSql('ALTER TABLE planning_recouvrement DROP FOREIGN KEY FK_6EA12BE3D8A38199');
        $this->addSql('ALTER TABLE planning_recouvrement DROP FOREIGN KEY FK_6EA12BE33414710B');
        $this->addSql('ALTER TABLE planning_recouvrement DROP FOREIGN KEY FK_6EA12BE3A4AEAFEA');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC1267F2DEE08');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC1263414710B');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC126A4AEAFEA');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC126D725330D');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC126B03A8386');
        $this->addSql('ALTER TABLE relance DROP FOREIGN KEY FK_50BBC126896DBBDE');
        $this->addSql('ALTER TABLE suivi_contact DROP FOREIGN KEY FK_C9A5129DD8A38199');
        $this->addSql('ALTER TABLE suivi_contact DROP FOREIGN KEY FK_C9A5129D3414710B');
        $this->addSql('ALTER TABLE suivi_contact DROP FOREIGN KEY FK_C9A5129DA4AEAFEA');
        $this->addSql('ALTER TABLE type_depense DROP FOREIGN KEY FK_1C24F8A2A4AEAFEA');
        $this->addSql('ALTER TABLE type_depense DROP FOREIGN KEY FK_1C24F8A2B03A8386');
        $this->addSql('ALTER TABLE type_depense DROP FOREIGN KEY FK_1C24F8A2896DBBDE');
        $this->addSql('DROP TABLE planning_recouvrement');
        $this->addSql('DROP TABLE relance');
        $this->addSql('DROP TABLE suivi_contact');
        $this->addSql('DROP TABLE type_depense');
        $this->addSql('ALTER TABLE quartier DROP FOREIGN KEY FK_FEE8962DD725330D');
        $this->addSql('DROP INDEX IDX_FEE8962DD725330D ON quartier');
        $this->addSql('ALTER TABLE quartier DROP agence_id');
        $this->addSql('ALTER TABLE transaction DROP payload');
        $this->addSql('ALTER TABLE facture_location DROP date_debut, DROP date_fin, DROP is_validated');
        $this->addSql('ALTER TABLE depenses DROP FOREIGN KEY FK_EE350ECBA4AEAFEA');
        $this->addSql('DROP INDEX IDX_EE350ECB5CDBC346 ON depenses');
        $this->addSql('DROP INDEX IDX_EE350ECBA4AEAFEA ON depenses');
        $this->addSql('ALTER TABLE depenses DROP type_depense_id, DROP entreprise_id, CHANGE montant_ttc montant_ttc INT NOT NULL, CHANGE date date VARCHAR(255) NOT NULL, CHANGE details details LONGTEXT NOT NULL, CHANGE scan scan VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE module_abonnement DROP max_agences, DROP max_employes, DROP max_locataires_mobile_app');
    }
}
