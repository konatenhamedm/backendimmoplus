<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute au menu « Modèles de relance » et « Paramètres relances », dans le même module que « Relances »,
 * pour les groupes administrateurs qui voient déjà « Relances ».
 */
final class Version20260923180000 extends AbstractMigration
{
    private const LIENS = [
        // lien => [titre, icône, décalage d'ordre après « Relances »]
        '/relances/modeles' => ['Modèles de relance', 'FileText', 1],
        '/relances/parametres' => ['Paramètres relances', 'Settings', 2],
    ];

    private const GROUPES_ADMIN = "'SADM', 'ADMIN', 'ADMINAG'";

    public function getDescription(): string
    {
        return 'Menu : liens Modèles de relance et Paramètres relances';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LIENS as $lien => [$titre, $icone, $decalage]) {
            $this->addSql(
                'INSERT INTO _admin_param_groupe_module (icon_id, titre, ordre, lien, created_at, is_active)
                 SELECT (SELECT id FROM _admin_param_icon WHERE code = ? ORDER BY id LIMIT 1), ?, ?, ?, NOW(), 1
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM _admin_param_groupe_module WHERE lien = ?)',
                [$icone, $titre, $decalage + 1, $lien, $lien]
            );

            // Mêmes module, groupe, entreprise et droit que le lien « Relances » existant
            $this->addSql(
                'INSERT INTO _admin_param_module_groupe_permition (permition_id, module_id, groupe_module_id, groupe_user_id, entreprise_id, ordre, ordre_groupe, menu_principal, created_at, is_active)
                 SELECT mgp.permition_id, mgp.module_id, nouveau.id, mgp.groupe_user_id, mgp.entreprise_id, mgp.ordre + ?, mgp.ordre_groupe, mgp.menu_principal, NOW(), 1
                 FROM _admin_param_module_groupe_permition mgp
                 INNER JOIN _admin_param_groupe_module relances ON relances.id = mgp.groupe_module_id AND relances.lien = \'/relances\'
                 INNER JOIN _admin_user_groupe g ON g.id = mgp.groupe_user_id AND g.code IN (' . self::GROUPES_ADMIN . ')
                 INNER JOIN _admin_param_groupe_module nouveau ON nouveau.lien = ?
                 WHERE NOT EXISTS (
                     SELECT 1 FROM _admin_param_module_groupe_permition existant
                     WHERE existant.groupe_module_id = nouveau.id
                       AND existant.groupe_user_id = mgp.groupe_user_id
                       AND existant.module_id = mgp.module_id
                       AND existant.entreprise_id <=> mgp.entreprise_id
                 )',
                [$decalage, $lien]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::LIENS) as $lien) {
            $this->addSql(
                'DELETE mgp FROM _admin_param_module_groupe_permition mgp
                 INNER JOIN _admin_param_groupe_module gm ON gm.id = mgp.groupe_module_id
                 WHERE gm.lien = ?',
                [$lien]
            );
            $this->addSql('DELETE FROM _admin_param_groupe_module WHERE lien = ?', [$lien]);
        }
    }
}
