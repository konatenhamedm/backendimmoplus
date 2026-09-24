<?php

namespace App\Service;

use App\Entity\ContratLocation;
use App\Entity\FactureLocation;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Prévient l'agent de recouvrement de la maison quand le contrat d'un de ses locataires
 * est résilié ou arrive à son terme (notification dans l'application + push).
 */
class FinContratNotifier
{
    public const RESILIATION = 'resiliation';
    public const ECHEANCE = 'echeance';

    public function __construct(
        private NotificationService $notificationService,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    /** Renvoie true si un agent a été notifié. Ne lève jamais d'exception. */
    public function notifierAgent(ContratLocation $contrat, string $raison): bool
    {
        try {
            $appartement = $contrat->getAppart();
            $maison = $appartement?->getMaisson();
            $agent = $maison?->getIdAgent();
            $entreprise = $contrat->getEntreprise() ?? $agent?->getEntreprise();
            if (!$agent || !$entreprise) {
                return false;
            }

            $locataire = $contrat->getLocataire();
            $nomLocataire = trim(($locataire?->getNom() ?? '') . ' ' . ($locataire?->getPrenoms() ?? '')) ?: 'Un locataire';
            $logement = implode(' · ', array_filter([$appartement?->getLibAppart(), $maison?->getLibMaison()]));
            $date = ($contrat->getDateFin() ?? new \DateTime())->format('d/m/Y');
            $resteDu = $this->resteDu($contrat);

            $titre = $raison === self::RESILIATION ? '📄 Contrat résilié' : '📅 Contrat arrivé à terme';
            $message = sprintf(
                '%s : le contrat de %s (%s) %s le %s.%s',
                $titre,
                $nomLocataire,
                $logement ?: 'logement',
                $raison === self::RESILIATION ? 'a été résilié' : 'a pris fin',
                $date,
                $resteDu > 0
                    ? sprintf(' Reste dû : %s FCFA, pensez à le recouvrer.', number_format($resteDu, 0, ',', ' '))
                    : ' Aucun impayé.'
            );

            $this->notificationService->notify($agent->getId(), $entreprise, $titre, $message, [
                'type' => 'contrat_' . $raison,
                'contrat_id' => (string) $contrat->getId(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Notification de fin du contrat #{$contrat->getId()} impossible : {$e->getMessage()}");

            return false;
        }
    }

    private function resteDu(ContratLocation $contrat): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(f.soldeFactLoc), 0)')
            ->from(FactureLocation::class, 'f')
            ->where('f.contrat = :contrat')
            ->andWhere('f.statut IS NULL OR f.statut NOT IN (:payes)')
            ->setParameter('contrat', $contrat)
            ->setParameter('payes', ['payer', 'paye', 'solde'])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
