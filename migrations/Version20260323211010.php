<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323211010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction ADD entreprise_id INT DEFAULT NULL, ADD module_abonnement_id INT DEFAULT NULL, CHANGE locataire_id locataire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A20C84BB FOREIGN KEY (module_abonnement_id) REFERENCES module_abonnement (id)');
        $this->addSql('CREATE INDEX IDX_723705D1A4AEAFEA ON transaction (entreprise_id)');
        $this->addSql('CREATE INDEX IDX_723705D1A20C84BB ON transaction (module_abonnement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A4AEAFEA');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A20C84BB');
        $this->addSql('DROP INDEX IDX_723705D1A4AEAFEA ON transaction');
        $this->addSql('DROP INDEX IDX_723705D1A20C84BB ON transaction');
        $this->addSql('ALTER TABLE transaction DROP entreprise_id, DROP module_abonnement_id, CHANGE locataire_id locataire_id INT NOT NULL');
    }
}
