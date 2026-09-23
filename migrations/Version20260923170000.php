<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923170000 extends AbstractMigration
{
    // Textes figés ici pour que la migration reste valable même si les valeurs par défaut du code changent
    private const LIBELLE = 'Modèle par défaut';
    private const RAPPEL_SUJET = 'Rappel : votre loyer de {mois} arrive à échéance';
    private const RAPPEL_MESSAGE = "Bonjour {locataire},\n\nNous vous rappelons que votre loyer de {mois} d'un montant de {montant} arrive à échéance le {date_limite} (dans {jours_restants} jour(s)).\n\nMerci de prendre vos dispositions pour effectuer le règlement à temps.\n\nCordialement,\n{agence}\n{agence_contact}";
    private const RAPPEL_SMS = '{agence} : Bonjour {locataire}, votre loyer de {mois} ({montant}) est attendu le {date_limite}. Merci.';
    private const RELANCE_SUJET = 'Relance : loyer de {mois} impayé';
    private const RELANCE_MESSAGE = "Bonjour {locataire},\n\nSauf erreur de notre part, nous n'avons pas reçu le règlement de votre facture {facture} d'un montant restant de {montant}, attendu le {date_limite} ({jours_retard} jour(s) de retard).\n\nNous vous prions de bien vouloir régulariser votre situation dans les plus brefs délais.\n\nCordialement,\n{agence}\n{agence_contact}";
    private const RELANCE_SMS = '{agence} : Bonjour {locataire}, votre loyer de {mois} ({montant}) est impayé depuis le {date_limite}. Merci de régulariser. {agence_contact}';

    public function getDescription(): string
    {
        return "Modèles de relance par agence (un par défaut par agence, liables aux contrats), offre SMS des abonnements et journal des SMS";
    }

    public function up(Schema $schema): void
    {
        // Modèles de relance
        $this->addSql("CREATE TABLE modele_relance (id INT AUTO_INCREMENT NOT NULL, agence_id INT NOT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, libelle VARCHAR(150) NOT NULL, par_defaut TINYINT(1) NOT NULL, actif TINYINT(1) NOT NULL, rappel_sujet VARCHAR(255) NOT NULL, rappel_message LONGTEXT NOT NULL, rappel_sms LONGTEXT NOT NULL, relance_sujet VARCHAR(255) NOT NULL, relance_message LONGTEXT NOT NULL, relance_sms LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, INDEX IDX_55363BF4D725330D (agence_id), INDEX IDX_55363BF4A4AEAFEA (entreprise_id), INDEX IDX_55363BF4B03A8386 (created_by_id), INDEX IDX_55363BF4896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE modele_relance ADD CONSTRAINT FK_55363BF4D725330D FOREIGN KEY (agence_id) REFERENCES agence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modele_relance ADD CONSTRAINT FK_55363BF4A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE modele_relance ADD CONSTRAINT FK_55363BF4B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE modele_relance ADD CONSTRAINT FK_55363BF4896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');

        // Un modèle par défaut pour chaque agence, en reprenant les textes déjà personnalisés dans parametre_relance
        $this->addSql(
            'INSERT INTO modele_relance (agence_id, entreprise_id, libelle, par_defaut, actif, rappel_sujet, rappel_message, rappel_sms, relance_sujet, relance_message, relance_sms, created_at, is_active)
             SELECT a.id, a.entreprise_id, ?, 1, 1, COALESCE(p.rappel_sujet, ?), COALESCE(p.rappel_message, ?), ?, COALESCE(p.relance_sujet, ?), COALESCE(p.relance_message, ?), ?, NOW(), 1
             FROM agence a LEFT JOIN parametre_relance p ON p.agence_id = a.id',
            [self::LIBELLE, self::RAPPEL_SUJET, self::RAPPEL_MESSAGE, self::RAPPEL_SMS, self::RELANCE_SUJET, self::RELANCE_MESSAGE, self::RELANCE_SMS]
        );
        $this->addSql('ALTER TABLE parametre_relance DROP rappel_sujet, DROP rappel_message, DROP relance_sujet, DROP relance_message');

        // Modèle propre à un contrat
        $this->addSql('ALTER TABLE contrat_location ADD modele_relance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat_location ADD CONSTRAINT FK_E7519391278464C8 FOREIGN KEY (modele_relance_id) REFERENCES modele_relance (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E7519391278464C8 ON contrat_location (modele_relance_id)');

        // Offre SMS des abonnements (-1 = illimité)
        $this->addSql('ALTER TABLE module_abonnement ADD has_sms TINYINT(1) DEFAULT 0 NOT NULL, ADD sms_quota INT DEFAULT 0 NOT NULL');

        // Journal des SMS
        $this->addSql("CREATE TABLE sms_envoi (id INT AUTO_INCREMENT NOT NULL, entreprise_id INT NOT NULL, agence_id INT DEFAULT NULL, facture_id INT DEFAULT NULL, destinataire VARCHAR(30) NOT NULL, message LONGTEXT NOT NULL, nb_sms INT NOT NULL, statut VARCHAR(20) NOT NULL, fournisseur VARCHAR(50) NOT NULL, reference_fournisseur VARCHAR(100) DEFAULT NULL, erreur LONGTEXT DEFAULT NULL, date_envoi DATETIME NOT NULL, INDEX IDX_48CDEC3BA4AEAFEA (entreprise_id), INDEX IDX_48CDEC3BD725330D (agence_id), INDEX IDX_48CDEC3B7F2DEE08 (facture_id), INDEX IDX_48CDEC3BE564F0BF708EDDEA (statut, date_envoi), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE sms_envoi ADD CONSTRAINT FK_48CDEC3BA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE sms_envoi ADD CONSTRAINT FK_48CDEC3BD725330D FOREIGN KEY (agence_id) REFERENCES agence (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sms_envoi ADD CONSTRAINT FK_48CDEC3B7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture_location (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sms_envoi');
        $this->addSql('ALTER TABLE module_abonnement DROP has_sms, DROP sms_quota');
        $this->addSql('ALTER TABLE contrat_location DROP FOREIGN KEY FK_E7519391278464C8');
        $this->addSql('DROP INDEX IDX_E7519391278464C8 ON contrat_location');
        $this->addSql('ALTER TABLE contrat_location DROP modele_relance_id');
        $this->addSql("ALTER TABLE parametre_relance ADD rappel_sujet VARCHAR(255) NOT NULL DEFAULT '', ADD rappel_message LONGTEXT NOT NULL, ADD relance_sujet VARCHAR(255) NOT NULL DEFAULT '', ADD relance_message LONGTEXT NOT NULL");
        $this->addSql('UPDATE parametre_relance p JOIN modele_relance m ON m.agence_id = p.agence_id AND m.par_defaut = 1 SET p.rappel_sujet = m.rappel_sujet, p.rappel_message = m.rappel_message, p.relance_sujet = m.relance_sujet, p.relance_message = m.relance_message');
        $this->addSql('DROP TABLE modele_relance');
    }
}
