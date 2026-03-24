<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324112550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agence (id INT AUTO_INCREMENT NOT NULL, entreprise_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_64C19AA9A4AEAFEA (entreprise_id), INDEX IDX_64C19AA9B03A8386 (created_by_id), INDEX IDX_64C19AA9896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agence ADD CONSTRAINT FK_64C19AA9A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE agence ADD CONSTRAINT FK_64C19AA9B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE agence ADD CONSTRAINT FK_64C19AA9896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE _admin_param_module_groupe_permition ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE _admin_param_module_groupe_permition ADD CONSTRAINT FK_29EAEA2BA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('CREATE INDEX IDX_29EAEA2BA4AEAFEA ON _admin_param_module_groupe_permition (entreprise_id)');
        $this->addSql('ALTER TABLE locataire ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE locataire ADD CONSTRAINT FK_C47CF6EBD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_C47CF6EBD725330D ON locataire (agence_id)');
        $this->addSql('ALTER TABLE users ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9D725330D ON users (agence_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE locataire DROP FOREIGN KEY FK_C47CF6EBD725330D');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9D725330D');
        $this->addSql('ALTER TABLE agence DROP FOREIGN KEY FK_64C19AA9A4AEAFEA');
        $this->addSql('ALTER TABLE agence DROP FOREIGN KEY FK_64C19AA9B03A8386');
        $this->addSql('ALTER TABLE agence DROP FOREIGN KEY FK_64C19AA9896DBBDE');
        $this->addSql('DROP TABLE agence');
        $this->addSql('ALTER TABLE _admin_param_module_groupe_permition DROP FOREIGN KEY FK_29EAEA2BA4AEAFEA');
        $this->addSql('DROP INDEX IDX_29EAEA2BA4AEAFEA ON _admin_param_module_groupe_permition');
        $this->addSql('ALTER TABLE _admin_param_module_groupe_permition DROP entreprise_id');
        $this->addSql('DROP INDEX IDX_C47CF6EBD725330D ON locataire');
        $this->addSql('ALTER TABLE locataire DROP agence_id');
        $this->addSql('DROP INDEX IDX_1483A5E9D725330D ON users');
        $this->addSql('ALTER TABLE users DROP agence_id');
    }
}
