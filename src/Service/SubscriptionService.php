<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\Abonnement;
use App\Entity\Maison;
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
     * Applique les limites du plan actuel de l'entreprise.
     * Si l'entreprise dépasse ses quotas (ex: passage de PRO à BASIC),
     * on désactive les éléments en trop.
     */
    public function enforceLimits(Entreprise $entreprise): void
    {
        // 1. Récupérer l'abonnement actif
        $activeAbonnement = $this->abonnementRepo->findOneBy([
            'entreprise' => $entreprise,
            'etat' => 'ACTIF'
        ]);

        if (!$activeAbonnement || !$activeAbonnement->getModuleAbonnement()) {
            // Pas d'abonnement actif ? On pourrait décider de tout désactiver ou ne rien faire.
            // Pour l'instant on ne fait rien pour éviter de bloquer l'utilisateur s'il y a un délai de grâce.
            return;
        }

        $module = $activeAbonnement->getModuleAbonnement();
        $maxBiens = $module->getMaxBiens();

        // 2. Vérifier les limites de Biens (Maison)
        if ($maxBiens !== -1 && $maxBiens !== null) {
            // Récupérer toutes les maisons de l'entreprise triées par date de création (les plus anciennes d'abord)
            // On utilise l'id comme substitut de date si createdAt n'est pas fiable
            $maisons = $this->maisonRepo->findBy(
                ['agence' => $entreprise->getAgences()], // Note: Maison est liée à Agence
                ['id' => 'ASC']
            );
            
            // Correction: On doit récupérer via les agences de l'entreprise
            // Mais l'entité Maison a un champ 'agence'
            // Une entreprise peut avoir plusieurs agences.
            
            $maisons = $this->em->createQuery('SELECT m FROM App\Entity\Maison m 
                                              JOIN m.agence a 
                                              WHERE a.entreprise = :entreprise 
                                              ORDER BY m.id ASC')
                               ->setParameter('entreprise', $entreprise)
                               ->getResult();

            $count = count($maisons);
            if ($count > $maxBiens) {
                for ($i = 0; $i < $count; $i++) {
                    if ($i < $maxBiens) {
                        // Dans la limite -> Actif
                        $maisons[$i]->setIsActive(true);
                    } else {
                        // Au-delà de la limite -> Désactiver
                        $maisons[$i]->setIsActive(false);
                    }
                    $this->em->persist($maisons[$i]);
                }
            } else {
                // Tout le monde est dans la limite, on s'assure qu'ils sont actifs (ou on laisse tel quel)
                // Si l'utilisateur revient à un plan supérieur, on pourrait tout réactiver
                foreach ($maisons as $maison) {
                    // On ne réactive que si on est sûr que c'était désactivé à cause du plan
                    // Mais pour faire simple, on réactive tout ce qui est dans le quota
                    $maison->setIsActive(true);
                    $this->em->persist($maison);
                }
            }
        } else {
            // Plan illimité (-1) -> Tout le monde actif
            $maisons = $this->em->createQuery('SELECT m FROM App\Entity\Maison m 
                                              JOIN m.agence a 
                                              WHERE a.entreprise = :entreprise')
                               ->setParameter('entreprise', $entreprise)
                               ->getResult();
            foreach ($maisons as $maison) {
                $maison->setIsActive(true);
                $this->em->persist($maison);
            }
        }

        $this->em->flush();
    }

    /**
     * Vérifie si l'entreprise peut ajouter une nouvelle maison.
     */
    public function canAddMaison(Entreprise $entreprise): bool
    {
        $activeAbonnement = $this->abonnementRepo->findOneBy([
            'entreprise' => $entreprise,
            'etat' => 'ACTIF'
        ]);

        if (!$activeAbonnement || !$activeAbonnement->getModuleAbonnement()) {
            return false; // Pas d'abonnement actif
        }

        $module = $activeAbonnement->getModuleAbonnement();
        $maxBiens = $module->getMaxBiens();

        if ($maxBiens === -1 || $maxBiens === null) {
            return true; // Illimité
        }

        // Compter les maisons actives
        $count = $this->em->createQuery('SELECT COUNT(m.id) FROM App\Entity\Maison m 
                                        JOIN m.agence a 
                                        WHERE a.entreprise = :entreprise AND m.isActive = true')
                         ->setParameter('entreprise', $entreprise)
                         ->getSingleScalarResult();

        return $count < $maxBiens;
    }
}
