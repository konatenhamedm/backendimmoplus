# 📋 Structure du Menu ImmoPlus - Explication Détaillée

## 🗂️ Tables de Gestion du Menu

Le système de menu d'ImmoPlus utilise 3 tables principales:

### 1. `_admin_param_groupe_module` (Groupes de Menu)
**Rôle**: Définit les menus principaux (sections du menu latéral)

**Colonnes**:
- `id`: Identifiant unique
- `titre`: Nom affiché du groupe (ex: "Gestion Immobilière")
- `ordre`: Ordre d'affichage dans le menu (1, 2, 3...)
- `lien`: URL du lien (# pour menu déroulant, /path pour lien direct)
- `icon_id`: Référence vers l'icône à afficher

**Exemple**:
```sql
(2, 'Gestion Immobilière', 2, '#', 5)
```
→ Menu "Gestion Immobilière", 2ème position, menu déroulant (#), icône maison

---

### 2. `_admin_param_module` (Modules/Sous-menus)
**Rôle**: Définit les éléments de sous-menu

**Colonnes**:
- `id`: Identifiant unique
- `titre`: Nom affiché du module (ex: "Maisons")
- `ordre`: Ordre dans le sous-menu (1, 2, 3...)

**Exemple**:
```sql
(2, 'Maisons', 1)
```
→ Module "Maisons", 1ère position dans son groupe

---

### 3. `_admin_param_module_groupe_permition` (Liaison & Configuration)
**Rôle**: Lie les modules aux groupes et définit les permissions

**Colonnes**:
- `id`: Identifiant unique
- `module_id`: Référence vers le module
- `groupe_module_id`: Référence vers le groupe de menu
- `permition_id`: Permission par défaut (R, CR, CRUD, etc.)
- `groupe_user_id`: Groupe d'utilisateurs concerné
- `ordre`: Ordre du module dans le groupe
- `ordre_groupe`: Ordre du groupe dans le menu principal
- `menu_principal`: Boolean (1 = menu principal direct, 0 = sous-menu)

**Exemple**:
```sql
(2, 2, 3, 1, 1, 2, 0)
```
→ Module "Maisons" (id=2), dans groupe "Gestion Immobilière" (id=2), permission CRUD (id=3), pour groupe Super Admin (id=1), 1er dans le sous-menu, groupe en 2ème position, c'est un sous-menu (0)

---

## 🎯 Configuration Actuelle du Menu

### Structure Hiérarchique

```
📊 1. Tableau de bord (ordre: 1, menu principal)
   └─ Dashboard (lien direct: /dashboard)

🏘️ 2. Gestion Immobilière (ordre: 2, menu déroulant)
   ├─ Maisons (ordre: 1)
   ├─ Appartements (ordre: 2)
   ├─ Quartiers (ordre: 3)
   └─ Types de Maison (ordre: 4)

👥 3. Gestion Locative (ordre: 3, menu déroulant)
   ├─ Locataires (ordre: 1)
   ├─ Propriétaires (ordre: 2)
   └─ Contrats de Location (ordre: 3)

💰 4. Finances (ordre: 4, menu déroulant)
   ├─ Factures de Location (ordre: 1)
   ├─ Versements Propriétaires (ordre: 2)
   ├─ Comptes Clients (ordre: 3)
   └─ Ligne Versement Frais (ordre: 4)

⚙️ 5. Paramètres (ordre: 5, menu déroulant)
   ├─ Civilités (ordre: 1)
   ├─ Fonctions (ordre: 2)
   ├─ Pays (ordre: 3)
   ├─ Villes (ordre: 4)
   ├─ Motifs (ordre: 5)
   ├─ Icônes (ordre: 6)
   └─ Années (ordre: 7)

🔐 6. Administration (ordre: 6, menu déroulant)
   ├─ Utilisateurs (ordre: 1)
   ├─ Groupes (ordre: 2)
   ├─ Modules (ordre: 3)
   ├─ Permissions (ordre: 4)
   └─ Entreprise (ordre: 5)
```

---

## 🔑 Explication des Champs Clés

### `menu_principal` (Boolean)
- **1 (true)**: Le module est un menu principal direct (ex: Dashboard)
  - Pas de sous-menu
  - Lien direct dans le menu
  - Clic = navigation immédiate

- **0 (false)**: Le module est un sous-menu
  - Appartient à un groupe déroulant
  - Nécessite de cliquer sur le groupe parent d'abord

### `ordre` vs `ordre_groupe`
- **`ordre`**: Position du module DANS son groupe
  - Ex: "Maisons" est le 1er élément de "Gestion Immobilière"
  
- **`ordre_groupe`**: Position du GROUPE dans le menu principal
  - Ex: "Gestion Immobilière" est le 2ème groupe du menu

### `lien` dans GroupeModule
- **`#`**: Menu déroulant (pas de navigation directe)
- **`/path`**: Lien direct (navigation immédiate)

---

## 📊 Mapping Complet

### Groupe 1: Tableau de bord
| Module ID | Titre | Ordre | Menu Principal | Route |
|-----------|-------|-------|----------------|-------|
| 1 | Dashboard | 1 | ✅ Oui | /dashboard |

### Groupe 2: Gestion Immobilière
| Module ID | Titre | Ordre | Menu Principal | Route |
|-----------|-------|-------|----------------|-------|
| 2 | Maisons | 1 | ❌ Non | /maison |
| 3 | Appartements | 2 | ❌ Non | /appartement |
| 4 | Quartiers | 3 | ❌ Non | /quartier |
| 5 | Types de Maison | 4 | ❌ Non | /type-maison |

### Groupe 3: Gestion Locative
| Module ID | Titre | Ordre | Menu Principal | Route |
|-----------|-------|-------|----------------|-------|
| 6 | Locataires | 1 | ❌ Non | /locataire |
| 7 | Propriétaires | 2 | ❌ Non | /proprietaire |
| 8 | Contrats de Location | 3 | ❌ Non | /contrat-location |

### Groupe 4: Finances
| Module ID | Titre | Ordre | Menu Principal | Route |
|-----------|-------|-------|----------------|-------|
| 9 | Factures de Location | 1 | ❌ Non | /facture-location |
| 10 | Versements Propriétaires | 2 | ❌ Non | /versement-proprio |
| 11 | Comptes Clients | 3 | ❌ Non | /compte-clt-t |
| 12 | Ligne Versement Frais | 4 | ❌ Non | /ligne-versement-frais |

### Groupe 5: Paramètres
| Module ID | Titre | Ordre | Menu Principal | Route |
|-----------|-------|-------|----------------|-------|
| 13 | Civilités | 1 | ❌ Non | /civilite |
| 14 | Fonctions | 2 | ❌ Non | /fonction |
| 15 | Pays | 3 | ❌ Non | /pays |
| 16 | Villes | 4 | ❌ Non | /ville |
| 17 | Motifs | 5 | ❌ Non | /motif |
| 18 | Icônes | 6 | ❌ Non | /icon |
| 19 | Années | 7 | ❌ Non | /annee |

### Groupe 6: Administration
| Module ID | Titre | Ordre | Menu Principal | Route |
|-----------|-------|-------|----------------|-------|
| 20 | Utilisateurs | 1 | ❌ Non | /utilisateurs |
| 21 | Groupes | 2 | ❌ Non | /groupe |
| 22 | Modules | 3 | ❌ Non | /module |
| 23 | Permissions | 4 | ❌ Non | /permission |
| 24 | Entreprise | 5 | ❌ Non | /entreprise |

---

## 🔐 Permissions par Défaut

Tous les modules ont la permission **CRUD (id=3)** pour le groupe **Super Admin (id=1)**

**Signification**:
- **C**reate: Peut créer
- **R**ead: Peut lire/consulter
- **U**pdate: Peut modifier
- **D**elete: Peut supprimer

---

## 🎨 Icônes Associées

| Groupe | Icône ID | Icône | URL |
|--------|----------|-------|-----|
| Tableau de bord | 2 | Dashboard | mdi/view-dashboard |
| Gestion Immobilière | 5 | Maisons | mdi/home-city |
| Gestion Locative | 7 | Locataires | mdi/account-multiple |
| Finances | 11 | Paiements | mdi/cash-multiple |
| Paramètres | 4 | Settings | mdi/cog |
| Administration | 3 | Users | mdi/account-group |

---

## 🔄 Comment Modifier le Menu

### Ajouter un nouveau module

```sql
-- 1. Ajouter le module
INSERT INTO `_admin_param_module` (`titre`, `ordre`) VALUES
('Nouveau Module', 1);

-- 2. Lier au groupe avec permissions
INSERT INTO `_admin_param_module_groupe_permition` 
(`module_id`, `groupe_module_id`, `permition_id`, `groupe_user_id`, `ordre`, `ordre_groupe`, `menu_principal`) 
VALUES
(25, 2, 3, 1, 5, 2, 0);
-- Module 25, dans groupe 2 (Gestion Immobilière), CRUD, Super Admin, 5ème position, groupe en 2ème, sous-menu
```

### Changer l'ordre d'un groupe

```sql
UPDATE `_admin_param_groupe_module` 
SET `ordre` = 3 
WHERE `id` = 2;
-- Met "Gestion Immobilière" en 3ème position
```

### Changer l'ordre d'un module

```sql
UPDATE `_admin_param_module_groupe_permition` 
SET `ordre` = 2 
WHERE `module_id` = 2 AND `groupe_module_id` = 2;
-- Met "Maisons" en 2ème position dans son groupe
```

---

## 📱 Rendu Frontend Attendu

```tsx
<Sidebar>
  {/* Groupe 1 - Menu principal direct */}
  <MenuItem icon="dashboard" href="/dashboard">
    Tableau de bord
  </MenuItem>

  {/* Groupe 2 - Menu déroulant */}
  <MenuGroup icon="house" title="Gestion Immobilière">
    <MenuItem href="/maison">Maisons</MenuItem>
    <MenuItem href="/appartement">Appartements</MenuItem>
    <MenuItem href="/quartier">Quartiers</MenuItem>
    <MenuItem href="/type-maison">Types de Maison</MenuItem>
  </MenuGroup>

  {/* Groupe 3 - Menu déroulant */}
  <MenuGroup icon="users" title="Gestion Locative">
    <MenuItem href="/locataire">Locataires</MenuItem>
    <MenuItem href="/proprietaire">Propriétaires</MenuItem>
    <MenuItem href="/contrat-location">Contrats de Location</MenuItem>
  </MenuGroup>

  {/* ... etc */}
</Sidebar>
```

---

## ✅ Vérification Post-Installation

Après avoir exécuté le script SQL, vérifiez:

```sql
-- Nombre de groupes de modules
SELECT COUNT(*) FROM _admin_param_groupe_module;
-- Devrait retourner: 6

-- Nombre de modules
SELECT COUNT(*) FROM _admin_param_module;
-- Devrait retourner: 24

-- Nombre de liaisons (configuration menu)
SELECT COUNT(*) FROM _admin_param_module_groupe_permition;
-- Devrait retourner: 24

-- Afficher la structure complète du menu
SELECT 
    gm.ordre AS groupe_ordre,
    gm.titre AS groupe,
    m.titre AS module,
    mgp.ordre AS module_ordre,
    mgp.menu_principal,
    p.code AS permission
FROM _admin_param_module_groupe_permition mgp
JOIN _admin_param_groupe_module gm ON mgp.groupe_module_id = gm.id
JOIN _admin_param_module m ON mgp.module_id = m.id
JOIN _admin_param_permition p ON mgp.permition_id = p.id
WHERE mgp.groupe_user_id = 1
ORDER BY gm.ordre, mgp.ordre;
```

---

**Le menu est maintenant complètement configuré et prêt à être utilisé dans le frontend!** 🎉
