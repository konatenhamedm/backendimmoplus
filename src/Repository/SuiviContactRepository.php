<?php

namespace App\Repository;

use App\Entity\SuiviContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuiviContact>
 */
class SuiviContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviContact::class);
    }

    public function save(SuiviContact $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SuiviContact $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('s.dateContact', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
