<?php

namespace App\Repository;

use App\Entity\ChargeProprio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChargeProprio>
 */
class ChargeProprioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChargeProprio::class);
    }

    public function save(ChargeProprio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChargeProprio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Centralized query for owner charges with filters
     */
    public function findWithFilters($entreprise, $agence = null, $proprioId = null, $search = null, $dateStart = null, $dateEnd = null)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        if ($agence && $agence !== 'all' && $agence !== 'null') {
            $qb->leftJoin('c.maison', 'm_agence')
               ->andWhere('m_agence.agence = :agence')
               ->setParameter('agence', $agence);
        }

        if ($proprioId && $proprioId !== 'all' && $proprioId !== 'null') {
            $qb->andWhere('c.proprio = :proprio')
               ->setParameter('proprio', $proprioId);
        }

        if ($search) {
            $qb->leftJoin('c.proprio', 'p_search')
               ->leftJoin('c.maison', 'm_search')
               ->andWhere('c.libelle LIKE :search OR p_search.nom LIKE :search OR p_search.prenoms LIKE :search OR m_search.libMaison LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($dateStart) {
            $qb->andWhere('c.dateCharge >= :dateStart')
               ->setParameter('dateStart', $dateStart . ' 00:00:00');
        }

        if ($dateEnd) {
            $qb->andWhere('c.dateCharge <= :dateEnd')
               ->setParameter('dateEnd', $dateEnd . ' 23:59:59');
        }

        return $qb->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
