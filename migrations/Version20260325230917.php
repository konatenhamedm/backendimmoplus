<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325230917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module_abonnement ADD max_biens INT NOT NULL, ADD has_facturation_auto TINYINT(1) NOT NULL, ADD has_relances_auto TINYINT(1) NOT NULL, ADD has_mobile_money TINYINT(1) NOT NULL, ADD has_rapports_avances TINYINT(1) NOT NULL, ADD has_gestion_depenses TINYINT(1) NOT NULL, ADD signature_electronique VARCHAR(50) NOT NULL, ADD has_multi_agences TINYINT(1) NOT NULL, ADD has_api_integrations TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE module_abonnement DROP max_biens, DROP has_facturation_auto, DROP has_relances_auto, DROP has_mobile_money, DROP has_rapports_avances, DROP has_gestion_depenses, DROP signature_electronique, DROP has_multi_agences, DROP has_api_integrations');
    }
}
