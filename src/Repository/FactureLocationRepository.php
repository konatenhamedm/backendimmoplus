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
            ->andWhere('f.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getResult();
    }

    /**
     * Centralized query for rent invoices with filters
     */
    public function findWithFilters($entreprise, $agence = null, $proprioId = null, $search = null, $statut = null, $isValidated = null, $locataireId = null, $startDate = null, $endDate = null)
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.locataire', 'l')
            ->where('f.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        if ($agence && $agence !== 'all' && $agence !== 'null') {
            $qb->andWhere('f.agence = :agence')
               ->setParameter('agence', $agence);
        }

        if ($locataireId && $locataireId !== 'all' && $locataireId !== 'null') {
            $qb->andWhere('l.id = :locataireId')
               ->setParameter('locataireId', $locataireId);
        }

        if ($startDate) {
            $qb->andWhere('f.dateEmission >= :startDate')
               ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('f.dateEmission <= :endDate')
               ->setParameter('endDate', (new \DateTime($endDate))->setTime(23, 59, 59));
        }

        if ($proprioId && $proprioId !== 'all' && $proprioId !== 'null') {
            $qb->leftJoin('f.appartement', 'a')
               ->leftJoin('a.maisson', 'm')
               ->andWhere('m.proprio = :proprio')
               ->setParameter('proprio', $proprioId);
        }

        if ($statut) {
            $qb->andWhere('f.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($isValidated && $isValidated !== 'all') {
            $qb->andWhere('f.isValidated = :isValidated')
               ->setParameter('isValidated', $isValidated);
        }

        if ($search) {
            $qb->leftJoin('f.appartement', 'a_search')
               ->leftJoin('a_search.maisson', 'm_search')
               ->andWhere('f.libFacture LIKE :search OR l.nom LIKE :search OR l.prenoms LIKE :search OR m_search.libMaison LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('f.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByAgent($agent)
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.appartement', 'a')
            ->innerJoin('a.maisson', 'm')
            ->innerJoin('m.idAgent', 'ag')
            ->andWhere('ag.id = :agent')
            ->setParameter('agent', $agent)
            ->getQuery()
            ->getResult();
    }

    public function findRelancesByAgentWithFilters($agent, $entreprise, $agence = null, $search = null, $statut = 'impayer', $maisonId = null, $locataireId = null)
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.locataire', 'l')
            ->leftJoin('f.appartement', 'a')
            ->leftJoin('a.maisson', 'm')
            ->where('f.entreprise = :entreprise')
            ->andWhere('m.idAgent = :agent')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('agent', $agent);

        if ($agence && $agence !== 'all' && $agence !== 'null') {
            $qb->andWhere('f.agence = :agence')
               ->setParameter('agence', $agence);
        }

        if ($statut && $statut !== 'all') {
            $qb->andWhere('f.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($maisonId && $maisonId !== 'all' && $maisonId !== 'null') {
            $qb->andWhere('m.id = :maisonId')
               ->setParameter('maisonId', $maisonId);
        }

        if ($locataireId && $locataireId !== 'all' && $locataireId !== 'null') {
            $qb->andWhere('l.id = :locataireId')
               ->setParameter('locataireId', $locataireId);
        }

        if ($search) {
            $qb->andWhere('f.libFacture LIKE :search OR l.nom LIKE :search OR l.prenoms LIKE :search OR m.libMaison LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('f.id', 'DESC')
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
            ->orderBy('f.dateEmission', 'DESC')
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
            ->select('sum(f.mntFact) - sum(f.soldeFactLoc) encaisse,sum(f.soldeFactLoc) reste')
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
            ->select('SUM(f.soldeFactLoc) as somme')
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
            ->select('f.libFacture', 'f.soldeFactLoc', 'f.dateLimite')
            ->innerJoin('f.locataire', 'l')
            ->andWhere('l.id = :id')
            ->andWhere('f.statut = :statut')
            ->setParameter('id', $value)
            ->setParameter('statut', 'impayer')
            /* ->groupBy('f.libFacture', 'f.dateLimite') */
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
