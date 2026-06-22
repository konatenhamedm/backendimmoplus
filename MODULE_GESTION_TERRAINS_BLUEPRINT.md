# Blueprint : Module de Gestion des Terrains (Indépendant & Avancé)

Ce document décrit l'architecture complète pour la mise en place d'un module de gestion des terrains dans l'application ImmoPlus.
Ce module est **100% indépendant** (aucun lien avec la location/résidence) et inclut une gestion avancée des démarches administratives, de la documentation, et de la cartographie.

---

## 1. Modélisation de la Base de Données (Backend Symfony)

### 1.1 Entités de Base (Catalogue)
*   **`Site`** :
    *   Relations : `Agence`, `Entreprise`.
    *   Nouveaux champs géographiques : `situationGeographique`, `superficieTotale`.
    *   Fichiers joints : `planLotissement` (Relation vers Fichier pour le plan global du site montrant tous les lots).
*   **`Terrain`** :
    *   Relations : `Agence`, `Entreprise`. (Les anciens liens avec `CompteCltT` et `Echancier` sont supprimés).
    *   Dimensions et Cartographie : `dimensions` (ex: 20x25), `superfice` (ex: 500m²).
    *   Fichiers joints : `planTopographique` (Relation vers Fichier spécifique au lot).

### 1.2 Entités Financières 100% Indépendantes
*   **`ClientTerrain`** : Entité client isolée (Nom, contact, pièces d'identité).
*   **`VenteTerrain`** : Le dossier principal liant `Terrain` et `ClientTerrain`.
    *   Champs de Vente : `prixVente`, `apportInitial`, `resteAPayer`.
    *   **Nouveau :** `typeVente` (`avec_papier`, `sans_papier_gestion_agence`).
*   **`EchancierTerrain` & `VersementTerrain`** : Pour gérer la facturation échelonnée et les encaissements, strictement liés à `VenteTerrain`.

### 1.3 Moteur de Workflow & Démarches (Nouveauté)
Si la vente est "sans papier" (l'agence gère les démarches), le système active le workflow :
*   **`DemarcheAdministrative`** : Lié à `VenteTerrain`. Gère le coût total de la démarche et le statut global.
*   **`EtapeDemarche`** : Lié à `DemarcheAdministrative`. Représente chaque sous-étape (ex: 1. Lettre d'attribution, 2. ACD, 3. Notaire).
    *   Champs : `nomEtape`, `statut` (En attente, En cours, Terminé), `dateValidation`.
*   **`DocumentVenteTerrain`** : Permet de joindre tous les documents générés à chaque étape (scans, PDF) au dossier.

---

## 2. Architecture des APIs (Backend Symfony)

*   `GET/POST /api/terrains/sites` et `/api/terrains/lots` (Catalogue)
*   `GET/POST /api/terrains/ventes` (Ventes)
*   **Nouveaux Endpoints Workflow :**
    *   `POST /api/terrains/demarches/{id}/etape/{etapeId}` : Fait passer l'étape à "Terminé".
    *   **Automatisme (Event Subscriber) :** À chaque fois que ce point d'accès est appelé pour valider une étape, le backend déclenche un envoi d'email automatique au client pour l'informer ("Votre ACD est prêt", "Votre dossier est chez le notaire").

---

## 3. Architecture Frontend (Next.js)

Dossier : `app/(dashboard)/module-terrains/`

### 3.1 Vues Classiques
*   **`sites/page.tsx`** : Affichage des sites. Si un `planLotissement` (image) est disponible, un bouton "Voir le plan global" affiche le plan en plein écran.
*   **`terrains/page.tsx`** : Liste avec filtres sur la superficie (ex: "Voir tous les lots de 500m²").
*   **`ventes/page.tsx`** : Dossiers de ventes avec gestionnaire de documents (Upload drag & drop).

### 3.2 Vue Avancée : Le Kanban des Démarches
*   **`demarches/page.tsx`** : Une interface de type **Kanban (Tableau de type Trello)**.
    *   Colonnes : "À lancer", "En cours (Ministère/Notaire)", "Terminé".
    *   Les dossiers des clients s'affichent sous forme de cartes.
    *   L'agent déplace la carte dans la colonne suivante. Cela met à jour le statut dans la base de données et envoie instantanément l'alerte email au client.

---

## 4. Propositions Complémentaires (Optionnelles)

*   **Plan Interactif SVG :** Si vous fournissez les plans de masse en format SVG, le frontend peut colorer automatiquement les lots (Vert = Dispo, Rouge = Vendu) en se basant sur les identifiants de la base de données.
*   **Facturation des Démarches :** Nous recommandons que les `fraisEstimes` des démarches administratives fassent l'objet de paiements (Versements) distincts du prix d'achat initial du terrain, afin de garder une comptabilité claire.

*Validez ce Blueprint complet pour déclencher l'implémentation de la Phase 1.*
