# 🔧 CORRECTION URGENTE - GroupeModule NULL

## ⚠️ Problème Identifié

Les `groupe_module_id` sont NULL dans la table `_admin_param_module_groupe_permition`.
Cela empêche le menu de s'afficher correctement.

## ✅ Solution

Exécutez le script de correction `fix_menu_clean.sql`

---

## 📋 Méthode 1: Via MySQL en ligne de commande

```bash
cd /Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api

# Remplacez 'votre_user' et 'votre_database' par vos identifiants
mysql -u votre_user -p votre_database < fix_menu_clean.sql

# Exemple:
mysql -u root -p immoplus < fix_menu_clean.sql
```

Entrez votre mot de passe MySQL quand demandé.

---

## 📋 Méthode 2: Via phpMyAdmin

1. **Ouvrir phpMyAdmin** dans votre navigateur
   - URL: `http://localhost/phpmyadmin`

2. **Sélectionner votre base de données**
   - Cliquer sur `immoplus` dans le panneau gauche

3. **Aller dans l'onglet SQL**
   - Cliquer sur l'onglet "SQL" en haut

4. **Copier-coller le contenu du fichier**
   - Ouvrir le fichier `fix_menu_clean.sql`
   - Copier tout le contenu
   - Coller dans la zone de texte SQL

5. **Exécuter**
   - Cliquer sur "Exécuter" ou "Go"

---

## 📋 Méthode 3: Via Symfony Console (Recommandé)

Exécutez ces commandes une par une:

```bash
cd /Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api

# 1. Désactiver les contraintes
php bin/console dbal:run-sql "SET FOREIGN_KEY_CHECKS = 0"

# 2. Vider les tables
php bin/console dbal:run-sql "DELETE FROM _admin_param_module_groupe_permition"
php bin/console dbal:run-sql "DELETE FROM _admin_param_module"
php bin/console dbal:run-sql "DELETE FROM _admin_param_groupe_module"

# 3. Réinitialiser les auto-increment
php bin/console dbal:run-sql "ALTER TABLE _admin_param_module_groupe_permition AUTO_INCREMENT = 1"
php bin/console dbal:run-sql "ALTER TABLE _admin_param_module AUTO_INCREMENT = 1"
php bin/console dbal:run-sql "ALTER TABLE _admin_param_groupe_module AUTO_INCREMENT = 1"

# 4. Insérer les groupes de modules
php bin/console dbal:run-sql "INSERT INTO _admin_param_groupe_module (id, titre, ordre, lien, icon_id) VALUES (1, 'Tableau de bord', 1, '/dashboard', 2), (2, 'Gestion Immobilière', 2, '#', 5), (3, 'Gestion Locative', 3, '#', 7), (4, 'Finances', 4, '#', 11), (5, 'Paramètres', 5, '#', 4), (6, 'Administration', 6, '#', 3)"

# 5. Insérer les modules
php bin/console dbal:run-sql "INSERT INTO _admin_param_module (id, titre, ordre) VALUES (1, 'Dashboard', 1), (2, 'Maisons', 1), (3, 'Appartements', 2), (4, 'Quartiers', 3), (5, 'Types de Maison', 4), (6, 'Locataires', 1), (7, 'Propriétaires', 2), (8, 'Contrats de Location', 3), (9, 'Factures de Location', 1), (10, 'Versements Propriétaires', 2), (11, 'Comptes Clients', 3), (12, 'Ligne Versement Frais', 4), (13, 'Civilités', 1), (14, 'Fonctions', 2), (15, 'Pays', 3), (16, 'Villes', 4), (17, 'Motifs', 5), (18, 'Icônes', 6), (19, 'Années', 7), (20, 'Utilisateurs', 1), (21, 'Groupes', 2), (22, 'Modules', 3), (23, 'Permissions', 4), (24, 'Entreprise', 5)"

# 6. Insérer la configuration (partie 1)
php bin/console dbal:run-sql "INSERT INTO _admin_param_module_groupe_permition (module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES (1, 1, 3, 1, 1, 1, 1), (2, 2, 3, 1, 1, 2, 0), (3, 2, 3, 1, 2, 2, 0), (4, 2, 3, 1, 3, 2, 0), (5, 2, 3, 1, 4, 2, 0), (6, 3, 3, 1, 1, 3, 0), (7, 3, 3, 1, 2, 3, 0), (8, 3, 3, 1, 3, 3, 0)"

# 7. Insérer la configuration (partie 2)
php bin/console dbal:run-sql "INSERT INTO _admin_param_module_groupe_permition (module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES (9, 4, 3, 1, 1, 4, 0), (10, 4, 3, 1, 2, 4, 0), (11, 4, 3, 1, 3, 4, 0), (12, 4, 3, 1, 4, 4, 0), (13, 5, 3, 1, 1, 5, 0), (14, 5, 3, 1, 2, 5, 0), (15, 5, 3, 1, 3, 5, 0), (16, 5, 3, 1, 4, 5, 0)"

# 8. Insérer la configuration (partie 3)
php bin/console dbal:run-sql "INSERT INTO _admin_param_module_groupe_permition (module_id, groupe_module_id, permition_id, groupe_user_id, ordre, ordre_groupe, menu_principal) VALUES (17, 5, 3, 1, 5, 5, 0), (18, 5, 3, 1, 6, 5, 0), (19, 5, 3, 1, 7, 5, 0), (20, 6, 3, 1, 1, 6, 0), (21, 6, 3, 1, 2, 6, 0), (22, 6, 3, 1, 3, 6, 0), (23, 6, 3, 1, 4, 6, 0), (24, 6, 3, 1, 5, 6, 0)"

# 9. Réactiver les contraintes
php bin/console dbal:run-sql "SET FOREIGN_KEY_CHECKS = 1"
```

---

## ✅ Vérification

Après l'exécution, vérifiez que tout est correct:

```bash
# Vérifier qu'il n'y a plus de NULL
php bin/console dbal:run-sql "SELECT COUNT(*) as total_null FROM _admin_param_module_groupe_permition WHERE groupe_module_id IS NULL"

# Devrait retourner: total_null = 0

# Afficher la configuration complète
php bin/console dbal:run-sql "SELECT m.titre as module, gm.titre as groupe, mgp.ordre, mgp.menu_principal FROM _admin_param_module_groupe_permition mgp JOIN _admin_param_module m ON mgp.module_id = m.id JOIN _admin_param_groupe_module gm ON mgp.groupe_module_id = gm.id ORDER BY mgp.ordre_groupe, mgp.ordre"
```

---

## 🎯 Résultat Attendu

Après correction, vous devriez avoir:
- ✅ 6 groupes de modules
- ✅ 24 modules
- ✅ 24 liaisons avec `groupe_module_id` NON NULL
- ✅ Menu complet et fonctionnel

---

## 📞 En Cas de Problème

Si vous rencontrez des erreurs:

1. **Erreur de clé étrangère**: Vérifiez que les contraintes sont désactivées
2. **Erreur de duplication**: Les tables n'ont pas été vidées correctement
3. **Erreur de connexion**: Vérifiez vos identifiants MySQL

---

**Choisissez la méthode qui vous convient le mieux et exécutez le script!**
