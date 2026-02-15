-- =====================================================
-- SCRIPT COMPLET DE CORRECTION DU MENU
-- =====================================================
-- Ce script vide les tables et réinsère les données
-- À exécuter dans phpMyAdmin ou MySQL
-- =====================================================

-- Désactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- ÉTAPE 1: VIDER LES TABLES CONCERNÉES
-- =====================================================

-- Vider la table de liaison (doit être vidée en premier à cause des FK)
TRUNCATE TABLE `_admin_param_module_groupe_permition`;

-- Vider les modules
TRUNCATE TABLE `_admin_param_module`;

-- Vider les groupes de modules
TRUNCATE TABLE `_admin_param_groupe_module`;

-- =====================================================
-- ÉTAPE 2: INSÉRER LES GROUPES DE MODULES
-- =====================================================

INSERT INTO `_admin_param_groupe_module` (`id`, `titre`, `ordre`, `lien`, `icon_id`) VALUES
(1, 'Tableau de bord', 1, '/dashboard', 2),
(2, 'Gestion Immobilière', 2, '#', 5),
(3, 'Gestion Locative', 3, '#', 7),
(4, 'Finances', 4, '#', 11),
(5, 'Paramètres', 5, '#', 4),
(6, 'Administration', 6, '#', 3);

-- =====================================================
-- ÉTAPE 3: INSÉRER LES MODULES
-- =====================================================

INSERT INTO `_admin_param_module` (`id`, `titre`, `ordre`) VALUES
(1, 'Dashboard', 1),
(2, 'Maisons', 1),
(3, 'Appartements', 2),
(4, 'Quartiers', 3),
(5, 'Types de Maison', 4),
(6, 'Locataires', 1),
(7, 'Propriétaires', 2),
(8, 'Contrats de Location', 3),
(9, 'Factures de Location', 1),
(10, 'Versements Propriétaires', 2),
(11, 'Comptes Clients', 3),
(12, 'Ligne Versement Frais', 4),
(13, 'Civilités', 1),
(14, 'Fonctions', 2),
(15, 'Pays', 3),
(16, 'Villes', 4),
(17, 'Motifs', 5),
(18, 'Icônes', 6),
(19, 'Années', 7),
(20, 'Utilisateurs', 1),
(21, 'Groupes', 2),
(22, 'Modules', 3),
(23, 'Permissions', 4),
(24, 'Entreprise', 5);

-- =====================================================
-- ÉTAPE 4: INSÉRER LA CONFIGURATION DU MENU
-- =====================================================

INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) 
VALUES
-- Tableau de bord (menu principal direct)
(1, 1, 3, 1, 1, 1, 1),

-- Gestion Immobilière
(2, 2, 3, 1, 1, 2, 0),
(3, 2, 3, 1, 2, 2, 0),
(4, 2, 3, 1, 3, 2, 0),
(5, 2, 3, 1, 4, 2, 0),

-- Gestion Locative
(6, 3, 3, 1, 1, 3, 0),
(7, 3, 3, 1, 2, 3, 0),
(8, 3, 3, 1, 3, 3, 0),

-- Finances
(9, 4, 3, 1, 1, 4, 0),
(10, 4, 3, 1, 2, 4, 0),
(11, 4, 3, 1, 3, 4, 0),
(12, 4, 3, 1, 4, 4, 0),

-- Paramètres
(13, 5, 3, 1, 1, 5, 0),
(14, 5, 3, 1, 2, 5, 0),
(15, 5, 3, 1, 3, 5, 0),
(16, 5, 3, 1, 4, 5, 0),
(17, 5, 3, 1, 5, 5, 0),
(18, 5, 3, 1, 6, 5, 0),
(19, 5, 3, 1, 7, 5, 0),

-- Administration
(20, 6, 3, 1, 1, 6, 0),
(21, 6, 3, 1, 2, 6, 0),
(22, 6, 3, 1, 3, 6, 0),
(23, 6, 3, 1, 4, 6, 0),
(24, 6, 3, 1, 5, 6, 0);

-- =====================================================
-- ÉTAPE 5: RÉACTIVER LES CONTRAINTES
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VÉRIFICATIONS
-- =====================================================

-- Compter les groupes de modules (devrait retourner 6)
SELECT COUNT(*) as total_groupes FROM `_admin_param_groupe_module`;

-- Compter les modules (devrait retourner 24)
SELECT COUNT(*) as total_modules FROM `_admin_param_module`;

-- Compter les liaisons (devrait retourner 24)
SELECT COUNT(*) as total_liaisons FROM `_admin_param_module_groupe_permition`;

-- Vérifier qu'il n'y a AUCUN groupe_module_id NULL (devrait retourner 0)
SELECT COUNT(*) as total_null 
FROM `_admin_param_module_groupe_permition` 
WHERE `groupe_module_id` IS NULL;

-- Afficher la structure complète du menu
SELECT 
    gm.ordre AS groupe_ordre,
    gm.titre AS groupe,
    m.titre AS module,
    mgp.groupe_module_id,
    mgp.ordre AS module_ordre,
    mgp.menu_principal,
    p.code AS permission
FROM `_admin_param_module_groupe_permition` mgp
JOIN `_admin_param_groupe_module` gm ON mgp.groupe_module_id = gm.id
JOIN `_admin_param_module` m ON mgp.module_id = m.id
JOIN `_admin_param_permition` p ON mgp.permition_id = p.id
WHERE mgp.groupe_user_id = 1
ORDER BY gm.ordre, mgp.ordre;

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================
-- Résultat attendu:
-- - 6 groupes de modules
-- - 24 modules
-- - 24 liaisons avec groupe_module_id NON NULL
-- - Menu complet et fonctionnel
-- =====================================================
