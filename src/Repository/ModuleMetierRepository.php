<?php

namespace App\Repository;

use App\Entity\ModuleMetier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleMetier>
 */
class ModuleMetierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleMetier::class);
    }

    /** @return ModuleMetier[] */
    public function findActifs(): array
    {
        return $this->findBy(['isActive' => true], ['ordre' => 'ASC', 'libelle' => 'ASC']);
    }
}
