-- =====================================================
-- Script pour ajouter les liens (routes) aux modules
-- =====================================================

-- Étape 1: Ajouter la colonne 'lien' à la table _admin_param_module
ALTER TABLE `_admin_param_module` ADD COLUMN `lien` VARCHAR(255) NULL AFTER `ordre`;

-- Étape 2: Mettre à jour les liens pour chaque module

-- Tableau de bord
UPDATE `_admin_param_module` SET `lien` = '/dashboard' WHERE `id` = 1;

-- Gestion Immobilière
UPDATE `_admin_param_module` SET `lien` = '/maison' WHERE `id` = 2;
UPDATE `_admin_param_module` SET `lien` = '/appartement' WHERE `id` = 3;
UPDATE `_admin_param_module` SET `lien` = '/quartier' WHERE `id` = 4;
UPDATE `_admin_param_module` SET `lien` = '/type-maison' WHERE `id` = 5;

-- Gestion Locative
UPDATE `_admin_param_module` SET `lien` = '/locataire' WHERE `id` = 6;
UPDATE `_admin_param_module` SET `lien` = '/proprietaire' WHERE `id` = 7;
UPDATE `_admin_param_module` SET `lien` = '/contrat-location' WHERE `id` = 8;

-- Finances
UPDATE `_admin_param_module` SET `lien` = '/facture-location' WHERE `id` = 9;
UPDATE `_admin_param_module` SET `lien` = '/versement-proprio' WHERE `id` = 10;
UPDATE `_admin_param_module` SET `lien` = '/compte-clt-t' WHERE `id` = 11;
UPDATE `_admin_param_module` SET `lien` = '/ligne-versement-frais' WHERE `id` = 12;

-- Paramètres
UPDATE `_admin_param_module` SET `lien` = '/civilite' WHERE `id` = 13;
UPDATE `_admin_param_module` SET `lien` = '/fonction' WHERE `id` = 14;
UPDATE `_admin_param_module` SET `lien` = '/pays' WHERE `id` = 15;
UPDATE `_admin_param_module` SET `lien` = '/ville' WHERE `id` = 16;
UPDATE `_admin_param_module` SET `lien` = '/motif' WHERE `id` = 17;
UPDATE `_admin_param_module` SET `lien` = '/icon' WHERE `id` = 18;
UPDATE `_admin_param_module` SET `lien` = '/annee' WHERE `id` = 19;

-- Administration
UPDATE `_admin_param_module` SET `lien` = '/utilisateurs' WHERE `id` = 20;
UPDATE `_admin_param_module` SET `lien` = '/groupe' WHERE `id` = 21;
UPDATE `_admin_param_module` SET `lien` = '/module' WHERE `id` = 22;
UPDATE `_admin_param_module` SET `lien` = '/permission' WHERE `id` = 23;
UPDATE `_admin_param_module` SET `lien` = '/entreprise' WHERE `id` = 24;

-- Vérification
SELECT id, titre, lien FROM `_admin_param_module` ORDER BY id;
