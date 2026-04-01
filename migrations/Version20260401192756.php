<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401192756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lead_contact (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) NOT NULL, company VARCHAR(255) NOT NULL, plan_name VARCHAR(100) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE _depense_residence ADD type_depense_id INT DEFAULT NULL, ADD agence_id INT DEFAULT NULL, DROP categorie');
        $this->addSql('ALTER TABLE _depense_residence ADD CONSTRAINT FK_63EE723D5CDBC346 FOREIGN KEY (type_depense_id) REFERENCES type_depense (id)');
        $this->addSql('ALTER TABLE _depense_residence ADD CONSTRAINT FK_63EE723DD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_63EE723D5CDBC346 ON _depense_residence (type_depense_id)');
        $this->addSql('CREATE INDEX IDX_63EE723DD725330D ON _depense_residence (agence_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE lead_contact');
        $this->addSql('ALTER TABLE _depense_residence DROP FOREIGN KEY FK_63EE723D5CDBC346');
        $this->addSql('ALTER TABLE _depense_residence DROP FOREIGN KEY FK_63EE723DD725330D');
        $this->addSql('DROP INDEX IDX_63EE723D5CDBC346 ON _depense_residence');
        $this->addSql('DROP INDEX IDX_63EE723DD725330D ON _depense_residence');
        $this->addSql('ALTER TABLE _depense_residence ADD categorie VARCHAR(50) DEFAULT NULL, DROP type_depense_id, DROP agence_id');
    }
}
