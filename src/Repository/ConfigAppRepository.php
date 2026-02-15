<?php

namespace App\Repository;

use App\Entity\ConfigApp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigApp>
 *
 * @method ConfigApp|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConfigApp|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConfigApp[]    findAll()
 * @method ConfigApp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigAppRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigApp::class);
    }

    public function save(ConfigApp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ConfigApp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByEntreprise($entreprise)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
