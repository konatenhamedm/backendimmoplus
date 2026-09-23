<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Grands modules d'abonnement. Les données (Loyers, Résidences, Terrains et leurs rattachements)
 * sont créées par la commande app:modules-metier:init.
 */
final class Version20260923200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Grands modules d'abonnement (module_metier), rattachés aux sections du menu et aux formules";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE module_metier (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, code VARCHAR(30) NOT NULL, libelle VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icone VARCHAR(50) DEFAULT NULL, ordre INT NOT NULL, prefixes_api JSON NOT NULL, created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_FBA34DA377153098 (code), INDEX IDX_FBA34DA3B03A8386 (created_by_id), INDEX IDX_FBA34DA3896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE module_metier ADD CONSTRAINT FK_FBA34DA3B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE module_metier ADD CONSTRAINT FK_FBA34DA3896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');

        $this->addSql('CREATE TABLE module_abonnement_module_metier (module_abonnement_id INT NOT NULL, module_metier_id INT NOT NULL, INDEX IDX_A07A7E55A20C84BB (module_abonnement_id), INDEX IDX_A07A7E55B777E5E8 (module_metier_id), PRIMARY KEY(module_abonnement_id, module_metier_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE module_abonnement_module_metier ADD CONSTRAINT FK_A07A7E55A20C84BB FOREIGN KEY (module_abonnement_id) REFERENCES module_abonnement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE module_abonnement_module_metier ADD CONSTRAINT FK_A07A7E55B777E5E8 FOREIGN KEY (module_metier_id) REFERENCES module_metier (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE _admin_param_module ADD module_metier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE _admin_param_module ADD CONSTRAINT FK_8D7380BDB777E5E8 FOREIGN KEY (module_metier_id) REFERENCES module_metier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D7380BDB777E5E8 ON _admin_param_module (module_metier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE _admin_param_module DROP FOREIGN KEY FK_8D7380BDB777E5E8');
        $this->addSql('DROP INDEX IDX_8D7380BDB777E5E8 ON _admin_param_module');
        $this->addSql('ALTER TABLE _admin_param_module DROP module_metier_id');
        $this->addSql('DROP TABLE module_abonnement_module_metier');
        $this->addSql('DROP TABLE module_metier');
    }
}
