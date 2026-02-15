-- =====================================================
-- Script de CORRECTION - Inversion Module/GroupeModule
-- =====================================================
-- Module = Grands titres (Paramétrage, Gestion Immobilière, etc.)
-- GroupeModule = Ressources/Pages (/locataire, /maison, /civilite, etc.)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Vider les tables
DELETE FROM _admin_param_module_groupe_permition;
DELETE FROM _admin_param_module;
DELETE FROM _admin_param_groupe_module;

-- =====================================================
-- 1. MODULES (Grands Titres du Menu)
-- =====================================================
INSERT INTO _admin_param_module (id, titre, ordre) VALUES
(1, 'Tableau de bord', 1),
(2, 'Gestion Immobilière', 2),
(3, 'Gestion Locative', 3),
(4, 'Finances', 4),
(5, 'Paramètres', 5),
(6, 'Administration', 6);

-- =====================================================
-- 2. GROUPE_MODULE (Ressources/Pages avec liens)
-- =====================================================

-- Tableau de bord (1 ressource)
INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES
(1, 'Dashboard', 1, '/dashboard', 101);

-- Gestion Immobilière (4 ressources)
INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES
(2, 'Maisons', 1, '/maison', 102),
(3, 'Appartements', 2, '/appartement', 102),
(4, 'Quartiers', 3, '/quartier', 102),
(5, 'Types de Maison', 4, '/type-maison', 102);

-- Gestion Locative (3 ressources)
INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES
(6, 'Locataires', 1, '/locataire', 103),
(7, 'Propriétaires', 2, '/proprietaire', 103),
(8, 'Contrats de Location', 3, '/contrat-location', 103);

-- Finances (4 ressources)
INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES
(9, 'Factures de Location', 1, '/facture-location', 101),
(10, 'Versements Propriétaires', 2, '/versement-proprio', 101),
(11, 'Comptes Clients', 3, '/compte-clt-t', 101),
(12, 'Ligne Versement Frais', 4, '/ligne-versement-frais', 101);

-- Paramètres (7 ressources)
INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES
(13, 'Civilités', 1, '/civilite', 102),
(14, 'Fonctions', 2, '/fonction', 102),
(15, 'Pays', 3, '/pays', 102),
(16, 'Villes', 4, '/ville', 102),
(17, 'Motifs', 5, '/motif', 102),
(18, 'Icônes', 6, '/icon', 102),
(19, 'Années', 7, '/annee', 102);

-- Administration (5 ressources)
INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES
(20, 'Utilisateurs', 1, '/utilisateurs', 103),
(21, 'Groupes', 2, '/groupe', 103),
(22, 'Modules', 3, '/module', 103),
(23, 'Permissions', 4, '/permission', 103),
(24, 'Entreprise', 5, '/entreprise', 103);

-- =====================================================
-- 3. MODULE_GROUPE_PERMITION (Liaison)
-- =====================================================
-- Maintenant: module_id = Grand titre, groupe_module_id = Ressource

-- Tableau de bord
INSERT INTO _admin_param_module_groupe_permition 
(module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES
(1, 1, 3, 4, 1, 1, 1);

-- Gestion Immobilière
INSERT INTO _admin_param_module_groupe_permition 
(module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES
(2, 2, 3, 4, 1, 2, 0),
(2, 3, 3, 4, 2, 2, 0),
(2, 4, 3, 4, 3, 2, 0),
(2, 5, 3, 4, 4, 2, 0);

-- Gestion Locative
INSERT INTO _admin_param_module_groupe_permition 
(module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES
(3, 6, 3, 4, 1, 3, 0),
(3, 7, 3, 4, 2, 3, 0),
(3, 8, 3, 4, 3, 3, 0);

-- Finances
INSERT INTO _admin_param_module_groupe_permition 
(module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES
(4, 9, 3, 4, 1, 4, 0),
(4, 10, 3, 4, 2, 4, 0),
(4, 11, 3, 4, 3, 4, 0),
(4, 12, 3, 4, 4, 4, 0);

-- Paramètres
INSERT INTO _admin_param_module_groupe_permition 
(module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES
(5, 13, 3, 4, 1, 5, 0),
(5, 14, 3, 4, 2, 5, 0),
(5, 15, 3, 4, 3, 5, 0),
(5, 16, 3, 4, 4, 5, 0),
(5, 17, 3, 4, 5, 5, 0),
(5, 18, 3, 4, 6, 5, 0),
(5, 19, 3, 4, 7, 5, 0);

-- Administration
INSERT INTO _admin_param_module_groupe_permition 
(module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES
(6, 20, 3, 4, 1, 6, 0),
(6, 21, 3, 4, 2, 6, 0),
(6, 22, 3, 4, 3, 6, 0),
(6, 23, 3, 4, 4, 6, 0),
(6, 24, 3, 4, 5, 6, 0);

SET FOREIGN_KEY_CHECKS = 1;

-- Vérification
SELECT 
    m.id as module_id,
    m.titre as module_titre,
    gm.id as groupe_module_id,
    gm.titre as ressource_titre,
    gm.lien as ressource_lien,
    mgp.ordre as ordre_ressource
FROM _admin_param_module_groupe_permition mgp
JOIN _admin_param_module m ON mgp.module_id = m.id
JOIN _admin_param_groupe_module gm ON mgp.groupe_module_id = gm.id
WHERE mgp.groupe_user_id = 4
ORDER BY m.ordre, mgp.ordre;
