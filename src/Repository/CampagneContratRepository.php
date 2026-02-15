<?php

namespace App\Repository;

use App\Entity\CampagneContrat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CampagneContrat>
 *
 * @method CampagneContrat|null find($id, $lockMode = null, $lockVersion = null)
 * @method CampagneContrat|null findOneBy(array $criteria, array $orderBy = null)
 * @method CampagneContrat[]    findAll()
 * @method CampagneContrat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampagneContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampagneContrat::class);
    }

    public function save(CampagneContrat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CampagneContrat $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
