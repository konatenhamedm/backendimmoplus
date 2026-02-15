<?php

namespace App\Repository;

use App\Entity\ContratLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratLocation>
 *
 * @method ContratLocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContratLocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContratLocation[]    findAll()
 * @method ContratLocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContratLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratLocation::class);
    }

    public function save(ContratLocation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContratLocation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.locataire', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }


    public function getContratLocActif($entreprise): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.locataire', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->andWhere('c.Etat = :etat')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('etat', 1)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }




    //    /**
    //     * @return Contratloc[] Returns an array of Contratloc objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Contratloc
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
