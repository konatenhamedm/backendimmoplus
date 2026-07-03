<?php

namespace App\Repository;

use App\Entity\PlanningRecouvrement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanningRecouvrement>
 */
class PlanningRecouvrementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanningRecouvrement::class);
    }

    public function save(PlanningRecouvrement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PlanningRecouvrement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.locataire', 'l')
            ->addSelect('l')
            ->leftJoin('p.agent', 'a')
            ->addSelect('a')
            ->andWhere('p.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findAllPlannings()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.locataire', 'l')
            ->addSelect('l')
            ->leftJoin('p.agent', 'a')
            ->addSelect('a')
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
