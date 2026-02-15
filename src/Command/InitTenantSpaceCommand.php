<?php

namespace App\Command;

use App\Entity\Groupe;
use App\Entity\GroupeModule;
use App\Entity\Module;
use App\Entity\ModuleGroupePermition;
use App\Entity\Permition;
use App\Entity\Icon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-tenant-space',
    description: 'Initialise les routes et la configuration d\'affichage pour l\'espace locataire dans la base de données.',
)]
class InitTenantSpaceCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Ensure Icons Exist
        $iconsToCreate = [
            'UserCheck' => 'Locataire Space',
            'LayoutDashboard' => 'Tableau de bord',
            'Receipt' => 'Factures',
            'Banknote' => 'Paiements',
            'FileSignature' => 'Contrat',
            'Settings' => 'Paramètres',
            'Home' => 'Accueil/Logement'
        ];

        foreach ($iconsToCreate as $code => $libelle) {
            $icon = $this->entityManager->getRepository(Icon::class)->findOneBy(['code' => $code]);
            if (!$icon) {
                $icon = new Icon();
                $icon->setCode($code);
                $icon->setLibelle($libelle);
                $icon->setImage('');
                $this->entityManager->persist($icon);
                $io->note("Icône créée : $code");
            }
        }
        $this->entityManager->flush();

        // 2. Ensure Permission 'R' exists
        $permR = $this->entityManager->getRepository(Permition::class)->findOneBy(['code' => 'R']);
        if (!$permR) {
            $permR = new Permition();
            $permR->setCode('R');
            $permR->setLibelle('Lecture');
            $this->entityManager->persist($permR);
            $io->note("Permission 'R' créée.");
        }
        $this->entityManager->flush();

        // 3. Ensure Groupe 'LOCATAIRE' exists
        $groupeLocataire = $this->entityManager->getRepository(Groupe::class)->findOneBy(['code' => 'LOCATAIRE']);
        if (!$groupeLocataire) {
            $groupeLocataire = new Groupe();
            $groupeLocataire->setCode('LOCATAIRE');
            $groupeLocataire->setName('Compte locataire');
            $groupeLocataire->setRoles(['ROLE_LOCATAIRE']);
            $groupeLocataire->setDescription('Groupe pour les locataires accédant à leur espace personnel.');
            $this->entityManager->persist($groupeLocataire);
            $io->note("Groupe 'LOCATAIRE' créé.");
        }
        $this->entityManager->flush();

        // 4. Create Module 'Espace Locataire'
        $module = $this->entityManager->getRepository(Module::class)->findOneBy(['titre' => 'Espace Locataire']);
        if (!$module) {
            $module = new Module();
            $module->setTitre('Espace Locataire');
            $module->setOrdre(7);
            $iconUserCheck = $this->entityManager->getRepository(Icon::class)->findOneBy(['code' => 'UserCheck']);
            if ($iconUserCheck) {
                $module->setIcon($iconUserCheck);
            }
            $this->entityManager->persist($module);
            $io->note("Module 'Espace Locataire' créé.");
        }
        $this->entityManager->flush();

        // 5. Create Dashboard Link (in Tableau de Bord module if exists, or just in Espace Locataire)
        // Let's stick to Espace Locataire for clarity as requested.
        $tenantItems = [
            ['titre' => 'Tableau de bord', 'lien' => '/dashboard', 'icon' => 'LayoutDashboard', 'ordre' => 1],
            ['titre' => 'Mes Factures', 'lien' => '/mes-factures', 'icon' => 'Receipt', 'ordre' => 2],
            ['titre' => 'Mes Paiements', 'lien' => '/mes-paiements', 'icon' => 'Banknote', 'ordre' => 3],
            ['titre' => 'Mon Contrat', 'lien' => '/mon-contrat', 'icon' => 'FileSignature', 'ordre' => 4],
            ['titre' => 'Mon Profil', 'lien' => '/profil', 'icon' => 'Settings', 'ordre' => 5],
        ];

        foreach ($tenantItems as $itemData) {
            $grpMod = $this->entityManager->getRepository(GroupeModule::class)->findOneBy(['lien' => $itemData['lien'], 'titre' => $itemData['titre']]);
            if (!$grpMod) {
                $grpMod = new GroupeModule();
                $grpMod->setTitre($itemData['titre']);
                $grpMod->setLien($itemData['lien']);
                $grpMod->setOrdre($itemData['ordre']);
                $icon = $this->entityManager->getRepository(Icon::class)->findOneBy(['code' => $itemData['icon']]);
                if ($icon) {
                    $grpMod->setIcon($icon);
                }
                $this->entityManager->persist($grpMod);
                $io->note("GroupeModule créé : {$itemData['titre']} -> {$itemData['lien']}");
            }

            // Assign Permission to LOCATAIRE group
            $mgp = $this->entityManager->getRepository(ModuleGroupePermition::class)->findOneBy([
                'groupeUser' => $groupeLocataire,
                'module' => $module,
                'groupeModule' => $grpMod
            ]);

            if (!$mgp) {
                $mgp = new ModuleGroupePermition();
                $mgp->setGroupeUser($groupeLocataire);
                $mgp->setModule($module);
                $mgp->setGroupeModule($grpMod);
                $mgp->setPermition($permR);
                $mgp->setOrdre($itemData['ordre']);
                $mgp->setOrdreGroupe($module->getOrdre());
                $mgp->setMenuPrincipal(true);
                $this->entityManager->persist($mgp);
                $io->note("Permission assignée : {$itemData['titre']} pour LOCATAIRE");
            }
        }

        // Also ensure Tableau de Bord -> Vue Globale is assigned
        $dashboardModule = $this->entityManager->getRepository(Module::class)->findOneBy(['titre' => 'Tableau de bord']);
        $vueGlobale = $this->entityManager->getRepository(GroupeModule::class)->findOneBy(['lien' => '/dashboard', 'titre' => 'Vue globale']);
        if ($dashboardModule && $vueGlobale) {
             $mgpDash = $this->entityManager->getRepository(ModuleGroupePermition::class)->findOneBy([
                'groupeUser' => $groupeLocataire,
                'module' => $dashboardModule,
                'groupeModule' => $vueGlobale
            ]);
            if (!$mgpDash) {
                $mgpDash = new ModuleGroupePermition();
                $mgpDash->setGroupeUser($groupeLocataire);
                $mgpDash->setModule($dashboardModule);
                $mgpDash->setGroupeModule($vueGlobale);
                $mgpDash->setPermition($permR);
                $mgpDash->setOrdre(1);
                $mgpDash->setOrdreGroupe($dashboardModule->getOrdre());
                $mgpDash->setMenuPrincipal(true);
                $this->entityManager->persist($mgpDash);
                $io->note("Permission 'Vue globale' ajoutée au module 'Tableau de bord' pour LOCATAIRE.");
            }
        }

        $this->entityManager->flush();

        $io->success("La base de données a été mise à jour avec succès pour l'espace locataire !");

        return Command::SUCCESS;
    }
}
