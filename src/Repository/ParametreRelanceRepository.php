<?php

namespace App\Repository;

use App\Entity\ParametreRelance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParametreRelance>
 */
class ParametreRelanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametreRelance::class);
    }

    /** @return ParametreRelance[] */
    public function findAutomatiques(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.agence', 'a')
            ->where('p.mode = :mode')
            ->andWhere('a.isActive = true')
            ->setParameter('mode', ParametreRelance::MODE_AUTOMATIQUE)
            ->getQuery()
            ->getResult();
    }
}
