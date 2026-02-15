<?php

namespace App\Repository;

use App\Entity\FactureLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FactureLocation>
 *
 * @method FactureLocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactureLocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactureLocation[]    findAll()
 * @method FactureLocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureLocation::class);
    }

    public function save(FactureLocation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FactureLocation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('f')
            ->join('f.locataire', 'l')
            ->andWhere('l.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }


    /**
     * @return FactureLocation[] Returns an array of FactureLocation objects
     */
    public function findAllFactureLocataire($value): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.locataire', 'l')
            ->andWhere('l.id = :id')
            ->andWhere('f.statut = :statut')
            ->setParameter('id', $value)
            ->setParameter('statut', 'impayer')
            ->orderBy('f.DateEmission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FactureLocation[] Returns an array of FactureLocation objects
     */
    public function findAllFactureLocataireByAgentCampagne($agent, $campagne): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.locataire', 'l')
            ->innerJoin('f.compagne', 'c')
            ->innerJoin('f.appartement', 'a')
            ->innerJoin('a.maisson', 'm')
            ->innerJoin('m.IdAgent', 'ag')
            ->andWhere('f.compagne = :campagne')
            ->andWhere('ag.id = :agent')
            /* ->andWhere('f.statut = :statut') */
            ->setParameter('agent', $agent)
            ->setParameter('campagne', $campagne)
            /* ->setParameter('statut', 'payer') */
            ->getQuery()
            ->getResult();
    }


    /**
     * @return FactureLocation[] Returns an array of FactureLocation objects
     */
    public function findAllFactureLocataireByAgentCampagneTotal($agent, $campagne): array
    {
        return $this->createQueryBuilder('f')
            ->select('sum(f.MntFact) - sum(f.SoldeFactLoc) encaisse,sum(f.SoldeFactLoc) reste')
            ->innerJoin('f.locataire', 'l')
            ->innerJoin('f.compagne', 'c')
            ->innerJoin('f.appartement', 'a')
            ->innerJoin('a.maisson', 'm')
            ->innerJoin('m.IdAgent', 'ag')
            ->andWhere('f.compagne = :campagne')
            ->andWhere('ag.id = :agent')
            /* ->andWhere('f.statut = :statut') */
            ->setParameter('agent', $agent)
            ->setParameter('campagne', $campagne)
            /* ->setParameter('statut', 'payer') */
            ->getQuery()
            ->getResult();
    }



    public function findAllFactureCampagne($value)
    {
        return $this->createQueryBuilder('f')
            ->select('SUM(f.SoldeFactLoc) as somme')
            ->innerJoin('f.compagne', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllFactureCampagneImprime($value)
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.compagne', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FactureLocation[] Returns an array of FactureLocation objects
     */
    public function findAllFactureLocataireImpayer($value): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.LibFacture', 'f.SoldeFactLoc', 'f.DateLimite')
            ->innerJoin('f.locataire', 'l')
            ->andWhere('l.id = :id')
            ->andWhere('f.statut = :statut')
            ->setParameter('id', $value)
            ->setParameter('statut', 'impayer')
            /* ->groupBy('f.LibFacture', 'f.DateLimite') */
            ->getQuery()
            ->getResult();
    }

    //    public function findOneBySomeField($value): ?Factureloc
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
