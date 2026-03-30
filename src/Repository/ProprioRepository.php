<?php

namespace App\Repository;

use App\Entity\Proprio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proprio>
 *
 * @method Proprio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Proprio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Proprio[]    findAll()
 * @method Proprio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProprioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proprio::class);
    }

    public function save(Proprio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Proprio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Centralized query for owners with filters
     */
    public function findWithFilters($entreprise, $search = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.prenoms LIKE :search OR p.contacts LIKE :search OR p.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Proprio[] Returns an array of Proprio objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Proprio
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
