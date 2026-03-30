<?php

namespace App\Repository;

use App\Entity\Agence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Agence>
 */
class AgenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agence::class);
    }

    /**
     * Centralized query for agencies with filters
     */
    public function findWithFilters($entreprise, $search = null, $agenceId = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->andWhere('a.isActive = true');

        if ($agenceId) {
            $qb->andWhere('a.id = :agenceId')
               ->setParameter('agenceId', $agenceId);
        }

        if ($search) {
            $qb->andWhere('a.nom LIKE :search OR a.contact LIKE :search OR a.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Agence[] Returns an array of Agence objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Agence
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
