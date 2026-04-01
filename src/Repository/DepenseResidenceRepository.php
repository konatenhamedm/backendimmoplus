<?php
namespace App\Repository;

use App\Entity\DepenseResidence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DepenseResidenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $r)
    {
        parent::__construct($r, DepenseResidence::class);
    }

    public function save(DepenseResidence $e, bool $flush = false): void
    {
        $this->getEntityManager()->persist($e);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(DepenseResidence $e, bool $flush = false): void
    {
        $this->getEntityManager()->remove($e);
        if ($flush) $this->getEntityManager()->flush();
    }
}
