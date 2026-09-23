<?php

namespace App\Repository;

use App\Entity\Agence;
use App\Entity\ModeleRelance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModeleRelance>
 */
class ModeleRelanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModeleRelance::class);
    }

    /** @return ModeleRelance[] le modèle par défaut en premier */
    public function findByAgence(Agence $agence): array
    {
        return $this->findBy(['agence' => $agence], ['parDefaut' => 'DESC', 'libelle' => 'ASC']);
    }

    public function findDefaut(Agence $agence): ?ModeleRelance
    {
        return $this->findOneBy(['agence' => $agence, 'parDefaut' => true]);
    }
}
