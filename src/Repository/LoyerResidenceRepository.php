<?php
namespace App\Repository;

use App\Entity\LoyerResidence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoyerResidenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $r)
    {
        parent::__construct($r, LoyerResidence::class);
    }

    public function save(LoyerResidence $e, bool $flush = false): void
    {
        $this->getEntityManager()->persist($e);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(LoyerResidence $e, bool $flush = false): void
    {
        $this->getEntityManager()->remove($e);
        if ($flush) $this->getEntityManager()->flush();
    }
}
