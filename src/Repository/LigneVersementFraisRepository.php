<?php

namespace App\Repository;

use App\Entity\Ligneversementfrais;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ligneversementfrais>
 *
 * @method Ligneversementfrais|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ligneversementfrais|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ligneversementfrais[]    findAll()
 * @method Ligneversementfrais[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LigneVersementFraisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ligneversementfrais::class);
    }

    public function save(Ligneversementfrais $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ligneversementfrais $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Ligneversementfrais[] Returns an array of Ligneversementfrais objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Ligneversementfrais
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
