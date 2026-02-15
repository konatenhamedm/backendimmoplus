<?php

namespace App\Repository;

use App\Entity\VersmtProprio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VersmtProprio>
 *
 * @method VersmtProprio|null find($id, $lockMode = null, $lockVersion = null)
 * @method VersmtProprio|null findOneBy(array $criteria, array $orderBy = null)
 * @method VersmtProprio[]    findAll()
 * @method VersmtProprio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VersmtProprioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VersmtProprio::class);
    }

    public function save(VersmtProprio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VersmtProprio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByLocataire($locataireId)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.locataire = :locataire')
            ->setParameter('locataire', $locataireId)
            ->getQuery()
            ->getResult();
    }
}
