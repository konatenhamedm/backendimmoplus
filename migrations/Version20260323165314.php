<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323165314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abonnement (id INT AUTO_INCREMENT NOT NULL, module_abonnement_id INT DEFAULT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, etat VARCHAR(255) NOT NULL, date_fin DATETIME NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_351268BBA20C84BB (module_abonnement_id), INDEX IDX_351268BBA4AEAFEA (entreprise_id), INDEX IDX_351268BBB03A8386 (created_by_id), INDEX IDX_351268BB896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE module_abonnement (id INT AUTO_INCREMENT NOT NULL, pays_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, etat TINYINT(1) NOT NULL, description LONGTEXT NOT NULL, montant VARCHAR(255) NOT NULL, duree VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, numero INT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_A0F74326F55AE19E (numero), INDEX IDX_A0F74326A6E44244 (pays_id), INDEX IDX_A0F74326B03A8386 (created_by_id), INDEX IDX_A0F74326896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBA20C84BB FOREIGN KEY (module_abonnement_id) REFERENCES module_abonnement (id)');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BB896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE module_abonnement ADD CONSTRAINT FK_A0F74326A6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE module_abonnement ADD CONSTRAINT FK_A0F74326B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE module_abonnement ADD CONSTRAINT FK_A0F74326896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBA20C84BB');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBA4AEAFEA');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBB03A8386');
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BB896DBBDE');
        $this->addSql('ALTER TABLE module_abonnement DROP FOREIGN KEY FK_A0F74326A6E44244');
        $this->addSql('ALTER TABLE module_abonnement DROP FOREIGN KEY FK_A0F74326B03A8386');
        $this->addSql('ALTER TABLE module_abonnement DROP FOREIGN KEY FK_A0F74326896DBBDE');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('DROP TABLE module_abonnement');
    }
}
