<?php

namespace App\Repository;

use App\Entity\ParametrePenalite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParametrePenalite>
 */
class ParametrePenaliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametrePenalite::class);
    }

    /** @return ParametrePenalite[] */
    public function findActifs(): array
    {
        return $this->findBy(['actif' => true]);
    }
}
