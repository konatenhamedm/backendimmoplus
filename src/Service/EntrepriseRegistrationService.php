<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\Entreprise;
use App\Entity\Employe;
use App\Entity\User;
use App\Entity\Groupe;
use App\Entity\Civilite;
use App\Repository\PaysRepository;
use App\Repository\GroupeRepository;
use App\Repository\CiviliteRepository;
use App\Repository\ModuleAbonnementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EntrepriseRegistrationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private PaysRepository $paysRepo,
        private GroupeRepository $groupeRepo,
        private CiviliteRepository $civiliteRepo,
        private ModuleAbonnementRepository $moduleAbonnementRepo,
        private MenuGeneratorService $menuService
    ) {}

    public function processRegistration(array $data): Entreprise
    {
        $pays = $this->paysRepo->find($data['pays_id']);
        if (!$pays) {
            throw new \Exception("Pays introuvable");
        }

        // --- CREATION DE L'ENTREPRISE ---
        $entreprise = new Entreprise();
        $entreprise->setDenomination($data['denomination']);
        $entreprise->setCode('ENT-' . strtoupper(substr(uniqid(), -6)));
        $entreprise->setPays($pays);
        
        if (isset($data['contacts'])) $entreprise->setContacts($data['contacts']);
        if (isset($data['sigle'])) $entreprise->setSigle($data['sigle']);
        if (isset($data['email'])) $entreprise->setEmail($data['email']);
        
        // FNE
        if (isset($data['fneLogin'])) $entreprise->setFneLogin($data['fneLogin']);
        if (isset($data['fnePassword'])) $entreprise->setFnePassword($data['fnePassword']);

        // --- GESTION DE L'ABONNEMENT ---
        $moduleAbonnementId = $data['module_abonnement_id'] ?? null;
        $typeAbonnement = 'ESSAI';
        $joursDuree = 14;

        if ($moduleAbonnementId) {
            $moduleAbonnement = $this->moduleAbonnementRepo->find($moduleAbonnementId);
            if ($moduleAbonnement) {
                $typeAbonnement = $moduleAbonnement->getCode();
                $joursDuree = (int)$moduleAbonnement->getDuree();
            }
        }

        $dateFin = new \DateTime();
        $dateFin->modify("+$joursDuree days");

        $entreprise->setAbonnement($typeAbonnement);
        $entreprise->setDateFinAbonnement($dateFin);
        $entreprise->setIsActive(true);
        $entreprise->setDateCreation(new \DateTime());

        $this->em->persist($entreprise);

        // --- NOUVEAU SYSTEME D'ABONNEMENT ---
        $abonnement = new Abonnement();
        $abonnement->setEntreprise($entreprise);
        $abonnement->setType($typeAbonnement);
        $abonnement->setEtat('ACTIF');
        $abonnement->setDateFin($dateFin);
        $this->em->persist($abonnement);

        // --- GÉNÉRATION DU MENU ---
        $this->menuService->generateDefaultMenu($entreprise);

        // --- RECHERCHE / CREATION DU GROUPE ADMIN ---
        $groupe = $this->groupeRepo->findOneBy(['code' => 'ADMIN']);
        if (!$groupe) {
            $groupe = new Groupe();
            $groupe->setCode('ADMIN');
            $groupe->setName('Administrateurs');
            $this->em->persist($groupe);
        }
        
        // --- CIVILITÉ PAR DÉFAUT ---
        $civilite = $this->civiliteRepo->findOneBy([]);
        if (!$civilite) {
            $civilite = new Civilite();
            $civilite->setCode('M.');
            $civilite->setLibelle('Monsieur');
            $this->em->persist($civilite);
        }

        // --- CREATION DE L'EMPLOYE (ADMIN) ---
        $employe = new Employe();
        $employe->setNom($data['admin_nom']);
        $employe->setPrenom($data['admin_prenoms'] ?? '');
        $employe->setEntreprise($entreprise);
        $employe->setCivilite($civilite);
        $employe->setMatricule('EMP-' . strtoupper(substr(uniqid(), -6)));
        $employe->setContact($data['contacts'] ?? '00000000');
        $employe->setAdresseMail($data['email'] ?? 'admin@email.com');
        $employe->setNumPiece($data['denomination']); // Default value
        $employe->setResidence($data['denomination']); // Default value
        $employe->setIsActive(true);
        $this->em->persist($employe);

        // --- CREATION DU COMPTE UTILISATEUR ---
        $user = new User();
        $user->setLogin($data['admin_login']);
        $user->setEmploye($employe);
        $user->setGroupe($groupe);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_BUREAU']);
        $user->setPassword($this->hasher->hashPassword($user, $data['admin_password']));
        $user->setIsActive(true);
        $this->em->persist($user);

        $this->em->flush();

        return $entreprise;
    }
}
