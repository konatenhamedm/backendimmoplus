<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\Abonnement;
use App\Entity\Maison;
use App\Entity\Agence;
use App\Entity\Employe;
use App\Entity\Locataire;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\MaisonRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AbonnementRepository $abonnementRepo,
        private MaisonRepository $maisonRepo
    ) {}

    /**
     * Renouvelle ou change l'abonnement d'une entreprise.
     */
    public function renewSubscription(Entreprise $entreprise, \App\Entity\ModuleAbonnement $module, string $type = 'RENOUVELLEMENT'): Abonnement
    {
        // 1. Expirer les abonnements actifs
        $activeAbonnements = $this->abonnementRepo->findBy([
            'entreprise' => $entreprise,
            'etat' => 'ACTIF'
        ]);
        foreach ($activeAbonnements as $oldAb) {
            $oldAb->setEtat('EXPIRE');
            $this->em->persist($oldAb);
        }

        // 2. Créer le nouvel abonnement
        $abonnement = new Abonnement();
        $abonnement->setEntreprise($entreprise);
        $abonnement->setModuleAbonnement($module);
        $abonnement->setEtat('ACTIF');
        $abonnement->setType($type);

        // Date de fin basée sur la durée du module
        $dateFin = new \DateTime();
        $dureeEnJours = (int) $module->getDuree();
        if ($dureeEnJours > 0) {
            $dateFin->modify("+{$dureeEnJours} days");
        } else {
            $dateFin->modify("+30 days"); // Par défaut
        }
        $abonnement->setDateFin($dateFin);

        // 3. Mettre à jour l'entreprise
        $entreprise->setDateFinAbonnement(clone $abonnement->getDateFin());
        $entreprise->setAbonnement($module->getCode());

        $this->em->persist($abonnement);
        $this->em->persist($entreprise);
        
        // 4. Appliquer les limites immédiatement
        $this->enforceLimits($entreprise);

        $this->em->flush();

        return $abonnement;
    }

    /**
     * Applique les limites du plan actuel de l'entreprise.
     * Si l'entreprise dépasse ses quotas (ex: passage de PRO à BASIC),
     * on désactive les éléments en trop (plus récents en premier).
     */
    public function enforceLimits(Entreprise $entreprise): void
    {
        // 1. Récupérer l'abonnement actif
        $activeAbonnement = $this->abonnementRepo->findOneBy([
            'entreprise' => $entreprise,
            'etat' => 'ACTIF'
        ]);

        if (!$activeAbonnement || !$activeAbonnement->getModuleAbonnement()) {
            return;
        }

        $module = $activeAbonnement->getModuleAbonnement();

        // --- A. BIENS (MAISONS) ---
        $maxBiens = $module->getMaxBiens();
        $this->enforceEntityLimit($entreprise, Maison::class, $maxBiens, 'JOIN entity.agence a WHERE a.entreprise = :entreprise');

        // --- B. AGENCES ---
        $maxAgences = $module->getMaxAgences();
        $this->enforceEntityLimit($entreprise, Agence::class, $maxAgences, 'WHERE entity.entreprise = :entreprise');

        // --- C. EMPLOYES ---
        $maxEmployes = $module->getMaxEmployes();
        $this->enforceEntityLimit($entreprise, Employe::class, $maxEmployes, 'WHERE entity.entreprise = :entreprise');

        // --- D. LOCATAIRES MOBILE APP ---
        $maxLocatairesMobile = $module->getMaxLocatairesMobileApp();
        $this->enforceMobileAccessLimit($entreprise, $maxLocatairesMobile);

        $this->em->flush();
    }

    /**
     * Méthode générique pour limiter le nombre d'entités actives.
     */
    private function enforceEntityLimit(Entreprise $entreprise, string $entityClass, ?int $max, string $condition): void
    {
        if ($max === null) return;

        $entities = $this->em->createQuery("SELECT entity FROM $entityClass entity $condition ORDER BY entity.id ASC")
                            ->setParameter('entreprise', $entreprise)
                            ->getResult();

        $count = count($entities);
        if ($max === -1) {
            foreach ($entities as $entity) {
                if (method_exists($entity, 'setIsActive')) $entity->setIsActive(true);
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $status = ($i < $max);
                if (method_exists($entities[$i], 'setIsActive')) {
                    $entities[$i]->setIsActive($status);
                    $this->em->persist($entities[$i]);
                }
            }
        }
    }

    /**
     * Limite l'accès mobile des locataires.
     */
    private function enforceMobileAccessLimit(Entreprise $entreprise, ?int $max): void
    {
        if ($max === null) return;

        // On cherche les locataires qui ont un utilisateur associé
        $locatairesWithUser = $this->em->createQuery('SELECT l FROM App\Entity\Locataire l 
                                                     JOIN l.user u 
                                                     WHERE l.entreprise = :entreprise 
                                                     ORDER BY l.id ASC')
                                      ->setParameter('entreprise', $entreprise)
                                      ->getResult();

        $count = count($locatairesWithUser);
        if ($max === -1) {
            foreach ($locatairesWithUser as $locataire) {
                if ($locataire->getUser()) {
                    $locataire->getUser()->setIsActive(true);
                }
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $locataire = $locatairesWithUser[$i];
                if ($locataire->getUser()) {
                    $locataire->getUser()->setIsActive($i < $max);
                    $this->em->persist($locataire->getUser());
                }
            }
        }
    }

    /**
     * Vérifie si l'entreprise peut ajouter une nouvelle maison.
     */
    public function canAddMaison(Entreprise $entreprise): bool
    {
        return $this->canAddEntity($entreprise, Maison::class, 'getMaxBiens', 'JOIN entity.agence a WHERE a.entreprise = :entreprise');
    }

    /**
     * Vérifie si l'entreprise peut ajouter un nouveau propriétaire.
     */
    public function canAddProprio(Entreprise $entreprise): bool
    {
        return $this->canAddEntity($entreprise, \App\Entity\Proprio::class, 'getMaxBiens', 'JOIN entity.agence a WHERE a.entreprise = :entreprise');
    }

    /**
     * Vérifie si l'entreprise peut ajouter un nouveau locataire.
     */
    public function canAddLocataire(Entreprise $entreprise): bool
    {
        return $this->canAddEntity($entreprise, Locataire::class, 'getMaxLocatairesMobileApp', 'WHERE entity.entreprise = :entreprise');
    }

    /**
     * Vérifie si l'entreprise peut ajouter un nouvel utilisateur classique.
     */
    public function canAddUser(Entreprise $entreprise): bool
    {
        return $this->canAddEntity($entreprise, User::class, 'getMaxEmployes', 'JOIN entity.agence a WHERE a.entreprise = :entreprise');
    }

    /**
     * Vérifie si l'entreprise peut ajouter une nouvelle agence.
     */
    public function canAddAgence(Entreprise $entreprise): bool
    {
        return $this->canAddEntity($entreprise, Agence::class, 'getMaxAgences', 'WHERE entity.entreprise = :entreprise');
    }

    /**
     * Vérifie si l'entreprise peut ajouter un nouvel employé.
     */
    public function canAddEmploye(Entreprise $entreprise): bool
    {
        return $this->canAddEntity($entreprise, Employe::class, 'getMaxEmployes', 'WHERE entity.entreprise = :entreprise');
    }

    /**
     * Vérifie si l'entreprise peut activer l'accès mobile pour un locataire.
     */
    public function canAddLocataireMobileApp(Entreprise $entreprise): bool
    {
        $activeAbonnement = $this->abonnementRepo->findOneBy(['entreprise' => $entreprise, 'etat' => 'ACTIF']);
        if (!$activeAbonnement || !$activeAbonnement->getModuleAbonnement()) return false;

        $max = $activeAbonnement->getModuleAbonnement()->getMaxLocatairesMobileApp();
        if ($max === -1 || $max === null) return true;

        $count = $this->em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u 
                                        JOIN u.locataire l 
                                        WHERE l.entreprise = :entreprise AND u.isActive = true')
                         ->setParameter('entreprise', $entreprise)
                         ->getSingleScalarResult();

        return $count < $max;
    }

    private function canAddEntity(Entreprise $entreprise, string $entityClass, string $maxMethod, string $condition): bool
    {
        $activeAbonnement = $this->abonnementRepo->findOneBy(['entreprise' => $entreprise, 'etat' => 'ACTIF']);
        
        $max = 1; // Default quota pour abonnement Gratuit (ou sans abonnement)
        
        if ($activeAbonnement && $activeAbonnement->getModuleAbonnement()) {
            $limit = $activeAbonnement->getModuleAbonnement()->$maxMethod();
            if ($limit === -1 || $limit === null) return true;
            $max = $limit;
        }

        $alias = 'entity';
        $count = $this->em->createQuery("SELECT COUNT($alias.id) FROM $entityClass $alias $condition AND $alias.isActive = true")
                         ->setParameter('entreprise', $entreprise)
                         ->getSingleScalarResult();

        return $count < $max;
    }

    /**
     * Retourne le nom de l'abonnement actuel de l'entreprise.
     */
    public function getCurrentPlanName(Entreprise $entreprise): string
    {
        $activeAbonnement = $this->abonnementRepo->findOneBy(['entreprise' => $entreprise, 'etat' => 'ACTIF']);
        if ($activeAbonnement && $activeAbonnement->getModuleAbonnement()) {
            return $activeAbonnement->getModuleAbonnement()->getCode();
        }
        return 'GRATUIT';
    }
}
