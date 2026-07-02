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
 
            ['id' => 13, 'permition_id' => 3, 'module_id' => 1, 'groupe_module_id' => 1, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 14, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 2, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 15, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 3, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 16, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 4, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 3, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 17, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 5, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 4, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 18, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 6, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 19, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 7, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 20, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 8, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 21, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 9, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 22, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 10, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 23, 'permition_id' => 3, 'module_id' => 9, 'groupe_module_id' => 32, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:51:53', 'updated_at' => '2026-03-25 22:09:36', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 24, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 33, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 16:10:00', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 25, 'permition_id' => 3, 'module_id' => 5, 'groupe_module_id' => 17, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 22:18:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 26, 'permition_id' => 3, 'module_id' => 5, 'groupe_module_id' => 29, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 22:18:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 27, 'permition_id' => 3, 'module_id' => 5, 'groupe_module_id' => 46, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 23:28:47', 'updated_at' => '2026-04-01 12:52:44', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 28, 'permition_id' => 3, 'module_id' => 6, 'groupe_module_id' => 25, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 01:10:24', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 29, 'permition_id' => 3, 'module_id' => 6, 'groupe_module_id' => 31, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 01:10:24', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 30, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 3, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 31, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 4, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 3, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 32, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 5, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 4, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 33, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 6, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 34, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 7, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 35, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 8, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 36, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 9, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 37, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 10, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:36:09', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 38, 'permition_id' => 3, 'module_id' => 9, 'groupe_module_id' => 32, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 10:51:53', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 39, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 33, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 16:10:00', 'updated_at' => '2026-04-01 13:22:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 40, 'permition_id' => 3, 'module_id' => 5, 'groupe_module_id' => 17, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 22:18:23', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 41, 'permition_id' => 3, 'module_id' => 5, 'groupe_module_id' => 29, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 22:18:23', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 42, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 37, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 23:28:47', 'updated_at' => '2026-04-01 13:27:27', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 43, 'permition_id' => 3, 'module_id' => 1, 'groupe_module_id' => 1, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 44, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 2, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-25 09:04:37', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 45, 'permition_id' => 3, 'module_id' => 1, 'groupe_module_id' => 1, 'groupe_user_id' => 6, 'created_by_id' => null, 'updated_by_id' => 3, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:03:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 46, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 26, 'groupe_user_id' => 6, 'created_by_id' => null, 'updated_by_id' => 3, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:03:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 47, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 27, 'groupe_user_id' => 6, 'created_by_id' => null, 'updated_by_id' => 3, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:03:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 48, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 28, 'groupe_user_id' => 6, 'created_by_id' => null, 'updated_by_id' => 3, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:03:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 49, 'permition_id' => 3, 'module_id' => 3, 'groupe_module_id' => 29, 'groupe_user_id' => 6, 'created_by_id' => null, 'updated_by_id' => 3, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:03:23', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 50, 'permition_id' => 3, 'module_id' => 6, 'groupe_module_id' => 25, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:11:49', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 51, 'permition_id' => 3, 'module_id' => 6, 'groupe_module_id' => 31, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 12:11:49', 'updated_at' => '2026-04-01 13:21:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 52, 'permition_id' => 3, 'module_id' => 1, 'groupe_module_id' => 1, 'groupe_user_id' => 5, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:49:42', 'updated_at' => '2026-04-01 12:58:07', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 53, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 44, 'groupe_user_id' => 5, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:49:42', 'updated_at' => '2026-04-01 12:58:07', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 54, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 45, 'groupe_user_id' => 5, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:49:42', 'updated_at' => '2026-04-01 12:58:07', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 55, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 43, 'groupe_user_id' => 5, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:49:42', 'updated_at' => '2026-04-01 12:58:07', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 56, 'permition_id' => 3, 'module_id' => 2, 'groupe_module_id' => 42, 'groupe_user_id' => 5, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 3, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:49:42', 'updated_at' => '2026-04-01 12:58:07', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 57, 'permition_id' => 3, 'module_id' => 1, 'groupe_module_id' => 1, 'groupe_user_id' => 3, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:51:34', 'updated_at' => '2026-04-01 12:56:18', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 58, 'permition_id' => 3, 'module_id' => 11, 'groupe_module_id' => 47, 'groupe_user_id' => 3, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:51:34', 'updated_at' => '2026-04-01 12:56:18', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 59, 'permition_id' => 3, 'module_id' => 11, 'groupe_module_id' => 38, 'groupe_user_id' => 3, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:51:34', 'updated_at' => '2026-04-01 12:56:18', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 60, 'permition_id' => 3, 'module_id' => 11, 'groupe_module_id' => 39, 'groupe_user_id' => 3, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-03-26 14:51:34', 'updated_at' => '2026-04-01 12:56:18', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 303, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 33, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 12:43:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 304, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 44, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 12:43:09', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 306, 'permition_id' => 3, 'module_id' => 11, 'groupe_module_id' => 37, 'groupe_user_id' => 3, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 3, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 12:56:18', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 307, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 10, 'groupe_user_id' => 5, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 4, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 13:19:51', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 308, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 44, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 13:21:45', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 309, 'permition_id' => 3, 'module_id' => 10, 'groupe_module_id' => 42, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 13:21:45', 'updated_at' => '2026-04-01 13:22:45', 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 311, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 48, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 13:25:52', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 312, 'permition_id' => 3, 'module_id' => 4, 'groupe_module_id' => 48, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 13:26:34', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 313, 'permition_id' => 3, 'module_id' => 5, 'groupe_module_id' => 46, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 13:26:34', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 315, 'permition_id' => 3, 'module_id' => 12, 'groupe_module_id' => 51, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 19:26:30', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 316, 'permition_id' => 3, 'module_id' => 12, 'groupe_module_id' => 50, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 19:26:30', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 317, 'permition_id' => 3, 'module_id' => 12, 'groupe_module_id' => 49, 'groupe_user_id' => 2, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 19:26:30', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 318, 'permition_id' => 3, 'module_id' => 12, 'groupe_module_id' => 51, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 0, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 19:29:21', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 319, 'permition_id' => 3, 'module_id' => 12, 'groupe_module_id' => 50, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 1, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 19:29:21', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
            ['id' => 320, 'permition_id' => 3, 'module_id' => 12, 'groupe_module_id' => 49, 'groupe_user_id' => 7, 'created_by_id' => null, 'updated_by_id' => 2, 'ordre' => 2, 'ordre_groupe' => 0, 'menu_principal' => 1, 'created_at' => '2026-04-01 19:29:21', 'updated_at' => null, 'is_active' => 1, 'entreprise_id' => 2],
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
