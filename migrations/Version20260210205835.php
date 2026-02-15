<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210205835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_location ADD montant_facture INT NOT NULL, ADD solde_facture_location INT NOT NULL, DROP mnt_fact, DROP solde_fact_loc, CHANGE lib_facture libelle_facture VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE versement_proprio ADD libelle_versement VARCHAR(255) NOT NULL, ADD numero_recu VARCHAR(255) NOT NULL, DROP libelle, DROP numero');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_location ADD mnt_fact INT NOT NULL, ADD solde_fact_loc INT NOT NULL, DROP montant_facture, DROP solde_facture_location, CHANGE libelle_facture lib_facture VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE versement_proprio ADD libelle VARCHAR(255) NOT NULL, ADD numero VARCHAR(255) NOT NULL, DROP libelle_versement, DROP numero_recu');
    }
}
