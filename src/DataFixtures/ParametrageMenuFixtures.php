<?php

namespace App\DataFixtures;

use App\Entity\Agence;
use App\Entity\Civilite;
use App\Entity\Employe;
use App\Entity\Entreprise;
use App\Entity\Fonction;
use App\Entity\Groupe;
use App\Entity\GroupeModule;
use App\Entity\Icon;
use App\Entity\Locataire;
use App\Entity\Module;
use App\Entity\ModuleGroupePermition;
use App\Entity\Permition;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParametrageMenuFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Icons
        $iconsData = [
            1 => ['code' => 'LayoutDashboard', 'libelle' => 'Tableau de bord'],
            2 => ['code' => 'Users', 'libelle' => 'Utilisateurs'],
            3 => ['code' => 'Settings', 'libelle' => 'Paramètres'],
            4 => ['code' => 'FileText', 'libelle' => 'Factures'],
            5 => ['code' => 'Banknote', 'libelle' => 'Paiements'],
            6 => ['code' => 'Home', 'libelle' => 'Maisons'],
            7 => ['code' => 'Layers', 'libelle' => 'Appartements'],
            8 => ['code' => 'FileSignature', 'libelle' => 'Contrats'],
            9 => ['code' => 'PieChart', 'libelle' => 'Rapports'],
            10 => ['code' => 'ShieldCheck', 'libelle' => 'Permissions'],
            11 => ['code' => 'MapPin', 'libelle' => 'Quartiers'],
            12 => ['code' => 'UserCheck', 'libelle' => 'Locataires'],
            13 => ['code' => 'UserTie', 'libelle' => 'Propriétaires'],
            14 => ['code' => 'Receipt', 'libelle' => 'Finance'],
            15 => ['code' => 'Building', 'libelle' => 'Administration'],
        ];

        $icons = [];
        foreach ($iconsData as $id => $data) {
            $icon = $manager->getRepository(Icon::class)->findOneBy(['code' => $data['code']]) ?: new Icon();
            $icon->setCode($data['code']);
            $icon->setLibelle($data['libelle']);
            $icon->setIsActive(true);
            $manager->persist($icon);
            $icons[$id] = $icon;
            $this->addReference('icon_' . $id, $icon);
        }

        // 2. Permissions
        $permissionsData = [
            1 => ['code' => 'R', 'libelle' => 'Lecture'],
            2 => ['code' => 'CR', 'libelle' => 'Lecture et création'],
            3 => ['code' => 'CRUD', 'libelle' => 'Tous les droits'],
            4 => ['code' => 'RD', 'libelle' => 'Lecture et suppression'],
            5 => ['code' => 'RU', 'libelle' => 'Lecture et Modification'],
            6 => ['code' => 'RUD', 'libelle' => 'Lecture et modification et suppression'],
            7 => ['code' => 'CRU', 'libelle' => 'Lecture et création et suppression']
        ];

        $perms = [];
        foreach ($permissionsData as $id => $data) {
            $perm = $manager->getRepository(Permition::class)->findOneBy(['code' => $data['code']]) ?: new Permition();
            $perm->setCode($data['code']);
            $perm->setLibelle($data['libelle']);
            $perm->setIsActive(true);
            $manager->persist($perm);
            $perms[$id] = $perm;
            $this->addReference('perm_' . $id, $perm);
        }

        // 3. Modules
        $modulesData = [
            1 => ['titre' => 'Tableau de bord', 'ordre' => 1, 'icon' => 1],
            2 => ['titre' => 'Gestion Immobilière', 'ordre' => 2, 'icon' => 6],
            3 => ['titre' => 'Gestion Locative', 'ordre' => 3, 'icon' => 12],
            4 => ['titre' => 'Finances', 'ordre' => 4, 'icon' => 5],
            5 => ['titre' => 'Paramètres', 'ordre' => 5, 'icon' => 3],
            6 => ['titre' => 'Gestion utilisateur', 'ordre' => 6, 'icon' => 15],
            7 => ['titre' => 'Espace Locataire', 'ordre' => 7, 'icon' => 12],
            8 => ['titre' => 'Dépenses', 'ordre' => 8, 'icon' => 12],
            9 => ['titre' => 'Rapports', 'ordre' => 9, 'icon' => 9],
            10 => ['titre' => 'Gestion comptabilité', 'ordre' => 10, 'icon' => 9],
            11 => ['titre' => 'Gestion agent', 'ordre' => 11, 'icon' => 9],
        ];

        $modules = [];
        foreach ($modulesData as $id => $data) {
            $module = $manager->getRepository(Module::class)->findOneBy(['titre' => $data['titre']]) ?: new Module();
            $module->setTitre($data['titre']);
            $module->setOrdre($data['ordre']);
            $module->setIcon($icons[$data['icon']]);
            $module->setIsActive(true);
            $manager->persist($module);
            $modules[$id] = $module;
            $this->addReference('module_' . $id, $module);
        }

        // 4. GroupeModule (Items)
        $itemsData = [
            1 => ['titre' => 'Tableau de bord', 'ordre' => 1, 'lien' => '/dashboard', 'icon' => 1],
            2 => ['titre' => 'Sites', 'ordre' => 1, 'lien' => '/maison', 'icon' => 6],
            3 => ['titre' => 'Appartements', 'ordre' => 2, 'lien' => '/appartement', 'icon' => 7],
            4 => ['titre' => 'Quartiers', 'ordre' => 3, 'lien' => '/quartier', 'icon' => 11],
            5 => ['titre' => 'Type de Site', 'ordre' => 4, 'lien' => '/type-maison', 'icon' => 15],
            6 => ['titre' => 'Locataires', 'ordre' => 1, 'lien' => '/locataire', 'icon' => 12],
            7 => ['titre' => 'Propriétaires', 'ordre' => 2, 'lien' => '/proprietaire', 'icon' => 13],
            8 => ['titre' => 'Contrats de Location', 'ordre' => 3, 'lien' => '/contrat-location', 'icon' => 8],
            9 => ['titre' => 'Factures de Location', 'ordre' => 1, 'lien' => '/facture-location', 'icon' => 14],
            10 => ['titre' => 'Versements Propriétaires', 'ordre' => 2, 'lien' => '/versement-proprio', 'icon' => 5],
            11 => ['titre' => 'Comptes Clients', 'ordre' => 3, 'lien' => '/compte-clt-t', 'icon' => 2],
            12 => ['titre' => 'Ligne de Versement Frais', 'ordre' => 4, 'lien' => '/ligne-versement-frais', 'icon' => 14],
            13 => ['titre' => 'Civilités', 'ordre' => 1, 'lien' => '/civilite', 'icon' => 3],
            14 => ['titre' => 'Fonctions', 'ordre' => 2, 'lien' => '/fonction', 'icon' => 3],
            15 => ['titre' => 'Pays', 'ordre' => 3, 'lien' => '/pays', 'icon' => 3],
            16 => ['titre' => 'Villes', 'ordre' => 4, 'lien' => '/ville', 'icon' => 3],
            17 => ['titre' => 'Motifs', 'ordre' => 5, 'lien' => '/motif', 'icon' => 3],
            18 => ['titre' => 'Icônes', 'ordre' => 6, 'lien' => '/icon', 'icon' => 3],
            19 => ['titre' => 'Années', 'ordre' => 7, 'lien' => '/annee', 'icon' => 3],
            20 => ['titre' => 'Utilisateurs', 'ordre' => 1, 'lien' => '/utilisateurs', 'icon' => 2],
            21 => ['titre' => 'Groupes', 'ordre' => 2, 'lien' => '/groupe', 'icon' => 10],
            22 => ['titre' => 'Modules', 'ordre' => 3, 'lien' => '/module', 'icon' => 7],
            23 => ['titre' => 'Permissions', 'ordre' => 4, 'lien' => '/permission', 'icon' => 10],
            24 => ['titre' => 'Entreprise', 'ordre' => 5, 'lien' => '/entreprise', 'icon' => 15],
            25 => ['titre' => 'Employés', 'ordre' => 1, 'lien' => '/personnel', 'icon' => 1],
            26 => ['titre' => 'Mes Factures', 'ordre' => 2, 'lien' => '/mes-factures', 'icon' => 14],
            27 => ['titre' => 'Mes Paiements', 'ordre' => 3, 'lien' => '/mes-paiements', 'icon' => 5],
            28 => ['titre' => 'Mon Contrat', 'ordre' => 4, 'lien' => '/mon-contrat', 'icon' => 8],
            29 => ['titre' => 'Mon Profil', 'ordre' => 5, 'lien' => '/profil', 'icon' => 3],
            30 => ['titre' => 'Dépense maison', 'ordre' => 1, 'lien' => '/charge-proprio', 'icon' => 1],
            31 => ['titre' => 'Compte utilisateur', 'ordre' => 1, 'lien' => '/utilisateurs', 'icon' => 1],
            32 => ['titre' => 'Etats & Rapport', 'ordre' => 1, 'lien' => '/etats', 'icon' => 9],
            33 => ['titre' => 'Charges propriétaire', 'ordre' => 1, 'lien' => '/charge-proprio', 'icon' => 1],
            34 => ['titre' => 'Natures', 'ordre' => 1, 'lien' => '/nature', 'icon' => 1],
            35 => ['titre' => 'Situation matrimoniale', 'ordre' => 1, 'lien' => '/situation-matrimoniale', 'icon' => 1],
            37 => ['titre' => 'Agences', 'ordre' => 1, 'lien' => '/agence', 'icon' => 1],
            38 => ['titre' => 'Rélances', 'ordre' => 1, 'lien' => '/relances', 'icon' => 1],
            39 => ['titre' => 'Planing recouvrement', 'ordre' => 1, 'lien' => '/planning-recouvrement', 'icon' => 1],
            40 => ['titre' => 'Suivi contacts', 'ordre' => 1, 'lien' => '/suivi-contacts', 'icon' => 1],
            41 => ['titre' => 'Charges', 'ordre' => 1, 'lien' => '/compta/charges', 'icon' => 1],
            42 => ['titre' => 'Fiscalite', 'ordre' => 1, 'lien' => '/compta/fiscalite', 'icon' => 1],
            43 => ['titre' => 'Rapprochement', 'ordre' => 1, 'lien' => '/compta/rapprochement', 'icon' => 1],
            44 => ['titre' => 'Validation', 'ordre' => 1, 'lien' => '/compta/validation', 'icon' => 1],
        ];

        $items = [];
        foreach ($itemsData as $id => $data) {
            $item = $manager->getRepository(GroupeModule::class)->findOneBy(['titre' => $data['titre'], 'lien' => $data['lien']]) ?: new GroupeModule();
            $item->setTitre($data['titre']);
            $item->setOrdre($data['ordre']);
            $item->setLien($data['lien']);
            $item->setIcon($icons[$data['icon']]);
            $item->setIsActive(true);
            $manager->persist($item);
            $items[$id] = $item;
            $this->addReference('item_' . $id, $item);
        }

        // 5. Groups
        $groupsData = [
            1 => ['name' => 'Super Administrateur', 'code' => 'SADM', 'roles' => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']],
            2 => ['name' => 'Super admin entreprise', 'code' => 'ADMIN', 'roles' => ['ROLE_ADMIN']],
            3 => ['name' => 'Agents de recouvrement', 'code' => 'AGENT', 'roles' => ['ROLE_AGENT']],
            5 => ['name' => 'Comptable', 'code' => 'COMPTABLE', 'roles' => ['ROLE_COMPTABLE']],
            6 => ['name' => 'Compte locataire', 'code' => 'LOCATAIRE', 'roles' => ['ROLE_LOCATAIRE']],
            7 => ['name' => 'Admin agence', 'code' => 'ADMINAG', 'roles' => ['ROLE_ADMIN_AGENCE']],
        ];

        $groups = [];
        foreach ($groupsData as $id => $data) {
            $grp = $manager->getRepository(Groupe::class)->findOneBy(['code' => $data['code']]) ?: new Groupe();
            $grp->setName($data['name']);
            $grp->setCode($data['code']);
            $grp->setRoles($data['roles']);
            $grp->setDescription('');
            $grp->setIsActive(true);
            $manager->persist($grp);
            $groups[$id] = $grp;
            $this->addReference('group_' . $id, $grp);
        }

        // 6. Mapping (ModuleGroupePermition)
        // I will implement a selection of mappings based on the SQL provided
        $mappings = [
            ['perm' => 3, 'module' => 5, 'item' => 13, 'group' => 1],
            ['perm' => 3, 'module' => 5, 'item' => 15, 'group' => 1],
            ['perm' => 3, 'module' => 5, 'item' => 16, 'group' => 1],
            ['perm' => 3, 'module' => 5, 'item' => 23, 'group' => 1],
            ['perm' => 3, 'module' => 5, 'item' => 18, 'group' => 1],
            ['perm' => 3, 'module' => 5, 'item' => 19, 'group' => 1],
            ['perm' => 3, 'module' => 6, 'item' => 25, 'group' => 1],
            ['perm' => 3, 'module' => 6, 'item' => 31, 'group' => 1],
            ['perm' => 3, 'module' => 6, 'item' => 21, 'group' => 1],
            ['perm' => 3, 'module' => 6, 'item' => 22, 'group' => 1],
            ['perm' => 3, 'module' => 6, 'item' => 24, 'group' => 1],
            ['perm' => 3, 'module' => 1, 'item' => 1, 'group' => 1],
            // Entreprise 2 mappings
            ['perm' => 3, 'module' => 1, 'item' => 1, 'group' => 2],
            ['perm' => 3, 'module' => 2, 'item' => 2, 'group' => 2],
            ['perm' => 3, 'module' => 2, 'item' => 3, 'group' => 2],
            ['perm' => 3, 'module' => 2, 'item' => 4, 'group' => 2],
            ['perm' => 3, 'module' => 2, 'item' => 5, 'group' => 2],
            ['perm' => 3, 'module' => 3, 'item' => 6, 'group' => 2],
            ['perm' => 3, 'module' => 3, 'item' => 7, 'group' => 2],
            ['perm' => 3, 'module' => 3, 'item' => 8, 'group' => 2],
            ['perm' => 3, 'module' => 4, 'item' => 9, 'group' => 2],
            ['perm' => 3, 'module' => 4, 'item' => 10, 'group' => 2],
            ['perm' => 3, 'module' => 9, 'item' => 32, 'group' => 2],
            ['perm' => 3, 'module' => 4, 'item' => 33, 'group' => 2],
            ['perm' => 3, 'module' => 5, 'item' => 17, 'group' => 2],
            ['perm' => 3, 'module' => 5, 'item' => 29, 'group' => 2],
            ['perm' => 3, 'module' => 5, 'item' => 37, 'group' => 2],
            ['perm' => 3, 'module' => 6, 'item' => 25, 'group' => 2],
            ['perm' => 3, 'module' => 6, 'item' => 31, 'group' => 2],
        ];

        foreach ($mappings as $m) {
            $existing = $manager->getRepository(ModuleGroupePermition::class)->findOneBy([
                'groupeUser' => $groups[$m['group']],
                'module' => $modules[$m['module']],
                'groupeModule' => $items[$m['item']],
                'permition' => $perms[$m['perm']]
            ]);

            if (!$existing) {
                $link = new ModuleGroupePermition();
                $link->setGroupeUser($groups[$m['group']]);
                $link->setModule($modules[$m['module']]);
                $link->setGroupeModule($items[$m['item']]);
                $link->setPermition($perms[$m['perm']]);
                $link->setOrdre($items[$m['item']]->getOrdre());
                $link->setOrdreGroupe($modules[$m['module']]->getOrdre());
                $link->setMenuPrincipal(true);
                $link->setIsActive(true);
                $manager->persist($link);
            }
        }

        // 7. Companies and Users
        // Entreprise 1
        $ent1 = $manager->getRepository(Entreprise::class)->findOneBy(['numero' => 'ENT001']) ?: new Entreprise();
        $ent1->setDenomination('motiplus');
        $ent1->setNumero('ENT001');
        $ent1->setCode('IMMO001');
        $ent1->setContacts('00000000');
        $ent1->setSigle('IP');
        $ent1->setEmail('info@immoplus.com');
        $ent1->setAgrements('AGREMENT');
        $ent1->setSituationGeo('Abidjan');
        $ent1->setMobile('00000000');
        $ent1->setSiteWeb('www.immoplus.com');
        $ent1->setDirecteur('directeur');
        $ent1->setVille('Abidjan');
        $manager->persist($ent1);

        // Entreprise 2
        $ent2 = $manager->getRepository(Entreprise::class)->find(2);
        if (!$ent2) {
             $ent2 = new Entreprise();
             $ent2->setDenomination('ATELIYA');
             $ent2->setNumero('ENT002');
             $ent2->setCode('ATEL');
             $ent2->setContacts('00000000');
             $ent2->setSigle('AT');
             $ent2->setEmail('info@ateliya.com');
             $ent2->setAgrements('AGR-2024');
             $ent2->setSituationGeo('Abidjan');
             $ent2->setMobile('00000000');
             $ent2->setSiteWeb('www.ateliya.com');
             $ent2->setDirecteur('Mr Konate');
             $ent2->setVille('Abidjan');
             $manager->persist($ent2);
        }

        $civilite = $manager->getRepository(Civilite::class)->findOneBy(['code' => 'M.']) ?: new Civilite();
        $civilite->setLibelle('Monsieur');
        $civilite->setCode('M.');
        $manager->persist($civilite);

        // Required Users
        $usersToCreate = [
            ['email' => 'ateliya@gmail.com', 'group' => 2, 'ent' => $ent2, 'role' => 'ROLE_ADMIN'],
            ['email' => 'konatesnessim@gmail.com', 'group' => 7, 'ent' => $ent2, 'role' => 'ROLE_ADMIN_AGENCE'],
            ['email' => 'carme2221@gmail.com', 'group' => 6, 'ent' => $ent2, 'role' => 'ROLE_LOCATAIRE'],
            ['email' => 'agent@motiplus.com', 'group' => 3, 'ent' => $ent2, 'role' => 'ROLE_AGENT'],
            ['email' => 'comptable@motiplus.com', 'group' => 5, 'ent' => $ent2, 'role' => 'ROLE_COMPTABLE'],
        ];

        foreach ($usersToCreate as $u) {
            $user = $manager->getRepository(User::class)->findOneBy(['login' => $u['email']]) ?: new User();
            $user->setLogin($u['email']);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setRoles([$u['role']]);
            $user->setGroupe($groups[$u['group']]);
            $user->setEntreprise($u['ent']);
            $user->setIsActive(true);
            $manager->persist($user);

            if ($u['group'] === 6) { // Locataire
                $loc = $user->getLocataire() ?: new Locataire();
                $loc->setNom('Locataire');
                $loc->setPrenoms('Demo');
                $loc->setContacts('00000000');
                $loc->setEntreprise($u['ent']);
                $loc->setGenre('M');
                $loc->setDateNaiss(new \DateTime('1990-01-01'));
                $loc->setLieuNaiss('Abidjan');
                $loc->setProfession('Demo');
                $loc->setNumpiece('00000000');
                $manager->persist($loc);
                $user->setLocataire($loc);
            } else {
                $emp = $user->getEmploye() ?: new Employe();
                $emp->setNom('User');
                $emp->setPrenom($u['email']);
                $emp->setMatricule(substr($u['email'], 0, 8));
                $emp->setContact('00000000');
                $emp->setAdresseMail($u['email']);
                $emp->setCivilite($civilite);
                $emp->setEntreprise($u['ent']);
                $emp->setNumPiece('00000000');
                $emp->setResidence('Abidjan');
                $emp->setUser($user);
                $manager->persist($emp);
            }
        }

        $manager->flush();
    }
}
