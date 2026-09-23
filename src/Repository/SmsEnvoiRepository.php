<?php

namespace App\Repository;

use App\Entity\Entreprise;
use App\Entity\SmsEnvoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SmsEnvoi>
 */
class SmsEnvoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmsEnvoi::class);
    }

    /** Nombre de SMS envoyés avec succès par l'entreprise depuis une date. */
    public function countEnvoyesDepuis(Entreprise $entreprise, \DateTimeInterface $depuis): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COALESCE(SUM(s.nbSms), 0)')
            ->where('s.entreprise = :entreprise')
            ->andWhere('s.statut = :statut')
            ->andWhere('s.dateEnvoi >= :depuis')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('statut', SmsEnvoi::STATUT_ENVOYE)
            ->setParameter('depuis', $depuis)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
