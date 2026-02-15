<?php

namespace App\Repository;

use App\Entity\ConnectionLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConnectionLog>
 *
 * @method ConnectionLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConnectionLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConnectionLog[]    findAll()
 * @method ConnectionLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConnectionLog::class);
    }

    public function add(ConnectionLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ConnectionLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
