-- =====================================================
-- Script d'initialisation COMPLET de la base de données motiplus
-- =====================================================
-- Ce script vide la base de données et insère les données initiales
-- Mot de passe admin: admin (hashé avec bcrypt)
-- =====================================================

-- Désactiver les contraintes de clés étrangères temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- NETTOYAGE DE LA BASE DE DONNÉES
-- =====================================================

TRUNCATE TABLE `_admin_param_module_groupe_permition`;
TRUNCATE TABLE `_admin_param_module`;
TRUNCATE TABLE `_admin_param_groupe_module`;
TRUNCATE TABLE `_admin_param_permition`;
TRUNCATE TABLE `_admin_param_groupe`;
TRUNCATE TABLE `_admin_param_user`;
TRUNCATE TABLE `_admin_param_icon`;
TRUNCATE TABLE `_admin_param_civilite`;
TRUNCATE TABLE `_admin_param_fonction`;
TRUNCATE TABLE `_admin_param_pays`;
TRUNCATE TABLE `_admin_param_ville`;
TRUNCATE TABLE `_admin_param_quartier`;
TRUNCATE TABLE `_admin_param_type_maison`;
TRUNCATE TABLE `_admin_param_motif`;
TRUNCATE TABLE `_admin_param_entreprise`;
TRUNCATE TABLE `_admin_param_annee`;

-- =====================================================
-- 1. PERMISSIONS
-- =====================================================
INSERT INTO `_admin_param_permition` (`id`, `code`, `libelle`) VALUES
(1, 'R', 'Lecture'),
(2, 'CR', 'Lecture et création'),
(3, 'CRUD', 'Tous les droits'),
(4, 'RD', 'Lecture et suppression'),
(5, 'RU', 'Lecture et Modification'),
(6, 'RUD', 'Lecture et modification et suppression'),
(7, 'CRU', 'Lecture et création et modification');

-- =====================================================
-- 2. ICÔNES
-- =====================================================
INSERT INTO `_admin_param_icon` (`id`, `code`, `libelle`, `image`) VALUES
(1, 'HOME', 'Accueil', 'https://api.iconify.design/mdi/home.svg'),
(2, 'DASHBOARD', 'Tableau de bord', 'https://api.iconify.design/mdi/view-dashboard.svg'),
(3, 'USERS', 'Utilisateurs', 'https://api.iconify.design/mdi/account-group.svg'),
(4, 'SETTINGS', 'Paramètres', 'https://api.iconify.design/mdi/cog.svg'),
(5, 'HOUSE', 'Maisons', 'https://api.iconify.design/mdi/home-city.svg'),
(6, 'APARTMENT', 'Appartements', 'https://api.iconify.design/mdi/office-building.svg'),
(7, 'TENANT', 'Locataires', 'https://api.iconify.design/mdi/account-multiple.svg'),
(8, 'OWNER', 'Propriétaires', 'https://api.iconify.design/mdi/account-tie.svg'),
(9, 'CONTRACT', 'Contrats', 'https://api.iconify.design/mdi/file-document.svg'),
(10, 'INVOICE', 'Factures', 'https://api.iconify.design/mdi/receipt.svg'),
(11, 'PAYMENT', 'Paiements', 'https://api.iconify.design/mdi/cash-multiple.svg'),
(12, 'REPORT', 'Rapports', 'https://api.iconify.design/mdi/chart-bar.svg');

-- =====================================================
-- 3. GROUPES DE MODULES (Menu Principal)
-- =====================================================
INSERT INTO `_admin_param_groupe_module` (`id`, `titre`, `ordre`, `lien`, `icon_id`) VALUES
(1, 'Tableau de bord', 1, '/dashboard', 2),
(2, 'Gestion Immobilière', 2, '#', 5),
(3, 'Gestion Locative', 3, '#', 7),
(4, 'Finances', 4, '#', 11),
(5, 'Paramètres', 5, '#', 4),
(6, 'Administration', 6, '#', 3);

-- =====================================================
-- 4. MODULES (Sous-menus)
-- =====================================================
INSERT INTO `_admin_param_module` (`id`, `titre`, `ordre`) VALUES
-- Tableau de bord (pas de sous-menu, lien direct)
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
-- 5. GROUPE ADMINISTRATEUR
-- =====================================================
INSERT INTO `_admin_param_groupe` (`id`, `code`, `libelle`, `description`) VALUES
(1, 'SUPER_ADMIN', 'Super Administrateur', 'Groupe avec tous les droits sur le système');

