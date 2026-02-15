<?php

namespace App\Repository;

use App\Entity\Echancier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Echancier>
 *
 * @method Echancier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Echancier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Echancier[]    findAll()
 * @method Echancier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EchancierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Echancier::class);
    }

//    /**
//     * @return Echancier[] Returns an array of Echancier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Echancier
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
