<?php

namespace App\Repository;

use App\Entity\Maison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maison>
 *
 * @method Maison|null find($id, $lockMode = null, $lockVersion = null)
 * @method Maison|null findOneBy(array $criteria, array $orderBy = null)
 * @method Maison[]    findAll()
 * @method Maison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maison::class);
    }

    public function save(Maison $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Maison $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('m')
            ->join('m.agence', 'a')
            ->join('m.quartier', 'q')
            ->join('m.proprio', 'p')
            ->join('m.typeMaison', 't')
            ->join('m.idAgent', 'ag')
            ->andWhere('a.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    public function findByAgence($agence)
    {
        // Forcing inner joins to avoid orphaned record crashes during serialization
        return $this->createQueryBuilder('m')
            ->join('m.agence', 'a')
            ->join('m.quartier', 'q')
            ->join('m.proprio', 'p')
            ->join('m.typeMaison', 't')
            ->join('m.idAgent', 'ag')
            ->andWhere('m.agence = :agence')
            ->setParameter('agence', $agence)
            ->orderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Maison[] Returns an array of Maison objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Maison
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
