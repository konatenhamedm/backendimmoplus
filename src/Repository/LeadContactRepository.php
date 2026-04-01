<?php

namespace App\Repository;

use App\Entity\LeadContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LeadContact>
 *
 * @method LeadContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method LeadContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method LeadContact[]    findAll()
 * @method LeadContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeadContact::class);
    }
}
