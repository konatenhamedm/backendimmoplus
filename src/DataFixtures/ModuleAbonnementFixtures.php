<?php

namespace App\DataFixtures;

use App\Entity\ModuleAbonnement;
use App\Entity\Pays;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ModuleAbonnementFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['modules'];
    }
    public function load(ObjectManager $manager): void
    {
        // On essaie de récupérer le pays par défaut (CI)
        $pays = $manager->getRepository(Pays::class)->findOneBy(['code' => 'CI']);
        // Parfois c'est sauvegardé en minuscules
        if (!$pays) {
            $pays = $manager->getRepository(Pays::class)->findOneBy(['code' => 'ci']);
        }

        // On définit nos 3 abonnements SaaS
        $modulesData = [
            [
                'code' => 'BASIC (Mensuel)',
                'description' => 'Idéal pour les petites agences (jusqu\'à 50 biens). Support WhatsApp.',
                'montant' => '25000',
                'duree' => '30'
            ],
            [
                'code' => 'BASIC (Annuel)',
                'description' => '2 mois offerts ! Idéal pour petites agences (jusqu\'à 50 biens).',
                'montant' => '250000',
                'duree' => '365'
            ],
            [
                'code' => 'PRO (Mensuel)',
                'description' => 'Agences pro (jusqu\'à 300 biens). Facturation automatisée, paiements Mobile Money.',
                'montant' => '75000',
                'duree' => '30'
            ],
            [
                'code' => 'PRO (Annuel)',
                'description' => '2 mois offerts ! Agences intermédiaires (jusqu\'à 300 biens).',
                'montant' => '750000',
                'duree' => '365'
            ],
            [
                'code' => 'ENTERPRISE (Mensuel)',
                'description' => 'Grandes agences. Biens illimités, multi-agences, API, support dédié.',
                'montant' => '200000',
                'duree' => '30'
            ],
            [
                'code' => 'ENTERPRISE (Annuel)',
                'description' => '2 mois offerts ! Grandes agences, promoteurs, multi-sites.',
                'montant' => '2000000',
                'duree' => '365'
            ]
        ];

        // On insère uniquement s'ils n'existent pas déjà
        foreach ($modulesData as $data) {
            $existing = $manager->getRepository(ModuleAbonnement::class)->findOneBy(['code' => $data['code']]);
            
            if (!$existing) {
                $module = new ModuleAbonnement();
                $module->setCode($data['code']);
                $module->setDescription($data['description']);
                $module->setMontant($data['montant']);
                $module->setDuree($data['duree']);
                $module->setEtat(true);

                if ($pays) {
                    $module->setPays($pays);
                }

                $manager->persist($module);
            }
        }

        $manager->flush();
        
        echo "Fixtures des Modules d'Abonnement chargées avec succès !\n";
    }
}
