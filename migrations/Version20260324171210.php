<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324171210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE motif ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE motif ADD CONSTRAINT FK_87D377BBA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('CREATE INDEX IDX_87D377BBA4AEAFEA ON motif (entreprise_id)');
        $this->addSql('ALTER TABLE type_maison ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE type_maison ADD CONSTRAINT FK_DADBDD55A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES _admin_param_entreprise (id)');
        $this->addSql('CREATE INDEX IDX_DADBDD55A4AEAFEA ON type_maison (entreprise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE motif DROP FOREIGN KEY FK_87D377BBA4AEAFEA');
        $this->addSql('DROP INDEX IDX_87D377BBA4AEAFEA ON motif');
        $this->addSql('ALTER TABLE motif DROP entreprise_id');
        $this->addSql('ALTER TABLE type_maison DROP FOREIGN KEY FK_DADBDD55A4AEAFEA');
        $this->addSql('DROP INDEX IDX_DADBDD55A4AEAFEA ON type_maison');
        $this->addSql('ALTER TABLE type_maison DROP entreprise_id');
    }
}
