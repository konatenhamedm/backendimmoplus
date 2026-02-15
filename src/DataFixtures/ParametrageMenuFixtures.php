<?php

namespace App\DataFixtures;

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
        // 1. Create Icons (Lucide names compatible with frontend)
        $iconsData = [
            'LayoutDashboard' => 'Tableau de bord',
            'Users' => 'Utilisateurs',
            'Settings' => 'Paramètres',
            'FileText' => 'Factures',
            'Banknote' => 'Paiements',
            'Home' => 'Maisons',
            'Layers' => 'Appartements',
            'FileSignature' => 'Contrats',
            'PieChart' => 'Rapports',
            'ShieldCheck' => 'Permissions',
            'MapPin' => 'Quartiers',
            'UserCheck' => 'Locataires',
            'UserTie' => 'Propriétaires',
            'Receipt' => 'Finance',
            'Building' => 'Administration'
        ];

        $icons = [];
        foreach ($iconsData as $code => $libelle) {
            $icon = new Icon();
            $icon->setCode($code);
            $icon->setLibelle($libelle);
            $icon->setImage(''); 
            $manager->persist($icon);
            $icons[$code] = $icon;
        }

        // 2. Create Permissions
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
            $perm = new Permition();
            $perm->setCode($data['code']);
            $perm->setLibelle($data['libelle']);
            $manager->persist($perm);
            $perms[$data['code']] = $perm;
        }

        // 3. Create Groups
        $groupsData = [
            ['name' => 'Super Administrateur', 'code' => 'SADM', 'roles' => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']],
            ['name' => 'Administrateur', 'code' => 'ADMIN', 'roles' => ['ROLE_ADMIN']],
            ['name' => 'Agents de recouvrement', 'code' => 'AGENT', 'roles' => ['ROLE_AGENT']],
            ['name' => 'Caisse', 'code' => 'CAISSE', 'roles' => ['ROLE_CAISSE']],
            ['name' => 'Comptable', 'code' => 'COMPTABLE', 'roles' => ['ROLE_COMPTABLE']],
            ['name' => 'Compte locataire', 'code' => 'LOCATAIRE', 'roles' => ['ROLE_LOCATAIRE']],
        ];

        $groups = [];
        foreach ($groupsData as $data) {
            $grp = new Groupe();
            $grp->setName($data['name']);
            $grp->setCode($data['code']);
            $grp->setRoles($data['roles']);
            $grp->setDescription('');
            $manager->persist($grp);
            $groups[$data['code']] = $grp;
        }

        // 4. Create Modules and Items (GroupeModule) based on MENU_ORGANIZATION.md
        $menuStructure = [
            'Tableau de bord' => [
                'icon' => 'LayoutDashboard', 'ordre' => 1,
                'items' => [
                    ['titre' => 'Vue globale', 'lien' => '/dashboard', 'icon' => 'LayoutDashboard']
                ]
            ],
            'Gestion Immobilière' => [
                'icon' => 'Home', 'ordre' => 2,
                'items' => [
                    ['titre' => 'Maisons', 'lien' => '/maison', 'icon' => 'Home'],
                    ['titre' => 'Appartements', 'lien' => '/appartement', 'icon' => 'Layers'],
                    ['titre' => 'Quartiers', 'lien' => '/quartier', 'icon' => 'MapPin'],
                    ['titre' => 'Types de Maison', 'lien' => '/type-maison', 'icon' => 'Building']
                ]
            ],
            'Gestion Locative' => [
                'icon' => 'UserCheck', 'ordre' => 3,
                'items' => [
                    ['titre' => 'Locataires', 'lien' => '/locataire', 'icon' => 'UserCheck'],
                    ['titre' => 'Propriétaires', 'lien' => '/proprietaire', 'icon' => 'UserTie'],
                    ['titre' => 'Contrats de Location', 'lien' => '/contrat-location', 'icon' => 'FileSignature']
                ]
            ],
            'Finances' => [
                'icon' => 'Banknote', 'ordre' => 4,
                'items' => [
                    ['titre' => 'Factures de Location', 'lien' => '/facture-location', 'icon' => 'Receipt'],
                    ['titre' => 'Versements Propriétaires', 'lien' => '/versement-proprio', 'icon' => 'Banknote'],
                    ['titre' => 'Comptes Clients', 'lien' => '/compte-clt-t', 'icon' => 'Users'],
                    ['titre' => 'Ligne de Versement Frais', 'lien' => '/ligne-versement-frais', 'icon' => 'Receipt']
                ]
            ],
            'Paramètres' => [
                'icon' => 'Settings', 'ordre' => 5,
                'items' => [
                    ['titre' => 'Civilités', 'lien' => '/civilite', 'icon' => 'Settings'],
                    ['titre' => 'Fonctions', 'lien' => '/fonction', 'icon' => 'Settings'],
                    ['titre' => 'Pays', 'lien' => '/pays', 'icon' => 'Settings'],
                    ['titre' => 'Villes', 'lien' => '/ville', 'icon' => 'Settings'],
                    ['titre' => 'Motifs', 'lien' => '/motif', 'icon' => 'Settings'],
                    ['titre' => 'Icônes', 'lien' => '/icon', 'icon' => 'Settings'],
                    ['titre' => 'Années', 'lien' => '/annee', 'icon' => 'Settings']
                ]
            ],
            'Administration' => [
                'icon' => 'Building', 'ordre' => 6,
                'items' => [
                    ['titre' => 'Utilisateurs', 'lien' => '/utilisateurs', 'icon' => 'Users'],
                    ['titre' => 'Groupes', 'lien' => '/groupe', 'icon' => 'ShieldCheck'],
                    ['titre' => 'Modules', 'lien' => '/module', 'icon' => 'Layers'],
                    ['titre' => 'Permissions', 'lien' => '/permission', 'icon' => 'ShieldCheck'],
                    ['titre' => 'Entreprise', 'lien' => '/entreprise', 'icon' => 'Building']
                ]
            ],
            'Espace Locataire' => [
                'icon' => 'UserCheck', 'ordre' => 7,
                'items' => [
                    ['titre' => 'Tableau de bord', 'lien' => '/dashboard', 'icon' => 'LayoutDashboard'],
                    ['titre' => 'Mes Factures', 'lien' => '/mes-factures', 'icon' => 'Receipt'],
                    ['titre' => 'Mes Paiements', 'lien' => '/mes-paiements', 'icon' => 'Banknote'],
                    ['titre' => 'Mon Contrat', 'lien' => '/mon-contrat', 'icon' => 'FileSignature'],
                    ['titre' => 'Mon Profil', 'lien' => '/profil', 'icon' => 'Settings']
                ]
            ]
        ];

        // 5. Build Menu & Assign Permissions
        foreach ($menuStructure as $modTitle => $modData) {
            $module = new Module();
            $module->setTitre($modTitle);
            $module->setOrdre($modData['ordre']);
            if (isset($icons[$modData['icon']])) {
                $module->setIcon($icons[$modData['icon']]);
            }
            $manager->persist($module);

            foreach ($modData['items'] as $index => $itemData) {
                $grpMod = new GroupeModule();
                $grpMod->setTitre($itemData['titre']);
                $grpMod->setLien($itemData['lien']);
                $grpMod->setOrdre($index + 1);
                if (isset($icons[$itemData['icon']])) {
                    $grpMod->setIcon($icons[$itemData['icon']] ?? null);
                }
                $manager->persist($grpMod);

                // Assign Permissions
                $this->assignPermission($manager, $groups['SADM'], $module, $grpMod, $perms['CRUD']);
                $this->assignPermission($manager, $groups['ADMIN'], $module, $grpMod, $perms['CRUD']);

                if (in_array($modTitle, ['Tableau de bord', 'Gestion Immobilière', 'Gestion Locative'])) {
                    $this->assignPermission($manager, $groups['AGENT'], $module, $grpMod, $perms['CRUD']);
                }

                if (in_array($modTitle, ['Tableau de bord', 'Finances'])) {
                    $this->assignPermission($manager, $groups['CAISSE'], $module, $grpMod, $perms['CRUD']);
                    $this->assignPermission($manager, $groups['COMPTABLE'], $module, $grpMod, $perms['CRUD']);
                }

                if ($modTitle === 'Tableau de bord' || $modTitle === 'Espace Locataire') {
                    $this->assignPermission($manager, $groups['LOCATAIRE'], $module, $grpMod, $perms['R']);
                }
            }
        }

        // 6. Create Users for each Group
        $entreprise = new Entreprise();
        $entreprise->setDenomination('ImmoPlus');
        $entreprise->setNumero('ENT002');
        $entreprise->setCode('IMMO002');
        $entreprise->setContacts('00000000');
        $entreprise->setSigle('IP');
        $entreprise->setEmail('info@immoplus.com');
        $entreprise->setAgrements('AGREMENT');
        $entreprise->setSituationGeo('Abidjan');
        $entreprise->setMobile('00000000');
        $entreprise->setSiteWeb('www.immoplus.com');
        $entreprise->setDirecteur('Directeur');
        $entreprise->setVille('Abidjan');
        $manager->persist($entreprise);

        $civilite = new Civilite();
        $civilite->setLibelle('Monsieur');
        $civilite->setCode('M.');
        $manager->persist($civilite);

        foreach ($groups as $code => $group) {
            $fonction = new Fonction();
            $fonction->setLibelle($group->getName());
            $fonction->setCode(substr($code, 0, 10)); 
            $fonction->setEntreprise($entreprise);
            $manager->persist($fonction);

            $login = strtolower($code) . '@immoplus.com';
            $user = new User();
            $user->setLogin($login);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $user->setRoles($group->getRoles());
            $user->setGroupe($group);
            $user->setEntreprise($entreprise);
            $user->setIsActive(true);

            if ($code === 'LOCATAIRE') {
                $locataire = new Locataire();
                $locataire->setPrenoms('Locataire ' . $code);
                $locataire->setContacts('00000000');
                $locataire->setEntreprise($entreprise);
                $locataire->setGenre('M');
                $locataire->setDateNaiss(new \DateTime('1990-01-01'));
                $locataire->setLieuNaiss('Abidjan');
                $locataire->setProfession('Indépendant');
                $locataire->setNumpiece('00000000');
                $manager->persist($locataire);
                $user->setLocataire($locataire);
            } else {
                $employe = new Employe();
                $employe->setNom($group->getName());
                $employe->setPrenom('User');
                $employe->setMatricule(substr($code, 0, 9) . '01');
                $employe->setContact('00000000');
                $employe->setAdresseMail($login);
                $employe->setCivilite($civilite);
                $employe->setFonction($fonction);
                $employe->setEntreprise($entreprise);
                $employe->setNumPiece('00000000');
                $employe->setResidence('Abidjan');
                $employe->setUser($user);
                $manager->persist($employe);
            }
            $manager->persist($user);
        }

        $manager->flush();
    }

    private function assignPermission(ObjectManager $manager, Groupe $group, Module $module, GroupeModule $item, Permition $perm)
    {
        $link = new ModuleGroupePermition();
        $link->setGroupeUser($group);
        $link->setModule($module);
        $link->setGroupeModule($item);
        $link->setPermition($perm);
        $link->setOrdre($item->getOrdre());
        $link->setOrdreGroupe($module->getOrdre());
        $link->setMenuPrincipal(true);
        $manager->persist($link);
    }
}
