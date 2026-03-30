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

        // On définit nos abonnements SaaS (Modèle 2024 adaptatif)
        $modulesData = [
            // --- BASIC ---
            [
                'code' => 'BASIC (Mensuel)',
                'description' => 'Petites agences et gestionnaires indépendants. Jusqu\'à 50 biens, 1 agence, 10 employés, 40 locataires mobile.',
                'montant' => '25000',
                'duree' => '30',
                'maxBiens' => 50,
                'maxAgences' => 1,
                'maxEmployes' => 10,
                'maxLocatairesMobileApp' => 40,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => false,
                'hasMobileMoney' => false,
                'hasRapportsAvances' => false,
                'hasGestionDepenses' => false,
                'signatureElectronique' => 'NONE',
                'hasMultiAgences' => false,
                'hasApiIntegrations' => false
            ],
            [
                'code' => 'BASIC (Annuel)',
                'description' => '2 mois offerts ! Idéal pour petites agences. Jusqu\'à 50 biens, 1 agence.',
                'montant' => '250000',
                'duree' => '365',
                'maxBiens' => 50,
                'maxAgences' => 1,
                'maxEmployes' => 10,
                'maxLocatairesMobileApp' => 40,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => false,
                'hasMobileMoney' => false,
                'hasRapportsAvances' => false,
                'hasGestionDepenses' => false,
                'signatureElectronique' => 'NONE',
                'hasMultiAgences' => false,
                'hasApiIntegrations' => false
            ],

            // --- PRO ---
            [
                'code' => 'PRO (Mensuel)',
                'description' => 'Agences professionnelles en croissance. Jusqu\'à 300 biens, 5 agences, 20 employés, 100 locataires mobile.',
                'montant' => '50000',
                'duree' => '30',
                'maxBiens' => 300,
                'maxAgences' => 5,
                'maxEmployes' => 20,
                'maxLocatairesMobileApp' => 100,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'STANDARD',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => false
            ],
            [
                'code' => 'PRO (Semestriel)',
                'description' => 'Option 6 mois. Jusqu\'à 300 biens, 5 agences, 20 employés.',
                'montant' => '120000',
                'duree' => '180',
                'maxBiens' => 300,
                'maxAgences' => 5,
                'maxEmployes' => 20,
                'maxLocatairesMobileApp' => 100,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'STANDARD',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => false
            ],
            [
                'code' => 'PRO (Annuel)',
                'description' => '2 mois offerts ! Jusqu\'à 300 biens, 5 agences, 20 employés.',
                'montant' => '500000',
                'duree' => '365',
                'maxBiens' => 300,
                'maxAgences' => 5,
                'maxEmployes' => 20,
                'maxLocatairesMobileApp' => 100,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'STANDARD',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => false
            ],

            // --- ENTERPRISE ---
            [
                'code' => 'ENTERPRISE (Mensuel)',
                'description' => 'Grandes agences et groupes. Biens illimités, multi-agences, API, support dédié.',
                'montant' => '80000',
                'duree' => '30',
                'maxBiens' => -1,
                'maxAgences' => -1,
                'maxEmployes' => -1,
                'maxLocatairesMobileApp' => -1,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'AVANCEE',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => true
            ],
            [
                'code' => 'ENTERPRISE (Semestriel)',
                'description' => 'Option 6 mois. Grandes agences, multi-sites.',
                'montant' => '200000',
                'duree' => '180',
                'maxBiens' => -1,
                'maxAgences' => -1,
                'maxEmployes' => -1,
                'maxLocatairesMobileApp' => -1,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'AVANCEE',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => true
            ],
            [
                'code' => 'ENTERPRISE (Annuel)',
                'description' => '2 mois offerts ! Grandes agences, promoteurs, multi-sites.',
                'montant' => '800000',
                'duree' => '365',
                'maxBiens' => -1,
                'maxAgences' => -1,
                'maxEmployes' => -1,
                'maxLocatairesMobileApp' => -1,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'AVANCEE',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => true
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

                $module->setMaxBiens($data['maxBiens']);
                $module->setMaxAgences($data['maxAgences']);
                $module->setMaxEmployes($data['maxEmployes']);
                $module->setMaxLocatairesMobileApp($data['maxLocatairesMobileApp']);

                $module->setHasFacturationAuto($data['hasFacturationAuto']);
                $module->setHasRelancesAuto($data['hasRelancesAuto']);
                $module->setHasMobileMoney($data['hasMobileMoney']);
                $module->setHasRapportsAvances($data['hasRapportsAvances']);
                $module->setHasGestionDepenses($data['hasGestionDepenses']);
                $module->setSignatureElectronique($data['signatureElectronique']);
                $module->setHasMultiAgences($data['hasMultiAgences']);
                $module->setHasApiIntegrations($data['hasApiIntegrations']);

                if ($pays) {
                    $module->setPays($pays);
                }

                $manager->persist($module);
            } else {
                // Update existing one to match the new features
                $existing->setDescription($data['description']);
                $existing->setMontant($data['montant']);
                $existing->setDuree($data['duree']);
                $existing->setMaxBiens($data['maxBiens']);
                $existing->setMaxAgences($data['maxAgences']);
                $existing->setMaxEmployes($data['maxEmployes']);
                $existing->setMaxLocatairesMobileApp($data['maxLocatairesMobileApp']);

                $existing->setHasFacturationAuto($data['hasFacturationAuto']);
                $existing->setHasRelancesAuto($data['hasRelancesAuto']);
                $existing->setHasMobileMoney($data['hasMobileMoney']);
                $existing->setHasRapportsAvances($data['hasRapportsAvances']);
                $existing->setHasGestionDepenses($data['hasGestionDepenses']);
                $existing->setSignatureElectronique($data['signatureElectronique']);
                $existing->setHasMultiAgences($data['hasMultiAgences']);
                $existing->setHasApiIntegrations($data['hasApiIntegrations']);
            }
        }

        $manager->flush();
        
        echo "Fixtures des Modules d'Abonnement chargées avec succès !\n";
    }
}
