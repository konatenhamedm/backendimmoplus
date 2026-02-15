<?php

namespace App\Repository;

use App\Entity\Appartement;
use App\Entity\Contratloc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appartement>
 *
 * @method Appartement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Appartement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Appartement[]    findAll()
 * @method Appartement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppartementRepository extends ServiceEntityRepository
{

    private $em;
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager,)
    {
        parent::__construct($registry, Appartement::class);
        $this->em = $entityManager;
    }


    public function save(Appartement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getAppart($appart, $maison)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.maisson', "m")
            ->andWhere('a.LibAppart = :appart')
            ->andWhere('m.LibMaison = :maison')
            ->setParameter('appart', $appart)
            ->setParameter('maison', $maison)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public  function findNiveauDisponible()
    {
        $qb = $this->em->createQueryBuilder();

        $linked = $qb->select('a')
            ->from(Contratloc::class, 'rl')
            ->innerJoin('rl.appart', 'a')
            ->andWhere('rl.id = :id')
            ->setParameter('id', 12);
        /* ->getQuery()
            ->getResult(); */


        $qb2 = $this->createQueryBuilder('e');
        $req2 = $qb2->select('e')
            ->where('e.etat = :etat')
            ->setParameter('etat', 0);

        return $req2;
    }

    public function firstQuery()
    {

        /* $qb1 = $this->createQueryBuilder('e');
        $qb1->select('e')
            ->andWhere('e.id = :id')
            ->setParameter('id', 6)
            ->setMaxResults(1);

        $qb2 = $this->createQueryBuilder('e');
        $qb2->select('e')
            ->andWhere('e.Oqp = :etat')
            ->setParameter('etat', 0);


        $sql = "SELECT * FROM (
                ({$qb1})
                UNION ALL
                ({$qb2})
            ) tmp 
       
           LIMIT ? OFFSET ?
           ";

        return $sql; */

        return  $this->createQueryBuilder('e')
            ->select('e')
            ->leftJoin('e.appartContratlocs', 'temp', 'WITH')
            ->orWhere('temp.id = :id')
            ->orWhere('e.Oqp = :etat')
            ->setParameter('id', 12)
            ->setParameter('etat', 0)
            ->getQuery()
            ->getResult();
    }

    public function getAppartContrat($contrat, $appartId): ?Appartement
    {
        return  $this->createQueryBuilder('e')
            ->select('e')
            ->leftJoin('e.appartContratlocs', 'temp', 'WITH')
            ->andWhere('temp.id = :id')
            ->andWhere('e.Oqp = :etat')
            ->andWhere('e.id = :appart')
            ->setParameter('id', $contrat)
            ->setParameter('etat', 1)
            ->setParameter('appart', $appartId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('a')
            ->join('a.maisson', 'm')
            ->join('m.proprio', 'p')
            ->andWhere('p.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    public function findAllByMaisonAndEntreprise($maisonId, $entreprise)
    {
        return $this->createQueryBuilder('a')
            ->join('a.maisson', 'm')
            ->join('m.proprio', 'p')
            ->andWhere('m.id = :maisonId')
            ->andWhere('p.entreprise = :entreprise')
            ->setParameter('maisonId', $maisonId)
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }



    //    /**
    //     * @return Appartement[] Returns an array of Appartement objects
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

    //    public function findOneBySomeField($value): ?Appartement
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
