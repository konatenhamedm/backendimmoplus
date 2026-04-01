<?php
namespace App\Repository;
use App\Entity\Residence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ResidenceRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Residence::class); }
    public function save(Residence $e, bool $flush = false): void { $this->getEntityManager()->persist($e); if ($flush) $this->getEntityManager()->flush(); }
    public function remove(Residence $e, bool $flush = false): void { $this->getEntityManager()->remove($e); if ($flush) $this->getEntityManager()->flush(); }
}
