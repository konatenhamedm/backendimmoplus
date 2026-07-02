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
                'description' => 'Petites agences et gestionnaires indépendants. 5 sites, 5 résidences, 1 agence, 5 employés.',
                'montant' => '95000',
                'duree' => '30',
                'maxBiens' => 5,
                'maxResidences' => 5,
                'maxAgences' => 1,
                'maxEmployes' => 5,
                'maxLocatairesMobileApp' => 40,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => false,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => false,
                'hasGestionDepenses' => false,
                'signatureElectronique' => 'NONE',
                'hasMultiAgences' => false,
                'hasApiIntegrations' => false,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => false,
                'hasGestionResidence' => false
            ],
            [
                'code' => 'BASIC (Semestriel)',
                'description' => 'Option 6 mois. Idéal pour petites agences. 5 sites, 5 résidences.',
                'montant' => '250000',
                'duree' => '180',
                'maxBiens' => 5,
                'maxResidences' => 5,
                'maxAgences' => 1,
                'maxEmployes' => 5,
                'maxLocatairesMobileApp' => 40,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => false,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => false,
                'hasGestionDepenses' => false,
                'signatureElectronique' => 'NONE',
                'hasMultiAgences' => false,
                'hasApiIntegrations' => false,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => false,
                'hasGestionResidence' => false
            ],
            [
                'code' => 'BASIC (Annuel)',
                'description' => '2 mois offerts ! Idéal pour petites agences. 5 sites, 5 résidences.',
                'montant' => '950000',
                'duree' => '365',
                'maxBiens' => 5,
                'maxResidences' => 5,
                'maxAgences' => 1,
                'maxEmployes' => 5,
                'maxLocatairesMobileApp' => 40,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => false,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => false,
                'hasGestionDepenses' => false,
                'signatureElectronique' => 'NONE',
                'hasMultiAgences' => false,
                'hasApiIntegrations' => false,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => false,
                'hasGestionResidence' => false
            ],

            // --- PRO ---
            [
                'code' => 'PRO (Mensuel)',
                'description' => 'Agences professionnelles en croissance. 10 sites, 10 résidences, 2 agences, 20 employés, 120 locataires mobile.',
                'montant' => '190000',
                'duree' => '30',
                'maxBiens' => 10,
                'maxResidences' => 10,
                'maxAgences' => 2,
                'maxEmployes' => 20,
                'maxLocatairesMobileApp' => 120,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'STANDARD',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => false,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => true,
                'hasGestionResidence' => true
            ],
            [
                'code' => 'PRO (Semestriel)',
                'description' => 'Option 6 mois. 10 sites, 10 résidences, 2 agences, 20 employés.',
                'montant' => '520000',
                'duree' => '180',
                'maxBiens' => 10,
                'maxResidences' => 10,
                'maxAgences' => 2,
                'maxEmployes' => 20,
                'maxLocatairesMobileApp' => 120,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'STANDARD',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => false,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => true,
                'hasGestionResidence' => true
            ],
            [
                'code' => 'PRO (Annuel)',
                'description' => '2 mois offerts ! 10 sites, 10 résidences, 2 agences, 20 employés.',
                'montant' => '1900000',
                'duree' => '365',
                'maxBiens' => 10,
                'maxResidences' => 10,
                'maxAgences' => 2,
                'maxEmployes' => 20,
                'maxLocatairesMobileApp' => 120,
                'hasFacturationAuto' => true,
                'hasRelancesAuto' => true,
                'hasMobileMoney' => true,
                'hasRapportsAvances' => true,
                'hasGestionDepenses' => true,
                'signatureElectronique' => 'STANDARD',
                'hasMultiAgences' => true,
                'hasApiIntegrations' => false,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => true,
                'hasGestionResidence' => true
            ],

            // --- ENTERPRISE ---
            [
                'code' => 'ENTERPRISE (Mensuel)',
                'description' => 'Grandes agences et groupes. 50 sites, 50 résidences, multi-agences, API, support dédié.',
                'montant' => '250000',
                'duree' => '30',
                'maxBiens' => 50,
                'maxResidences' => 50,
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
                'hasApiIntegrations' => true,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => true,
                'hasGestionResidence' => true
            ],
            [
                'code' => 'ENTERPRISE (Semestriel)',
                'description' => 'Option 6 mois. Grandes agences, multi-sites. 50 sites, 50 résidences.',
                'montant' => '700000',
                'duree' => '180',
                'maxBiens' => 50,
                'maxResidences' => 50,
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
                'hasApiIntegrations' => true,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => true,
                'hasGestionResidence' => true
            ],
            [
                'code' => 'ENTERPRISE (Annuel)',
                'description' => '2 mois offerts ! Grandes agences, promoteurs, multi-sites. 50 sites, 50 résidences.',
                'montant' => '2500000',
                'duree' => '365',
                'maxBiens' => 50,
                'maxResidences' => 50,
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
                'hasApiIntegrations' => true,
                'hasGestionImmobiliere' => true,
                'hasGestionTerrains' => true,
                'hasGestionResidence' => true
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
                $module->setMaxResidences($data['maxResidences'] ?? 0);
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
                $module->setHasGestionImmobiliere($data['hasGestionImmobiliere']);
                $module->setHasGestionTerrains($data['hasGestionTerrains']);
                $module->setHasGestionResidence($data['hasGestionResidence']);

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
                $existing->setMaxResidences($data['maxResidences'] ?? 0);
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
                $existing->setHasGestionImmobiliere($data['hasGestionImmobiliere']);
                $existing->setHasGestionTerrains($data['hasGestionTerrains']);
                $existing->setHasGestionResidence($data['hasGestionResidence']);
            }
        }

        $manager->flush();
        
        echo "Fixtures des Modules d'Abonnement chargées avec succès !\n";
    }
}
