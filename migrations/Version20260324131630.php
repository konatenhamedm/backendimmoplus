<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324131630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE _admin_employe ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE _admin_employe ADD CONSTRAINT FK_9368111ED725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_9368111ED725330D ON _admin_employe (agence_id)');
        $this->addSql('ALTER TABLE campagne ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campagne ADD CONSTRAINT FK_539B5D16D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_539B5D16D725330D ON campagne (agence_id)');
        $this->addSql('ALTER TABLE contrat_location ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat_location ADD CONSTRAINT FK_E7519391D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_E7519391D725330D ON contrat_location (agence_id)');
        $this->addSql('ALTER TABLE depenses ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE depenses ADD CONSTRAINT FK_EE350ECBD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_EE350ECBD725330D ON depenses (agence_id)');
        $this->addSql('ALTER TABLE facture_location ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture_location ADD CONSTRAINT FK_4C61873AD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_4C61873AD725330D ON facture_location (agence_id)');
        $this->addSql('ALTER TABLE maison ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE maison ADD CONSTRAINT FK_F90CB66DD725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_F90CB66DD725330D ON maison (agence_id)');
        $this->addSql('ALTER TABLE proprio ADD agence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE proprio ADD CONSTRAINT FK_79F4F386D725330D FOREIGN KEY (agence_id) REFERENCES agence (id)');
        $this->addSql('CREATE INDEX IDX_79F4F386D725330D ON proprio (agence_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE _admin_employe DROP FOREIGN KEY FK_9368111ED725330D');
        $this->addSql('DROP INDEX IDX_9368111ED725330D ON _admin_employe');
        $this->addSql('ALTER TABLE _admin_employe DROP agence_id');
        $this->addSql('ALTER TABLE campagne DROP FOREIGN KEY FK_539B5D16D725330D');
        $this->addSql('DROP INDEX IDX_539B5D16D725330D ON campagne');
        $this->addSql('ALTER TABLE campagne DROP agence_id');
        $this->addSql('ALTER TABLE contrat_location DROP FOREIGN KEY FK_E7519391D725330D');
        $this->addSql('DROP INDEX IDX_E7519391D725330D ON contrat_location');
        $this->addSql('ALTER TABLE contrat_location DROP agence_id');
        $this->addSql('ALTER TABLE depenses DROP FOREIGN KEY FK_EE350ECBD725330D');
        $this->addSql('DROP INDEX IDX_EE350ECBD725330D ON depenses');
        $this->addSql('ALTER TABLE depenses DROP agence_id');
        $this->addSql('ALTER TABLE facture_location DROP FOREIGN KEY FK_4C61873AD725330D');
        $this->addSql('DROP INDEX IDX_4C61873AD725330D ON facture_location');
        $this->addSql('ALTER TABLE facture_location DROP agence_id');
        $this->addSql('ALTER TABLE maison DROP FOREIGN KEY FK_F90CB66DD725330D');
        $this->addSql('DROP INDEX IDX_F90CB66DD725330D ON maison');
        $this->addSql('ALTER TABLE maison DROP agence_id');
        $this->addSql('ALTER TABLE proprio DROP FOREIGN KEY FK_79F4F386D725330D');
        $this->addSql('DROP INDEX IDX_79F4F386D725330D ON proprio');
        $this->addSql('ALTER TABLE proprio DROP agence_id');
    }
}
