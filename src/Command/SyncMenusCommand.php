<?php

namespace App\Command;

use App\Entity\Groupe;
use App\Entity\Module;
use App\Entity\GroupeModule;
use App\Entity\Icon;
use App\Entity\Permition;
use App\Entity\ModuleGroupePermition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-menus',
    description: 'Synchronise les permissions de menu pour les groupes existants.',
)]
class SyncMenusCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Nettoyer l'ancienne structure de permissions (optionnel si on recrée tout)
        $this->entityManager->getConnection()->executeStatement('DELETE FROM _admin_param_module_groupe_permition');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM _admin_param_groupe_module');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM _admin_param_module');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM _admin_param_icon');
        // Permitions can stay if they exist, but let's make sure they do
        
        // 1. Create Icons
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
            'Building' => 'Administration',
            'CreditCard' => 'Mes Paiements',
            'Calendar' => 'Calendrier',
            'DollarSign' => 'Charges',
            'Database' => 'Services',
            'Briefcase' => 'RH',
            'ClipboardList' => 'Référentiels',
            'Globe2' => 'Pays',
            'UserCog' => 'Configuration User',
            'TrendingDown' => 'Dépenses',
            'Tag' => 'Types de dépenses',
            'Hotel' => 'Résidences',
            'KeyRound' => 'Réservations',
        ];

        $icons = [];
        foreach ($iconsData as $code => $libelle) {
            $icon = new Icon();
            $icon->setCode($code);
            $icon->setLibelle($libelle);
            $icon->setImage(''); 
            $this->entityManager->persist($icon);
            $icons[$code] = $icon;
        }

        // 2. Fetch or Create Permissions
        $permRepo = $this->entityManager->getRepository(Permition::class);
        $perms = [];
        $crudCode = 'CRUD';
        $rCode = 'R';
        
        $permCrud = $permRepo->findOneBy(['code' => $crudCode]);
        if (!$permCrud) {
            $permCrud = new Permition();
            $permCrud->setCode($crudCode)->setLibelle('Tous les droits');
            $this->entityManager->persist($permCrud);
        }
        $perms['CRUD'] = $permCrud;

        $permR = $permRepo->findOneBy(['code' => $rCode]);
        if (!$permR) {
            $permR = new Permition();
            $permR->setCode($rCode)->setLibelle('Lecture');
            $this->entityManager->persist($permR);
        }
        $perms['R'] = $permR;

        $this->entityManager->flush();

        // 3. Fetch Groups
        $groupRepo = $this->entityManager->getRepository(Groupe::class);
        $groups = [];
        
        $grpRecords = $groupRepo->findAll();
        foreach($grpRecords as $g) {
            $groups[$g->getCode()] = $g;
        }

        if (empty($groups)) {
            $io->error("Aucun groupe trouvé dans la BDD. Exécutez votre INSERT d'abord.");
            return Command::FAILURE;
        }

        // 4. Menu Structure Detailed
        $menuStructure = [
            'Tableau de bord' => [
                'icon' => 'LayoutDashboard', 'ordre' => 1,
                'items' => [
                    ['titre' => 'Vue globale', 'lien' => '/dashboard', 'icon' => 'LayoutDashboard']
                ]
            ],
            'Immobilier' => [
                'icon' => 'Home', 'ordre' => 2,
                'items' => [
                    ['titre' => 'Maisons', 'lien' => '/maison', 'icon' => 'Home'],
                    ['titre' => 'Appartements', 'lien' => '/appartement', 'icon' => 'Building'],
                    ['titre' => 'Propriétaires', 'lien' => '/proprietaire', 'icon' => 'UserTie'],
                    ['titre' => 'Locataires', 'lien' => '/locataire', 'icon' => 'Users']
                ]
            ],
            'Contrats & Finances' => [
                'icon' => 'FileSignature', 'ordre' => 3,
                'items' => [
                    ['titre' => 'Contrats de location', 'lien' => '/contrat-location', 'icon' => 'FileSignature'],
                    ['titre' => 'Factures location', 'lien' => '/facture-location', 'icon' => 'Receipt'],
                    ['titre' => 'Versements propriétaires', 'lien' => '/versement-proprio', 'icon' => 'Banknote'],
                    ['titre' => 'Charges propriétaires', 'lien' => '/charge-proprio', 'icon' => 'DollarSign'],
                ]
            ],
            'Espace Locataire' => [
                'icon' => 'UserCheck', 'ordre' => 4,
                'items' => [
                    ['titre' => 'Mes Factures', 'lien' => '/mes-factures', 'icon' => 'FileText'],
                    ['titre' => 'Mes Paiements', 'lien' => '/mes-paiements', 'icon' => 'CreditCard'],
                    ['titre' => 'Mon Contrat', 'lien' => '/mon-contrat', 'icon' => 'Calendar']
                ]
            ],
            'Ressources Humaines' => [
                'icon' => 'Briefcase', 'ordre' => 5,
                'items' => [
                    ['titre' => 'Employés', 'lien' => '/employes', 'icon' => 'Users'],
                    ['titre' => 'Fonctions', 'lien' => '/fonction', 'icon' => 'ClipboardList'],
                    ['titre' => 'Services', 'lien' => '/service', 'icon' => 'Database']
                ]
            ],
            'Administration' => [
                'icon' => 'Building', 'ordre' => 6,
                'items' => [
                    ['titre' => 'Agences', 'lien' => '/agence', 'icon' => 'Building'],
                    ['titre' => 'Utilisateurs', 'lien' => '/utilisateurs', 'icon' => 'UserCog'],
                    ['titre' => 'Groupes', 'lien' => '/groupe', 'icon' => 'ShieldCheck'],
                ]
            ],
            'Référentiels' => [
                'icon' => 'MapPin', 'ordre' => 7,
                'items' => [
                    ['titre' => 'Pays', 'lien' => '/pays', 'icon' => 'Globe2'],
                    ['titre' => 'Villes', 'lien' => '/ville', 'icon' => 'MapPin'],
                    ['titre' => 'Quartiers', 'lien' => '/quartier', 'icon' => 'MapPin'],
                    ['titre' => 'Types de maison', 'lien' => '/type-maison', 'icon' => 'Home'],
                    ['titre' => 'Civilités', 'lien' => '/civilite', 'icon' => 'ClipboardList'],
                    ['titre' => 'Motifs', 'lien' => '/motif', 'icon' => 'FileText'],
                    ['titre' => 'Années', 'lien' => '/annee', 'icon' => 'Calendar']
                ]
            ],
            'Gestion Comptable' => [
                'icon' => 'Receipt', 'ordre' => 8,
                'items' => [
                    ['titre' => 'Dépenses Agence', 'lien' => '/depenses', 'icon' => 'TrendingDown'],
                    ['titre' => 'Types de Dépenses', 'lien' => '/type-depense', 'icon' => 'Tag'],
                ]
            ],
            'Résidences' => [
                'icon' => 'Hotel', 'ordre' => 9,
                'items' => [
                    ['titre' => 'Résidences', 'lien' => '/residence', 'icon' => 'Hotel'],
                ]
            ],
        ];

        // 5. Build Menu and Assign
        foreach ($menuStructure as $modTitle => $modData) {
            $module = new Module();
            $module->setTitre($modTitle);
            $module->setOrdre($modData['ordre']);
            if (isset($icons[$modData['icon']])) {
                $module->setIcon($icons[$modData['icon']]);
            }
            $this->entityManager->persist($module);

            foreach ($modData['items'] as $index => $itemData) {
                $grpMod = new GroupeModule();
                $grpMod->setTitre($itemData['titre']);
                $grpMod->setLien($itemData['lien']);
                $grpMod->setOrdre($index + 1);
                if (isset($icons[$itemData['icon']])) {
                    $grpMod->setIcon($icons[$itemData['icon']] ?? null);
                }
                $this->entityManager->persist($grpMod);

                // Affectations
                $this->assignToGroups($modTitle, $itemData['titre'], $module, $grpMod, $groups, $perms);
            }
        }

        $this->entityManager->flush();

        $io->success('Menu synchronisé avec succès pour tous les groupes !');
        return Command::SUCCESS;
    }

    private function assignToGroups($modTitle, $itemTitle, $module, $grpMod, $groups, $perms)
    {
        $allPros = ['SADM', 'ADMIN', 'AGENT', 'CAISSE', 'COMPTABLE'];
        $backoffice = ['SADM', 'ADMIN', 'COMPTABLE', 'AGENT'];
        $strictAdmin = ['SADM', 'ADMIN'];
        $locataire = ['LOCATAIRE'];

        $assignedTo = [];

        if ($modTitle === 'Tableau de bord') {
            $assignedTo = $allPros;
        } elseif ($modTitle === 'Immobilier') {
            $assignedTo = $allPros;
        } elseif ($modTitle === 'Contrats & Finances') {
            if ($itemTitle === 'Contrats de location') $assignedTo = $backoffice;
            if ($itemTitle === 'Factures location') $assignedTo = $allPros;
            if ($itemTitle === 'Versements propriétaires') $assignedTo = ['SADM', 'ADMIN', 'COMPTABLE', 'CAISSE'];
            if ($itemTitle === 'Charges propriétaires') $assignedTo = ['SADM', 'ADMIN', 'COMPTABLE'];
        } elseif ($modTitle === 'Espace Locataire') {
            $assignedTo = $locataire;
        } elseif ($modTitle === 'Ressources Humaines') {
            $assignedTo = $strictAdmin;
        } elseif ($modTitle === 'Administration') {
            if ($itemTitle === 'Agences' || $itemTitle === 'Groupes') $assignedTo = ['SADM'];
            if ($itemTitle === 'Utilisateurs') $assignedTo = $strictAdmin;
        } elseif ($modTitle === 'Référentiels') {
            if ($itemTitle === 'Quartiers') $assignedTo = ['SADM', 'ADMIN'];
            else $assignedTo = ['SADM'];
        } elseif ($modTitle === 'Gestion Comptable') {
            if ($itemTitle === 'Dépenses Agence') $assignedTo = ['SADM', 'ADMIN', 'COMPTABLE', 'AGENT', 'CAISSE'];
            if ($itemTitle === 'Types de Dépenses')  $assignedTo = ['SADM', 'ADMIN', 'COMPTABLE'];
        } elseif ($modTitle === 'Résidences') {
            $assignedTo = ['SADM', 'ADMIN', 'COMPTABLE', 'AGENT', 'CAISSE'];
        }

        foreach ($assignedTo as $code) {
            if (isset($groups[$code])) {
                $permName = ($code === 'LOCATAIRE') ? 'R' : 'CRUD';
                $this->assignPermission($groups[$code], $module, $grpMod, $perms[$permName], $itemTitle);
            }
        }
    }

    private function assignPermission(Groupe $group, Module $module, GroupeModule $item, Permition $perm, $itemTitle)
    {
        $link = new ModuleGroupePermition();
        $link->setGroupeUser($group);
        $link->setModule($module);
        $link->setGroupeModule($item);
        $link->setPermition($perm);
        $link->setOrdre($item->getOrdre());
        $link->setOrdreGroupe($module->getOrdre());
        $link->setMenuPrincipal(true);
        $this->entityManager->persist($link);
    }
}
