<?php

namespace App\Repository;

use App\Entity\JoursMois;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JoursMois>
 *
 * @method JoursMois|null find($id, $lockMode = null, $lockVersion = null)
 * @method JoursMois|null findOneBy(array $criteria, array $orderBy = null)
 * @method JoursMois[]    findAll()
 * @method JoursMois[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JoursMoisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JoursMois::class);
    }

//    /**
//     * @return JoursMois[] Returns an array of JoursMois objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JoursMois
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
