<?php
namespace App\Repository;

use App\Entity\ReservationResidence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationResidenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $r)
    {
        parent::__construct($r, ReservationResidence::class);
    }

    public function save(ReservationResidence $e, bool $flush = false): void
    {
        $this->getEntityManager()->persist($e);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(ReservationResidence $e, bool $flush = false): void
    {
        $this->getEntityManager()->remove($e);
        if ($flush) $this->getEntityManager()->flush();
    }

    /**
     * Trouve les réservations qui chevauchent la période donnée pour une résidence.
     * On exclut les réservations ANNULEE et optionnellement la réservation en cours d'édition.
     */
    public function findConflictingPeriods(
        int $residenceId,
        \DateTimeInterface $dateDebut,
        \DateTimeInterface $dateFin,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->join('r.residence', 'res')
            ->where('res.id = :rid')
            ->andWhere('r.etat != :annulee')
            ->andWhere('r.dateDebut < :fin')
            ->andWhere('r.dateFin > :debut')
            ->setParameter('rid',     $residenceId)
            ->setParameter('annulee', 'ANNULEE')
            ->setParameter('debut',   $dateDebut)
            ->setParameter('fin',     $dateFin);

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}
