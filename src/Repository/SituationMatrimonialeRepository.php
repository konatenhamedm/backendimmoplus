<?php

namespace App\Repository;

use App\Entity\SituationMatrimoniale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SituationMatrimoniale>
 *
 * @method SituationMatrimoniale|null find($id, $lockMode = null, $lockVersion = null)
 * @method SituationMatrimoniale|null findOneBy(array $criteria, array $orderBy = null)
 * @method SituationMatrimoniale[]    findAll()
 * @method SituationMatrimoniale[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SituationMatrimonialeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SituationMatrimoniale::class);
    }

    public function save(SituationMatrimoniale $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SituationMatrimoniale $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Sitmatri[] Returns an array of Sitmatri objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sitmatri
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
