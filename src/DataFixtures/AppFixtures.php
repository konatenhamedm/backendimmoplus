<?php

namespace App\DataFixtures;

use App\Entity\Entreprise;
use App\Entity\Locataire;
use App\Entity\Proprio;
use App\Entity\Maison;
use App\Entity\Appartement;
use App\Entity\ContratLocation;
use App\Entity\Annee;
use App\Entity\TabMois;
use App\Entity\Campagne;
use App\Entity\FactureLocation;
use App\Entity\User;
use App\Entity\Pays;
use App\Entity\Ville;
use App\Entity\Quartier;
use App\Entity\TypeMaison;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 0. Reference Data: Pays, Ville, Quartier, TypeMaison
        // Create Months (TabMois) - Critical for Invoice Generation
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        foreach ($months as $num => $lib) {
            $mois = new TabMois();
            $mois->setNumMois($num);
            $mois->setLibMois($lib);
            // Assuming simplified start/end for metadata
            $mois->setDebut('01'); 
            $mois->setFin('30'); 
            $manager->persist($mois);
        }

        $pays = new Pays();
        $pays->setCode('CI');
        $pays->setLibelle('Côte d\'Ivoire');
        $manager->persist($pays);

        $ville = new Ville();
        $ville->setLibVille('Abidjan');
        $ville->setAbregeVille('ABJ');
        $ville->setPays($pays);
        $manager->persist($ville);

        $quartier = new Quartier();
        $quartier->setLibQuartier('Cocody');
        $quartier->setVille($ville);
        $manager->persist($quartier);

        $typeMaison = new TypeMaison();
        $typeMaison->setLibType('Apparment');
        $manager->persist($typeMaison);

        // 1. Create Entreprise
        $entreprise = new Entreprise();
        $entreprise->setDenomination('motiplus Demo');
        $entreprise->setEmail('contact@immoplus.demo');
        $entreprise->setContacts('0102030405');
        $entreprise->setCode('IMMO001');
        $entreprise->setSigle('IMMO');
        $entreprise->setAgrements('AGREMENT-001'); // Mandatory
        $entreprise->setSituationGeo('Abidjan Cocody Riviera'); // Mandatory
        $entreprise->setMobile('0505050505');
        $entreprise->setSiteWeb('www.immoplus.demo');
        $entreprise->setDirecteur('Mr Konate');
        $entreprise->setNumero('ENT001'); // Mandatory
        $entreprise->setPays($pays);
        $entreprise->setVille($ville->getLibVille());
        // Add other required fields if any (check entities)
        $manager->persist($entreprise);

        // 2. Create User (Admin) with Employe
        // Employe must be created first or linked
        $employe = new \App\Entity\Employe();
        $employe->setNom('Admin');
        $employe->setPrenom('User');
        $employe->setMatricule('ADM001');
        $employe->setContact('0102030405');
        $employe->setAdresseMail('admin@immoplus.demo');
        // Need to add mandatory fields for Employe if any (checked: Fonction, Civilite are NotNull in definition? Let's check) 
        // Employe.php: fonction and civilite are ManyToOne JoinColumn nullable=false.
        // I need to create Fonction and Civilite.

        $civilite = new \App\Entity\Civilite();
        $civilite->setLibelle('Monsieur');
        $civilite->setCode('M');
        $manager->persist($civilite);
        $employe->setCivilite($civilite);

        $employe->setFonction('Administrateur');
        
        $employe->setEntreprise($entreprise);
        $employe->setContacts('0102030405');
        $employe->setNumPiece('CNI001');
        $employe->setResidence('Abidjan');
        $manager->persist($employe);

        $user = new User();
        $user->setLogin('admin@immoplus.demo');
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setEntreprise($entreprise);
        $user->setIsActive(true);
        $user->setEmploye($employe);
        $manager->persist($user);

        // 3. Create Proprio
        $proprio = new Proprio();
        $proprio->setNom('Propriétaire');
        $proprio->setPrenoms('Test');
        $proprio->setContacts('0708091011');
        $proprio->setEmail('proprio@test.com');
        $proprio->setAddresse('Abidjan Cocody');
        $proprio->setNumCni('CNI-PROPRIO-001');
        $proprio->setLieuNaiss('Abidjan');
        $proprio->setPrefession('Commerçant');
        $proprio->setDateNaiss(new \DateTime('1980-01-01'));
        $proprio->setDateCni(new \DateTime('2020-01-01'));
        // Genre not found in Proprio.php
        $proprio->setEntreprise($entreprise);
        $manager->persist($proprio);

        // 4. Create Maison
        $maison = new Maison();
        $maison->setLibMaison('Villa Test');
        $maison->setLot('Lot 123');
        $maison->setIlot('Ilot 4');
        $maison->setMntCom(10000); // 10% commission example
        $maison->setProprio($proprio);
        $maison->setIdAgent($user); // Assuming user can be agent
        $maison->setQuartier($quartier);
        $maison->setTypeMaison($typeMaison);
        // Maison does not have setEntreprise based on previous error and file check (it has relations to quartier which has entreprise, and idAgent which has entreprise)
        // But let's check Maison.php again. It does NOT have 'entreprise' property.
        $manager->persist($maison);
        
        // 5. Create Locataire
        $locataire = new Locataire();
        $locataire->setNom('Locataire');
        $locataire->setPrenoms('Test');
        $locataire->setDateNaiss(new \DateTime('1990-01-01'));
        $locataire->setLieuNaiss('Abidjan');
        $locataire->setProfession('Informaticien');
        $locataire->setContacts('0506070809');
        $locataire->setGenre('M');
        $locataire->setNumpiece('CI-123456789');
        $locataire->setEntreprise($entreprise);
        $manager->persist($locataire);

        // 6. Create Appartement
         $appartement = new Appartement();
         $appartement->setLibAppart('Appart A1');
         $appartement->setMaisson($maison);
         // $appartement->setLocataire($locataire); // Not a direct relationship
         $appartement->setNbrePieces(3);
         $appartement->setNumEtage(1); // First floor
         $appartement->setLoyer(150000);
         $appartement->setDetails("Details de l'appartement");
         // $appartement->setEntreprise($entreprise); // Not a direct relationship
         $manager->persist($appartement);

        // 7. Create ContratLocation
        $contrat = new ContratLocation();
        $contrat->setLocataire($locataire);
        $contrat->setMntLoyer('150000');
        $contrat->setDateDebut(new \DateTime('2024-01-01'));
        $contrat->setMntCaution('300000');
        $contrat->setNbMoisCaution(2);
        $contrat->setFraisanex(0);
        // Setting the JourGenerationFacture (day of month for payment) using the user's defined setter
        $contrat->setJourGenerationFacture(15); 
        $contrat->setEntreprise($entreprise);
        $contrat->setEtat(1); // Active
        $contrat->setAppart($appartement);
        $manager->persist($contrat);

        $manager->flush();
    }
}
