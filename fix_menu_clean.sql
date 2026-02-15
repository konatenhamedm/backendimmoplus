SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `_admin_param_module_groupe_permition`;
DELETE FROM `_admin_param_module`;
DELETE FROM `_admin_param_groupe_module`;

ALTER TABLE `_admin_param_module_groupe_permition` AUTO_INCREMENT = 1;
ALTER TABLE `_admin_param_module` AUTO_INCREMENT = 1;
ALTER TABLE `_admin_param_groupe_module` AUTO_INCREMENT = 1;

INSERT INTO `_admin_param_groupe_module` (`id`, `titre`, `ordre`, `lien`, `icon_id`) VALUES
(1, 'Tableau de bord', 1, '/dashboard', 2),
(2, 'Gestion Immobilière', 2, '#', 5),
(3, 'Gestion Locative', 3, '#', 7),
(4, 'Finances', 4, '#', 11),
(5, 'Paramètres', 5, '#', 4),
(6, 'Administration', 6, '#', 3);

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

INSERT INTO `_admin_param_module_groupe_permition` (`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) VALUES
(1, 1, 3, 1, 1, 1, 1),
(2, 2, 3, 1, 1, 2, 0),
(3, 2, 3, 1, 2, 2, 0),
(4, 2, 3, 1, 3, 2, 0),
(5, 2, 3, 1, 4, 2, 0),
(6, 3, 3, 1, 1, 3, 0),
(7, 3, 3, 1, 2, 3, 0),
(8, 3, 3, 1, 3, 3, 0),
(9, 4, 3, 1, 1, 4, 0),
(10, 4, 3, 1, 2, 4, 0),
(11, 4, 3, 1, 3, 4, 0),
(12, 4, 3, 1, 4, 4, 0),
(13, 5, 3, 1, 1, 5, 0),
(14, 5, 3, 1, 2, 5, 0),
(15, 5, 3, 1, 3, 5, 0),
(16, 5, 3, 1, 4, 5, 0),
(17, 5, 3, 1, 5, 5, 0),
(18, 5, 3, 1, 6, 5, 0),
(19, 5, 3, 1, 7, 5, 0),
(20, 6, 3, 1, 1, 6, 0),
(21, 6, 3, 1, 2, 6, 0),
(22, 6, 3, 1, 3, 6, 0),
(23, 6, 3, 1, 4, 6, 0),
(24, 6, 3, 1, 5, 6, 0);

SET FOREIGN_KEY_CHECKS = 1;
