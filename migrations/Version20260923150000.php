<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Paramètres de relance par agence (mode automatique / manuel, délais, modèles) et origine / étape des relances";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE parametre_relance (id INT AUTO_INCREMENT NOT NULL, agence_id INT NOT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, mode VARCHAR(20) NOT NULL, canaux JSON NOT NULL, rappel_actif TINYINT(1) NOT NULL, jours_avant_echeance INT NOT NULL, rappel_sujet VARCHAR(255) NOT NULL, rappel_message LONGTEXT NOT NULL, relance_actif TINYINT(1) NOT NULL, jours_apres_echeance JSON NOT NULL, relance_sujet VARCHAR(255) NOT NULL, relance_message LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_9736C88FD725330D (agence_id), INDEX IDX_9736C88FA4AEAFEA (entreprise_id), INDEX IDX_9736C88FB03A8386 (created_by_id), INDEX IDX_9736C88F896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE parametre_relance ADD CONSTRAINT FK_9736C88FD725330D FOREIGN KEY (agence_id) REFERENCES agence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE parametre_relance ADD CONSTRAINT FK_9736C88FA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE parametre_relance ADD CONSTRAINT FK_9736C88FB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE parametre_relance ADD CONSTRAINT FK_9736C88F896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');

        $this->addSql("ALTER TABLE relance ADD origine VARCHAR(20) DEFAULT NULL, ADD etape VARCHAR(30) DEFAULT NULL");
        $this->addSql("UPDATE relance SET origine = 'MANUEL' WHERE origine IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE relance DROP origine, DROP etape');
        $this->addSql('DROP TABLE parametre_relance');
    }
}