-- =====================================================
-- 6. LIAISON MODULE-GROUPE-PERMISSION (Configuration du Menu)
-- =====================================================
-- Cette table définit:
-- - Quel module appartient à quel groupe de menu
-- - Quelle permission par défaut
-- - L'ordre d'affichage
-- - Si c'est un menu principal ou sous-menu

-- Tableau de bord (Menu principal direct)
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(1, 1, 3, 1, 1, 1, 1);

-- Gestion Immobilière
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(2, 2, 3, 1, 1, 2, 0),  -- Maisons
(3, 2, 3, 1, 2, 2, 0),  -- Appartements
(4, 2, 3, 1, 3, 2, 0),  -- Quartiers
(5, 2, 3, 1, 4, 2, 0);  -- Types de Maison

-- Gestion Locative
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(6, 3, 3, 1, 1, 3, 0),  -- Locataires
(7, 3, 3, 1, 2, 3, 0),  -- Propriétaires
(8, 3, 3, 1, 3, 3, 0);  -- Contrats

-- Finances
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(9, 4, 3, 1, 1, 4, 0),   -- Factures
(10, 4, 3, 1, 2, 4, 0),  -- Versements Proprio
(11, 4, 3, 1, 3, 4, 0),  -- Comptes Clients
(12, 4, 3, 1, 4, 4, 0);  -- Ligne Versement

-- Paramètres
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(13, 5, 3, 1, 1, 5, 0),  -- Civilités
(14, 5, 3, 1, 2, 5, 0),  -- Fonctions
(15, 5, 3, 1, 3, 5, 0),  -- Pays
(16, 5, 3, 1, 4, 5, 0),  -- Villes
(17, 5, 3, 1, 5, 5, 0),  -- Motifs
(18, 5, 3, 1, 6, 5, 0),  -- Icônes
(19, 5, 3, 1, 7, 5, 0);  -- Années

-- Administration
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(20, 6, 3, 1, 1, 6, 0),  -- Utilisateurs
(21, 6, 3, 1, 2, 6, 0),  -- Groupes
(22, 6, 3, 1, 3, 6, 0),  -- Modules
(23, 6, 3, 1, 4, 6, 0),  -- Permissions
(24, 6, 3, 1, 5, 6, 0);  -- Entreprise

-- =====================================================
-- 7. DONNÉES DE BASE - CIVILITÉS
-- =====================================================
INSERT INTO `_admin_param_civilite` (`id`, `code`, `libelle`) VALUES
(1, 'M', 'Monsieur'),
(2, 'MME', 'Madame'),
(3, 'MLLE', 'Mademoiselle');

-- =====================================================
-- 8. DONNÉES DE BASE - FONCTIONS
-- =====================================================
INSERT INTO `_admin_param_fonction` (`id`, `code`, `libelle`) VALUES
(1, 'DIR', 'Directeur'),
(2, 'GEST', 'Gestionnaire'),
(3, 'COMPT', 'Comptable'),
(4, 'SECR', 'Secrétaire');

-- =====================================================
-- 9. DONNÉES DE BASE - PAYS
-- =====================================================
INSERT INTO `_admin_param_pays` (`id`, `code`, `nom`, `indicatif`) VALUES
(1, 'CI', 'Côte d\'Ivoire', '+225'),
(2, 'SN', 'Sénégal', '+221'),
(3, 'ML', 'Mali', '+223'),
(4, 'BF', 'Burkina Faso', '+226'),
(5, 'FR', 'France', '+33');

