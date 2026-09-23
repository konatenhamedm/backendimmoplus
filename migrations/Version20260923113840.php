<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923113840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend optionnels les champs du locataire (dateNaiss, lieuNaiss, profession, contacts, genre, numpiece)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE locataire CHANGE dateNaiss dateNaiss DATETIME DEFAULT NULL, CHANGE lieuNaiss lieuNaiss VARCHAR(255) DEFAULT NULL, CHANGE profession profession VARCHAR(255) DEFAULT NULL, CHANGE contacts contacts VARCHAR(255) DEFAULT NULL, CHANGE genre genre VARCHAR(255) DEFAULT NULL, CHANGE numpiece numpiece VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE locataire CHANGE dateNaiss dateNaiss DATETIME NOT NULL, CHANGE lieuNaiss lieuNaiss VARCHAR(255) NOT NULL, CHANGE profession profession VARCHAR(255) NOT NULL, CHANGE contacts contacts VARCHAR(255) NOT NULL, CHANGE genre genre VARCHAR(255) NOT NULL, CHANGE numpiece numpiece VARCHAR(255) NOT NULL');
    }
}
