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

    /**
     * Centralized query for lease contracts with filters
     */
    public function findWithFilters($entreprise, $agence = null, $proprioId = null, $search = null, $etat = null, $locataireId = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.locataire', 'l')
            ->where('l.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        if ($agence && $agence !== 'all' && $agence !== 'null') {
            $qb->andWhere('l.agence = :agence')
               ->setParameter('agence', $agence);
        }

        if ($locataireId && $locataireId !== 'all' && $locataireId !== 'null') {
            $qb->andWhere('l.id = :locataireId')
               ->setParameter('locataireId', $locataireId);
        }

        if ($proprioId && $proprioId !== 'all' && $proprioId !== 'null') {
            $qb->leftJoin('c.appart', 'a')
               ->leftJoin('a.maisson', 'm')
               ->andWhere('m.proprio = :proprio')
               ->setParameter('proprio', $proprioId);
        }

        if ($etat !== null && $etat !== '') {
            $qb->andWhere('c.etat = :etat')
                ->setParameter('etat', $etat);
        }

        if ($search) {
            $qb->andWhere('l.nom LIKE :search OR l.prenoms LIKE :search OR c.id LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }


    public function getContratLocActif($entreprise): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.locataire', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->andWhere('c.etat = :etat')
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
