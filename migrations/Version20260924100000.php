<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pénalités de retard : réglages par agence et montant de pénalité sur les factures';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE parametre_penalite (id INT AUTO_INCREMENT NOT NULL, agence_id INT NOT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, actif TINYINT(1) NOT NULL, type VARCHAR(20) NOT NULL, valeur DOUBLE PRECISION NOT NULL, delai_grace INT NOT NULL, recurrence_mensuelle TINYINT(1) NOT NULL, plafond INT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_69E01A18D725330D (agence_id), INDEX IDX_69E01A18A4AEAFEA (entreprise_id), INDEX IDX_69E01A18B03A8386 (created_by_id), INDEX IDX_69E01A18896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE parametre_penalite ADD CONSTRAINT FK_69E01A18D725330D FOREIGN KEY (agence_id) REFERENCES agence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parametre_penalite ADD CONSTRAINT FK_69E01A18A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE parametre_penalite ADD CONSTRAINT FK_69E01A18B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE parametre_penalite ADD CONSTRAINT FK_69E01A18896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');

        $this->addSql('ALTER TABLE facture_location ADD mntPenalite INT DEFAULT 0 NOT NULL, ADD nbPenalites INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facture_location DROP mntPenalite, DROP nbPenalites');
        $this->addSql('DROP TABLE parametre_penalite');
    }
}
