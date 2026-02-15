<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Employe;
use App\Entity\Entreprise;
use App\Entity\Pays;
use App\Entity\Fonction;
use App\Entity\Civilite;
use App\Entity\Groupe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:setup-admin',
    description: 'Initialise une entreprise, un employé et un utilisateur admin.',
)]
class SetupAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Pays
        $pays = $this->entityManager->getRepository(Pays::class)->findOneBy(['code' => 'CI']);
        if (!$pays) {
            $pays = new Pays();
            $pays->setCode('CI');
            $pays->setLibelle("Côte d'Ivoire");
            $this->entityManager->persist($pays);
            $io->info("Pays 'Côte d'Ivoire' créé.");
        }

        // 2. Entreprise
        $entreprise = new Entreprise();
        $entreprise->setDenomination("ImmoPlus SARL");
        $entreprise->setCode("IP001");
        $entreprise->setSigle("IP");
        $entreprise->setAgrements("AGREMENT-001");
        $entreprise->setSituationGeo("Abidjan, Cocody");
        $entreprise->setContacts("+225 0102030405");
        $entreprise->setAdresse("Cocody, Angré");
        $entreprise->setMobile("+225 0102030405");
        $entreprise->setEmail("contact@immoplus.com");
        $entreprise->setSiteWeb("www.immoplus.com");
        $entreprise->setDirecteur("Jean Dupont");
        $entreprise->setVille("Abidjan");
        $entreprise->setNumero("777");
        $entreprise->setIsActive(true);
        $entreprise->setPays($pays);
        $entreprise->setDateCreation(new \DateTime());
        $this->entityManager->persist($entreprise);
        $io->info("Entreprise 'ImmoPlus SARL' créée.");

        // 3. Fonction
        $fonction = new Fonction();
        $fonction->setLibelle("Administrateur");
        $fonction->setCode("ADMIN");
        $fonction->setEntreprise($entreprise);
        $this->entityManager->persist($fonction);

        // 4. Civilite
        $civilite = $this->entityManager->getRepository(Civilite::class)->find(1);

        // 5. Employe
        $employe = new Employe();
        $employe->setNom("KONATE");
        $employe->setPrenom("Nhamed");
        $employe->setFonction($fonction);
        $employe->setEntreprise($entreprise);
        $employe->setCivilite($civilite);
        $employe->setContact("+225 0707070707");
        $employe->setAdresseMail("nhamed@immoplus.com");
        $employe->setMatricule("EMP001");
        $employe->setNumPiece("123456789");
        $employe->setResidence("Abidjan");
        $this->entityManager->persist($employe);
        $io->info("Employé 'KONATE Nhamed' créé.");

        // 6. Groupe (ID 4)
        $groupe = $this->entityManager->getRepository(Groupe::class)->find(4);
        if (!$groupe) {
            $io->error("Le groupe avec ID 4 n'existe pas.");
            return Command::FAILURE;
        }

        // 7. User
        $user = new User();
        $user->setLogin("admin_immo");
        /* $user->setNom("KONATE");
        $user->setPrenoms("Nhamed"); */
        $user->setIsActive(true);
        $user->setGroupe($groupe);
        $user->setEntreprise($entreprise);
        $user->setEmploye($employe);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, "password123");
        $user->setPassword($hashedPassword);
        $user->setRoles(["ROLE_SUPER_ADMIN", "ROLE_ADMIN"]);

        $this->entityManager->persist($user);
        
        $employe->setUser($user);

        $this->entityManager->flush();

        $io->success("Initialisation réussie !");
        $io->table(
            ['Type', 'Détails'],
            [
                ['Entreprise', $entreprise->getDenomination()],
                ['Employé', $employe->getNom() . ' ' . $employe->getPrenom()],
                ['Utilisateur', $user->getLogin()],
                ['Mot de passe', 'password123'],
                ['Groupe', $groupe->getName()]
            ]
        );

        return Command::SUCCESS;
    }
}
