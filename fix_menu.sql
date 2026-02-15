-- =====================================================
-- Script de CORRECTION - Nettoyage et Réinitialisation du Menu
-- =====================================================
-- Ce script corrige le problème des groupe_module_id NULL
-- =====================================================

-- Désactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- NETTOYAGE COMPLET DES TABLES DE MENU
-- =====================================================

-- Vider complètement la table de liaison
DELETE FROM `_admin_param_module_groupe_permition`;

-- Vider les modules
DELETE FROM `_admin_param_module`;

-- Vider les groupes de modules
DELETE FROM `_admin_param_groupe_module`;

-- Réinitialiser les auto-increment
ALTER TABLE `_admin_param_module_groupe_permition` AUTO_INCREMENT = 1;
ALTER TABLE `_admin_param_module` AUTO_INCREMENT = 1;
ALTER TABLE `_admin_param_groupe_module` AUTO_INCREMENT = 1;

-- =====================================================
-- RÉINSERTION DES GROUPES DE MODULES
-- =====================================================
INSERT INTO `_admin_param_groupe_module` (`id`, `titre`, `ordre`, `lien`, `icon_id`) VALUES
(1, 'Tableau de bord', 1, '/dashboard', 2),
(2, 'Gestion Immobilière', 2, '#', 5),
(3, 'Gestion Locative', 3, '#', 7),
(4, 'Finances', 4, '#', 11),
(5, 'Paramètres', 5, '#', 4),
(6, 'Administration', 6, '#', 3);

-- =====================================================
-- RÉINSERTION DES MODULES
-- =====================================================
INSERT INTO `_admin_param_module` (`id`, `titre`, `ordre`) VALUES
-- Tableau de bord
(1, 'Dashboard', 1),

-- Gestion Immobilière
(2, 'Maisons', 1),
(3, 'Appartements', 2),
(4, 'Quartiers', 3),
(5, 'Types de Maison', 4),

-- Gestion Locative
(6, 'Locataires', 1),
(7, 'Propriétaires', 2),
(8, 'Contrats de Location', 3),

-- Finances
(9, 'Factures de Location', 1),
(10, 'Versements Propriétaires', 2),
(11, 'Comptes Clients', 3),
(12, 'Ligne Versement Frais', 4),

-- Paramètres
(13, 'Civilités', 1),
(14, 'Fonctions', 2),
(15, 'Pays', 3),
(16, 'Villes', 4),
(17, 'Motifs', 5),
(18, 'Icônes', 6),
(19, 'Années', 7),

-- Administration
(20, 'Utilisateurs', 1),
(21, 'Groupes', 2),
(22, 'Modules', 3),
(23, 'Permissions', 4),
(24, 'Entreprise', 5);

-- =====================================================
-- RÉINSERTION DE LA LIAISON MODULE-GROUPE-PERMISSION
-- =====================================================

-- Tableau de bord (Menu principal direct)
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(1, 1, 3, 1, 1, 1, 1);

-- Gestion Immobilière
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(2, 2, 3, 1, 1, 2, 0),
(3, 2, 3, 1, 2, 2, 0),
(4, 2, 3, 1, 3, 2, 0),
(5, 2, 3, 1, 4, 2, 0);

-- Gestion Locative
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(6, 3, 3, 1, 1, 3, 0),
(7, 3, 3, 1, 2, 3, 0),
(8, 3, 3, 1, 3, 3, 0);

-- Finances
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(9, 4, 3, 1, 1, 4, 0),
(10, 4, 3, 1, 2, 4, 0),
(11, 4, 3, 1, 3, 4, 0),
(12, 4, 3, 1, 4, 4, 0);

-- Paramètres
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(13, 5, 3, 1, 1, 5, 0),
(14, 5, 3, 1, 2, 5, 0),
(15, 5, 3, 1, 3, 5, 0),
(16, 5, 3, 1, 4, 5, 0),
(17, 5, 3, 1, 5, 5, 0),
(18, 5, 3, 1, 6, 5, 0),
(19, 5, 3, 1, 7, 5, 0);

-- Administration
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(20, 6, 3, 1, 1, 6, 0),
(21, 6, 3, 1, 2, 6, 0),
(22, 6, 3, 1, 3, 6, 0),
(23, 6, 3, 1, 4, 6, 0),
(24, 6, 3, 1, 5, 6, 0);

-- Réactiver les contraintes
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VÉRIFICATION
-- =====================================================

-- Afficher les groupes de modules
SELECT 'GROUPES DE MODULES:' as info;
SELECT id, titre, ordre, lien, icon_id FROM _admin_param_groupe_module ORDER BY ordre;

-- Afficher les modules
SELECT 'MODULES:' as info;
SELECT id, titre, ordre FROM _admin_param_module ORDER BY id;

-- Afficher la configuration complète
SELECT 'CONFIGURATION DU MENU:' as info;
SELECT 
    mgp.id,
    m.id as module_id,
    m.titre as module,
    gm.id as groupe_id,
    gm.titre as groupe,
    mgp.ordre as ordre_module,
    mgp.ordre_groupe,
    mgp.menu_principal,
    p.code as permission
FROM _admin_param_module_groupe_permition mgp
JOIN _admin_param_module m ON mgp.module_id = m.id
JOIN _admin_param_groupe_module gm ON mgp.groupe_module_id = gm.id
JOIN _admin_param_permition p ON mgp.permition_id = p.id
WHERE mgp.groupe_user_id = 1
ORDER BY mgp.ordre_groupe, mgp.ordre;

-- Vérifier qu'il n'y a plus de NULL
SELECT 'VÉRIFICATION DES NULL:' as info;
SELECT COUNT(*) as total_null 
FROM _admin_param_module_groupe_permition 
WHERE groupe_module_id IS NULL;

-- =====================================================
-- FIN DU SCRIPT DE CORRECTION
-- =====================================================