-- =====================================================
-- 10. DONNÉES DE BASE - VILLES (Côte d'Ivoire)
-- =====================================================
INSERT INTO `_admin_param_ville` (`id`, `code`, `nom`, `pays_id`) VALUES
(1, 'ABJ', 'Abidjan', 1),
(2, 'YAM', 'Yamoussoukro', 1),
(3, 'BKE', 'Bouaké', 1),
(4, 'SAN', 'San-Pédro', 1),
(5, 'KRG', 'Korhogo', 1);

-- =====================================================
-- 11. DONNÉES DE BASE - QUARTIERS (Abidjan)
-- =====================================================
INSERT INTO `_admin_param_quartier` (`id`, `code`, `nom`, `ville_id`) VALUES
(1, 'COC', 'Cocody', 1),
(2, 'PLT', 'Plateau', 1),
(3, 'YOP', 'Yopougon', 1),
(4, 'ADJ', 'Adjamé', 1),
(5, 'ATT', 'Attecoubé', 1),
(6, 'TRE', 'Treichville', 1),
(7, 'MAR', 'Marcory', 1),
(8, 'KOU', 'Koumassi', 1),
(9, 'PVR', 'Port-Bouët', 1),
(10, 'ABT', 'Abobo', 1);

-- =====================================================
-- 12. DONNÉES DE BASE - TYPES DE MAISON
-- =====================================================
INSERT INTO `_admin_param_type_maison` (`id`, `code`, `libelle`) VALUES
(1, 'VILLA', 'Villa'),
(2, 'DUPLEX', 'Duplex'),
(3, 'STUDIO', 'Studio'),
(4, 'F2', 'F2 - 2 Pièces'),
(5, 'F3', 'F3 - 3 Pièces'),
(6, 'F4', 'F4 - 4 Pièces'),
(7, 'F5', 'F5 - 5 Pièces et plus');

-- =====================================================
-- 13. DONNÉES DE BASE - MOTIFS
-- =====================================================
INSERT INTO `_admin_param_motif` (`id`, `code`, `libelle`) VALUES
(1, 'FIN_BAIL', 'Fin de bail'),
(2, 'RESIL_LOC', 'Résiliation par le locataire'),
(3, 'RESIL_PROP', 'Résiliation par le propriétaire'),
(4, 'NON_PAIEMENT', 'Non-paiement du loyer'),
(5, 'DEGRADATION', 'Dégradation du bien');

-- =====================================================
-- 14. ENTREPRISE PAR DÉFAUT
-- =====================================================
INSERT INTO `_admin_param_entreprise` (`id`, `nom`, `sigle`, `email`, `telephone`, `adresse`, `logo`) VALUES
(1, 'motiplus', 'IP', 'contact@immoplus.ci', '+225 01 02 03 04 05', 'Abidjan, Cocody', '/logo.png');

-- =====================================================
-- 15. UTILISATEUR ADMINISTRATEUR
-- =====================================================
-- Mot de passe: admin (hashé avec bcrypt)
-- Hash: $2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `_admin_param_user` (`id`, `nom`, `prenom`, `email`, `login`, `password`, `telephone`, `groupe_id`, `entreprise_id`, `is_active`, `created_at`) VALUES
(1, 'Admin', 'Super', 'admin@immoplus.ci', 'admin', '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+225 01 00 00 00 00', 1, 1, 1, NOW());

-- =====================================================
-- 16. ANNÉE ACADÉMIQUE EN COURS
-- =====================================================
INSERT INTO `_admin_param_annee` (`id`, `libelle`, `date_debut`, `date_fin`, `etat`) VALUES
(1, '2024-2025', '2024-01-01', '2024-12-31', 1);

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- RÉSUMÉ DE LA CONFIGURATION DU MENU
-- =====================================================
-- 
-- Structure du menu:
-- 
-- 1. 📊 Tableau de bord (menu principal direct)
--    └─ Dashboard
-- 
-- 2. 🏘️ Gestion Immobilière (menu déroulant)
--    ├─ Maisons
--    ├─ Appartements
--    ├─ Quartiers
--    └─ Types de Maison
-- 
-- 3. 👥 Gestion Locative (menu déroulant)
--    ├─ Locataires
--    ├─ Propriétaires
--    └─ Contrats de Location
-- 
-- 4. 💰 Finances (menu déroulant)
--    ├─ Factures de Location
--    ├─ Versements Propriétaires
--    ├─ Comptes Clients
--    └─ Ligne Versement Frais
-- 
-- 5. ⚙️ Paramètres (menu déroulant)
--    ├─ Civilités
--    ├─ Fonctions
--    ├─ Pays
--    ├─ Villes
--    ├─ Motifs
--    ├─ Icônes
--    └─ Années
-- 
-- 6. 🔐 Administration (menu déroulant)
--    ├─ Utilisateurs
--    ├─ Groupes
--    ├─ Modules
--    ├─ Permissions
--    └─ Entreprise
-- 
-- Tous les modules ont la permission CRUD (id=3) pour le groupe Super Admin (id=1)
-- 
-- =====================================================
-- FIN DU SCRIPT
-- =====================================================
