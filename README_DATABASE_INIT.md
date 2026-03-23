# 🗄️ Guide d'Initialisation de la Base de Données motiplus

## 📋 Vue d'ensemble

Ce script SQL initialise complètement la base de données motiplus avec:
- Les permissions système
- Un utilisateur administrateur (login: `admin`, mot de passe: `admin`)
- Toutes les données de base nécessaires au fonctionnement de l'application

## 🚀 Méthode 1: Ligne de commande MySQL

### Prérequis
- MySQL installé et en cours d'exécution
- Base de données créée (ex: `immoplus`)
- Accès avec un utilisateur ayant les droits nécessaires

### Commandes

```bash
# 1. Se placer dans le dossier du projet API
cd /Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api

# 2. Exécuter le script
mysql -u votre_utilisateur -p votre_base_de_donnees < init_database.sql

# Exemple concret:
mysql -u root -p immoplus < init_database.sql
```

Vous serez invité à entrer le mot de passe MySQL.

## 🌐 Méthode 2: phpMyAdmin

### Étapes

1. **Ouvrir phpMyAdmin** dans votre navigateur
   - URL habituelle: `http://localhost/phpmyadmin`

2. **Sélectionner votre base de données**
   - Cliquer sur le nom de votre base (ex: `immoplus`) dans le panneau gauche

3. **Aller dans l'onglet "Importer"**
   - Cliquer sur l'onglet "Importer" en haut

4. **Sélectionner le fichier**
   - Cliquer sur "Parcourir" ou "Choose File"
   - Naviguer vers: `/Volumes/konate/PERSONNEL/CONSTRUCTION/immoplus_version_api/init_database.sql`
   - Sélectionner le fichier

5. **Lancer l'importation**
   - Faire défiler vers le bas
   - Cliquer sur "Exécuter" ou "Go"

6. **Vérifier le succès**
   - Vous devriez voir un message de succès
   - Vérifier que les tables contiennent des données

## 🔐 Informations de Connexion

Après l'exécution du script, vous pouvez vous connecter avec:

- **Login**: `admin`
- **Mot de passe**: `admin`
- **Email**: `admin@immoplus.ci`
- **Groupe**: Super Administrateur
- **Permissions**: CRUD (tous les droits) sur tous les modules

## ✅ Vérification

Pour vérifier que tout s'est bien passé:

```sql
-- Vérifier les permissions
SELECT * FROM _admin_param_permition;
-- Devrait retourner 7 lignes

-- Vérifier l'utilisateur admin
SELECT * FROM _admin_param_user WHERE login = 'admin';
-- Devrait retourner 1 ligne

-- Vérifier les modules
SELECT COUNT(*) FROM _admin_param_module;
-- Devrait retourner 23

-- Vérifier les permissions du groupe admin
SELECT COUNT(*) FROM _admin_param_module_groupe_permition WHERE groupe_id = 1;
-- Devrait retourner 23 (CRUD sur tous les modules)
```

## 📊 Données Insérées

Le script insère les données suivantes:

| Table | Nombre d'enregistrements |
|-------|-------------------------|
| Permissions | 7 |
| Icônes | 12 |
| Groupes de modules | 6 |
| Modules | 23 |
| Groupe (Super Admin) | 1 |
| Permissions du groupe | 23 |
| Civilités | 3 |
| Fonctions | 4 |
| Pays | 5 |
| Villes | 5 |
| Quartiers | 10 |
| Types de maison | 7 |
| Motifs | 5 |
| Entreprise | 1 |
| Utilisateur admin | 1 |
| Année académique | 1 |

## ⚠️ Avertissements

### 🔴 ATTENTION: Ce script VIDE la base de données!

Le script utilise `TRUNCATE TABLE` pour vider toutes les tables avant d'insérer les nouvelles données.

**Toutes les données existantes seront SUPPRIMÉES!**

### Recommandations:

1. **Sauvegarde**: Faites une sauvegarde de votre base avant d'exécuter le script
   ```bash
   mysqldump -u root -p immoplus > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Environnement de développement**: Exécutez d'abord sur une base de développement

3. **Production**: NE PAS exécuter sur une base de production avec des données réelles!

## 🔄 Réexécution

Le script peut être exécuté plusieurs fois sans problème:
- Les `TRUNCATE` vident les tables
- Les `INSERT` ajoutent les nouvelles données
- Les contraintes de clés étrangères sont temporairement désactivées

## 🛠️ Personnalisation

### Changer le mot de passe admin

1. **Générer un nouveau hash** (en PHP):
   ```php
   <?php
   echo password_hash('votre_nouveau_mot_de_passe', PASSWORD_BCRYPT, ['cost' => 13]);
   ?>
   ```

2. **Modifier le script SQL**:
   - Remplacer le hash dans la ligne INSERT de `_admin_param_user`
   - Ligne ~215 du fichier `init_database.sql`

### Ajouter d'autres données

Vous pouvez ajouter vos propres INSERT à la fin du script:
- Propriétaires
- Locataires
- Maisons
- Appartements
- etc.

## 🐛 Dépannage

### Erreur: "Table doesn't exist"
- Vérifiez que les tables existent dans votre base
- Exécutez d'abord les migrations Symfony: `php bin/console doctrine:migrations:migrate`

### Erreur: "Foreign key constraint fails"
- Le script désactive temporairement les contraintes
- Vérifiez que `SET FOREIGN_KEY_CHECKS = 0;` est bien au début du script

### Erreur: "Access denied"
- Vérifiez vos identifiants MySQL
- Assurez-vous que l'utilisateur a les droits INSERT, DELETE, TRUNCATE

### Le mot de passe ne fonctionne pas
- Le hash bcrypt doit correspondre exactement
- Vérifiez que le coût est bien 13: `['cost' => 13]`
- Testez avec le mot de passe par défaut: `admin`

## 📞 Support

Pour toute question ou problème:
- Consultez la documentation Symfony
- Vérifiez les logs MySQL
- Contactez l'équipe de développement

---

**Dernière mise à jour**: 11 février 2026
**Version du script**: 1.0
**Compatibilité**: MySQL 5.7+, MariaDB 10.2+
