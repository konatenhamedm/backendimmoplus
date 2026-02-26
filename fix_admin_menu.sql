-- Supprimer l'Espace Locataire du menu des Administrateurs
DELETE FROM `_admin_param_module_groupe_permition` 
WHERE `module_id` IN (SELECT `id` FROM `_admin_param_module` WHERE `titre` = 'Espace Locataire')
AND `groupe_user_id` IN (SELECT `id` FROM `_admin_param_groupe` WHERE `code` IN ('SADM', 'ADMIN', 'ADS'));
