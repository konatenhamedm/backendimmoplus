<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\ModuleGroupePermition;
use App\Repository\GroupeRepository;
use App\Repository\GroupeModuleRepository;
use App\Repository\ModuleRepository;
use App\Repository\PermitionRepository;
use Doctrine\ORM\EntityManagerInterface;

class MenuGeneratorService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ModuleRepository $moduleRepo,
        private GroupeModuleRepository $groupeModuleRepo,
        private PermitionRepository $permitionRepo,
        private GroupeRepository $groupeRepo
    ) {}

    public function generateDefaultMenu(Entreprise $entreprise): void
    {
        $defaultMenuItems = [
            ["permition_id" => 3, "module_id" => 1, "groupe_module_id" => 1, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 2, "groupe_user_id" => 2, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 3, "groupe_user_id" => 2, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 4, "groupe_user_id" => 2, "ordre" => 3, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 5, "groupe_user_id" => 2, "ordre" => 4, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 6, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 7, "groupe_user_id" => 2, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 8, "groupe_user_id" => 2, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 9, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 10, "groupe_user_id" => 2, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 9, "groupe_module_id" => 32, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 33, "groupe_user_id" => 2, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 5, "groupe_module_id" => 17, "groupe_user_id" => 2, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 5, "groupe_module_id" => 29, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 5, "groupe_module_id" => 37, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 6, "groupe_module_id" => 25, "groupe_user_id" => 2, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 6, "groupe_module_id" => 31, "groupe_user_id" => 2, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 3, "groupe_user_id" => 7, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 4, "groupe_user_id" => 7, "ordre" => 3, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 5, "groupe_user_id" => 7, "ordre" => 4, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 6, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 7, "groupe_user_id" => 7, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 8, "groupe_user_id" => 7, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 9, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 10, "groupe_user_id" => 7, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 9, "groupe_module_id" => 32, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 33, "groupe_user_id" => 7, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 5, "groupe_module_id" => 17, "groupe_user_id" => 7, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 5, "groupe_module_id" => 29, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 5, "groupe_module_id" => 37, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 1, "groupe_module_id" => 1, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 2, "groupe_user_id" => 7, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 1, "groupe_module_id" => 1, "groupe_user_id" => 6, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 26, "groupe_user_id" => 6, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 4, "groupe_module_id" => 27, "groupe_user_id" => 6, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 28, "groupe_user_id" => 6, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 3, "groupe_module_id" => 29, "groupe_user_id" => 6, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 6, "groupe_module_id" => 25, "groupe_user_id" => 7, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 6, "groupe_module_id" => 31, "groupe_user_id" => 7, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 1, "groupe_module_id" => 1, "groupe_user_id" => 5, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 10, "groupe_module_id" => 44, "groupe_user_id" => 5, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 10, "groupe_module_id" => 41, "groupe_user_id" => 5, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 10, "groupe_module_id" => 43, "groupe_user_id" => 5, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 2, "groupe_module_id" => 42, "groupe_user_id" => 5, "ordre" => 3, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 1, "groupe_module_id" => 1, "groupe_user_id" => 3, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 11, "groupe_module_id" => 40, "groupe_user_id" => 3, "ordre" => 0, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 11, "groupe_module_id" => 38, "groupe_user_id" => 3, "ordre" => 1, "ordre_groupe" => 0, "menu_principal" => 1],
            ["permition_id" => 3, "module_id" => 11, "groupe_module_id" => 39, "groupe_user_id" => 3, "ordre" => 2, "ordre_groupe" => 0, "menu_principal" => 1],
        ];

        foreach ($defaultMenuItems as $item) {
            $mgp = new ModuleGroupePermition();
            $mgp->setEntreprise($entreprise);
            $mgp->setOrdre($item['ordre']);
            $mgp->setOrdreGroupe($item['ordre_groupe']);
            $mgp->setMenuPrincipal((bool)$item['menu_principal']);

            // Fetch relations
            $permition = $this->permitionRepo->find($item['permition_id']);
            $module = $this->moduleRepo->find($item['module_id']);
            $groupeModule = $this->groupeModuleRepo->find($item['groupe_module_id']);
            $groupeUser = $this->groupeRepo->find($item['groupe_user_id']);

            if ($permition) $mgp->setPermition($permition);
            if ($module) $mgp->setModule($module);
            if ($groupeModule) $mgp->setGroupeModule($groupeModule);
            if ($groupeUser) $mgp->setGroupeUser($groupeUser);

            $this->em->persist($mgp);
        }

        $this->em->flush();
    }
}
