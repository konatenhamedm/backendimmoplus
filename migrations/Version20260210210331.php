<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210210331 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_location ADD mntFact INT NOT NULL, ADD soldeFactLoc INT NOT NULL, ADD dateEmission DATETIME NOT NULL, ADD dateLimite DATETIME NOT NULL, DROP date_emission, DROP date_limite, DROP mnt_fact, DROP solde_fact_loc, CHANGE lib_facture libFacture VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_location ADD date_emission DATETIME NOT NULL, ADD date_limite DATETIME NOT NULL, ADD mnt_fact INT NOT NULL, ADD solde_fact_loc INT NOT NULL, DROP mntFact, DROP soldeFactLoc, DROP dateEmission, DROP dateLimite, CHANGE libFacture lib_facture VARCHAR(255) NOT NULL');
    }
}
