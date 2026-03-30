<?php

namespace App\DataFixtures;

use App\Entity\Agence;
use App\Entity\Appartement;
use App\Entity\Civilite;
use App\Entity\ContratLocation;
use App\Entity\Employe;
use App\Entity\Entreprise;
use App\Entity\Locataire;
use App\Entity\Maison;
use App\Entity\Proprio;
use App\Entity\Quartier;
use App\Entity\TypeMaison;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DemoEntrepriseFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            ModuleAbonnementFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // 1. Get or Create Entreprise ID=2
        $entreprise = $manager->getRepository(Entreprise::class)->find(2);
        if (!$entreprise) {
            $entreprise = new Entreprise();
            $entreprise->setDenomination("Agence Immobilier Pro");
            $entreprise->setEmail("contact@immo-pro.com");
            $entreprise->setContacts("0707070707");
            $entreprise->setCode("IMMO_PRO");
            $entreprise->setSigle("AIP");
            $entreprise->setAgrements("AGR-2024-PRO");
            $entreprise->setSituationGeo("Abidjan, Zone 4");
            $entreprise->setMobile("0505050505");
            $entreprise->setNumero("ENT-002");
            $entreprise->setVille("Abidjan");
            $manager->persist($entreprise);
            // Trigger flush to ensure ID if possible, but fixtures handle it later.
            // In some setups, we might need to use raw SQL if we MUST have ID 2.
        }

        // 2. Get or Create Agences (1 and 5)
        $agence1 = $manager->getRepository(Agence::class)->find(1);
        if (!$agence1) {
            $agence1 = new Agence();
            $agence1->setNom("Agence Centrale");
            $agence1->setEntreprise($entreprise);
            $agence1->setIsActive(true);
            $manager->persist($agence1);
        }

        $agence5 = $manager->getRepository(Agence::class)->find(5);
        if (!$agence5) {
            $agence5 = new Agence();
            $agence5->setNom("Agence Plateau");
            $agence5->setEntreprise($entreprise);
            $agence5->setIsActive(true);
            $manager->persist($agence5);
        }

        // 3. Reference Data
        $quartier = $manager->getRepository(Quartier::class)->findOneBy([]) ?: null;
        $typeMaison = $manager->getRepository(TypeMaison::class)->findOneBy([]) ?: null;
        $civilite = $manager->getRepository(Civilite::class)->findOneBy(['code' => 'M']) ?: null;
        
        // 4. Get or Create Agent User (ID=6)
        $userAgent = $manager->getRepository(User::class)->find(6);
        if (!$userAgent) {
            $employeAgent = new Employe();
            $employeAgent->setNom($faker->lastName);
            $employeAgent->setPrenom($faker->firstName);
            $employeAgent->setMatricule("AGT-" . $faker->unique()->randomNumber(4));
            $employeAgent->setContact($faker->phoneNumber);
            $employeAgent->setAdresseMail("agent@immo-pro.com");
            $employeAgent->setEntreprise($entreprise);
            $employeAgent->setAgence($agence1);
            $employeAgent->setIsActive(true);
            $employeAgent->setFonction('Agent Immobilier');
            if ($civilite) $employeAgent->setCivilite($civilite);
            $manager->persist($employeAgent);

            $userAgent = new User();
            $userAgent->setLogin("agent@immo-pro.com");
            $userAgent->setPassword($this->hasher->hashPassword($userAgent, 'password'));
            $userAgent->setRoles(['ROLE_USER']);
            $userAgent->setEntreprise($entreprise);
            $userAgent->setAgence($agence1);
            $userAgent->setEmploye($employeAgent);
            $userAgent->setIsActive(true);
            $manager->persist($userAgent);
        }

        // 5. Create Proprietaires
        $proprios = [];
        for ($i = 0; $i < 5; $i++) {
            $proprio = new Proprio();
            $proprio->setNom($faker->lastName);
            $proprio->setPrenoms($faker->firstName);
            $proprio->setContacts($faker->phoneNumber);
            $proprio->setEmail($faker->email);
            $proprio->setAddresse($faker->address);
            $proprio->setEntreprise($entreprise);
            $manager->persist($proprio);
            $proprios[] = $proprio;
        }

        // 6. Create Maisons (Houses)
        $maisons = [];
        $agences = [$agence1, $agence5];
        for ($i = 0; $i < 10; $i++) {
            $maison = new Maison();
            $maison->setLibMaison("Résidence " . $faker->streetName);
            $maison->setLot("Lot " . $faker->numberBetween(1, 500));
            $maison->setIlot("Ilot " . $faker->numberBetween(1, 50));
            $maison->setMntCom(10); // 10%
            $maison->setProprio($proprios[array_rand($proprios)]);
            $maison->setAgence($agences[$i % 2]); // Distribute across agences
            $maison->setIdAgent($userAgent);
            if ($quartier) $maison->setQuartier($quartier);
            if ($typeMaison) $maison->setTypeMaison($typeMaison);
            $manager->persist($maison);
            $maisons[] = $maison;
        }

        // 7. Create Locataires (including User ID=4)
        $locataires = [];
        for ($i = 0; $i < 15; $i++) {
            $locataire = new Locataire();
            $locataire->setNom($faker->lastName);
            $locataire->setPrenoms($faker->firstName);
            $locataire->setDateNaiss($faker->dateTimeBetween('-50 years', '-20 years'));
            $locataire->setLieuNaiss($faker->city);
            $locataire->setProfession($faker->jobTitle);
            $locataire->setContacts($faker->phoneNumber);
            $locataire->setGenre($faker->randomElement(['M', 'F']));
            $locataire->setNumpiece("CNI-" . $faker->unique()->randomNumber(8));
            $locataire->setEntreprise($entreprise);
            $locataire->setAgence($agences[$i % 2]);
            $manager->persist($locataire);
            $locataires[] = $locataire;

            // ID=4 User for the first locataire
            if ($i === 0) {
                $userLocataire = $manager->getRepository(User::class)->find(4);
                if (!$userLocataire) {
                    $userLocataire = new User();
                    $userLocataire->setLogin("locataire@demo.com");
                    $userLocataire->setPassword($this->hasher->hashPassword($userLocataire, 'password'));
                    $userLocataire->setRoles(['ROLE_LOCATAIRE']);
                    $userLocataire->setEntreprise($entreprise);
                    $userLocataire->setLocataire($locataire);
                    $userLocataire->setIsActive(true);
                    $manager->persist($userLocataire);
                }
            }
        }

        // 8. Create Appartements & Contrats
        foreach ($maisons as $index => $maison) {
            for ($j = 0; $j < 3; $j++) {
                $appartement = new Appartement();
                $appartement->setLibAppart("Appartement " . ($j + 1) . " - " . $maison->getLibMaison());
                $appartement->setMaisson($maison);
                $appartement->setNbrePieces($faker->numberBetween(1, 4));
                $appartement->setNumEtage($j);
                $appartement->setLoyer($faker->randomElement([150000, 200000, 250000, 300000]));
                $appartement->setDetails($faker->sentence());
                $appartement->setOqp(0);
                $manager->persist($appartement);

                // Create a contract for some of them
                if ($faker->boolean(70)) { // 70% chance to be rented
                    $locataire = $locataires[array_rand($locataires)];
                    $contrat = new ContratLocation();
                    $contrat->setLocataire($locataire);
                    $contrat->setAppart($appartement);
                    $contrat->setMntLoyer((string)$appartement->getLoyer());
                    $contrat->setDateDebut($faker->dateTimeBetween('-1 year', 'now'));
                    $contrat->setMntCaution((string)($appartement->getLoyer() * 2));
                    $contrat->setNbMoisCaution(2);
                    $contrat->setFraisanex('0');
                    $contrat->setJourGenerationFacture(5);
                    $contrat->setEntreprise($entreprise);
                    $contrat->setEtat(1);
                    $manager->persist($contrat);
                    
                    $appartement->setOqp(1); // Mark as occupied
                }
            }
        }

        $manager->flush();
    }
}
