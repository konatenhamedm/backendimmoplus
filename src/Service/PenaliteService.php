<?php

namespace App\Service;

use App\Entity\Agence;
use App\Entity\FactureLocation;
use App\Entity\ParametrePenalite;
use App\Repository\ParametrePenaliteRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Pénalités de retard : montant ajouté au reste à payer des factures dont la date limite est dépassée,
 * selon les réglages de l'agence (fixe ou pourcentage, délai de grâce, récurrence mensuelle, plafond).
 */
class PenaliteService
{
    private const STATUTS_PAYES = ['payer', 'paye', 'solde'];

    public function __construct(
        private EntityManagerInterface $em,
        private ParametrePenaliteRepository $repository,
    ) {
    }

    /** Réglages de l'agence ; valeurs par défaut (désactivées, non enregistrées) sinon. */
    public function getParametres(Agence $agence): ParametrePenalite
    {
        return $this->repository->findOneBy(['agence' => $agence])
            ?? (new ParametrePenalite())->setAgence($agence)->setEntreprise($agence->getEntreprise());
    }

    /** Montant d'une pénalité pour une facture (avant récurrence et plafond). */
    public function montantUnitaire(ParametrePenalite $p, int $montantFacture): int
    {
        return $p->getType() === ParametrePenalite::TYPE_POURCENTAGE
            ? (int) round($montantFacture * $p->getValeur() / 100)
            : (int) round($p->getValeur());
    }

    /**
     * Pénalité totale due à une date donnée.
     *
     * @return array{total: int, nombre: int}
     */
    public function penaliteDue(ParametrePenalite $p, FactureLocation $facture, \DateTimeInterface $aujourdhui): array
    {
        if (!$p->isActif() || !$facture->getDateLimite() || in_array($facture->getStatut(), self::STATUTS_PAYES, true)) {
            return ['total' => $facture->getMntPenalite(), 'nombre' => $facture->getNbPenalites()];
        }

        $limite = \DateTimeImmutable::createFromInterface($facture->getDateLimite())->setTime(0, 0);
        $jour = \DateTimeImmutable::createFromInterface($aujourdhui)->setTime(0, 0);
        $retard = (int) $limite->diff($jour)->format('%r%a');
        $depassement = $retard - $p->getDelaiGrace();
        if ($depassement <= 0) {
            return ['total' => $facture->getMntPenalite(), 'nombre' => $facture->getNbPenalites()];
        }

        // Une pénalité dès la fin du délai de grâce, puis une par tranche de 30 jours supplémentaires
        $nombre = $p->isRecurrenceMensuelle() ? 1 + intdiv($depassement - 1, 30) : 1;
        $total = $this->montantUnitaire($p, (int) $facture->getMntFact()) * $nombre;
        if ($p->getPlafond() !== null) {
            $total = min($total, $p->getPlafond());
        }

        // Jamais de baisse automatique d'une pénalité déjà appliquée
        return ['total' => max($total, $facture->getMntPenalite()), 'nombre' => max($nombre, $facture->getNbPenalites())];
    }

    /**
     * Applique les pénalités dues aux factures impayées de l'agence. Idempotent : seul l'écart avec
     * la pénalité déjà appliquée est ajouté au reste à payer.
     *
     * @return array{factures: int, montant: int}
     */
    public function appliquer(ParametrePenalite $p, ?\DateTimeInterface $aujourdhui = null): array
    {
        $aujourdhui ??= new \DateTimeImmutable('today');
        if (!$p->isActif()) {
            return ['factures' => 0, 'montant' => 0];
        }

        $limiteMax = \DateTimeImmutable::createFromInterface($aujourdhui)->setTime(0, 0)->modify('-' . $p->getDelaiGrace() . ' days');

        /** @var FactureLocation[] $factures */
        $factures = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f')
            ->where('f.agence = :agence')
            ->andWhere('f.statut IS NULL OR f.statut NOT IN (:payes)')
            ->andWhere('f.soldeFactLoc > 0')
            ->andWhere('f.dateLimite < :limite')
            ->setParameter('agence', $p->getAgence())
            ->setParameter('payes', self::STATUTS_PAYES)
            ->setParameter('limite', $limiteMax)
            ->getQuery()
            ->getResult();

        $nbFactures = 0;
        $montant = 0;
        foreach ($factures as $facture) {
            $du = $this->penaliteDue($p, $facture, $aujourdhui);
            $ecart = $du['total'] - $facture->getMntPenalite();
            if ($ecart <= 0) {
                continue;
            }
            $facture->setMntPenalite($du['total'])
                ->setNbPenalites($du['nombre'])
                ->setSoldeFactLoc((int) $facture->getSoldeFactLoc() + $ecart);
            $nbFactures++;
            $montant += $ecart;
        }
        $this->em->flush();

        return ['factures' => $nbFactures, 'montant' => $montant];
    }

    /** Phrase d'exemple affichée dans les écrans de réglage. */
    public function exemple(ParametrePenalite $p, int $loyer = 100000): string
    {
        if (!$p->isActif() || $p->getValeur() <= 0) {
            return 'Aucune pénalité : les factures en retard ne sont pas majorées.';
        }
        $montant = number_format($this->montantUnitaire($p, $loyer), 0, ',', ' ');
        $texte = sprintf(
            'Pour un loyer de %s FCFA : +%s FCFA %d jour(s) après la date limite',
            number_format($loyer, 0, ',', ' '),
            $montant,
            $p->getDelaiGrace()
        );
        if ($p->isRecurrenceMensuelle()) {
            $texte .= ', puis +' . $montant . ' FCFA chaque mois de retard supplémentaire';
        }
        if ($p->getPlafond()) {
            $texte .= sprintf(' (maximum %s FCFA)', number_format($p->getPlafond(), 0, ',', ' '));
        }

        return $texte . '.';
    }
}
