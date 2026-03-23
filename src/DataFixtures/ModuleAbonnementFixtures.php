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
                'code' => '1 MOIS (ESSENTIEL)',
                'description' => 'Abonnement basique pour une durée d\'un mois.',
                'montant' => '10000',
                'duree' => '30'
            ],
            [
                'code' => '6 MOIS (PRO)',
                'description' => 'Abonnement intermédiaire pour une durée de 6 mois avec remise incluse.',
                'montant' => '54000',
                'duree' => '180'
            ],
            [
                'code' => '1 AN (PREMIUM)',
                'description' => 'Abonnement complet pour une durée de 12 mois avec forte remise.',
                'montant' => '96000',
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
