<?php

namespace App\Repository;

use App\Entity\JoursMoisEntreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JoursMoisEntreprise>
 *
 * @method JoursMoisEntreprise|null find($id, $lockMode = null, $lockVersion = null)
 * @method JoursMoisEntreprise|null findOneBy(array $criteria, array $orderBy = null)
 * @method JoursMoisEntreprise[]    findAll()
 * @method JoursMoisEntreprise[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JoursMoisEntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JoursMoisEntreprise::class);
    }
    public function save(JoursMoisEntreprise $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getJour($entreprise){
               return $this->createQueryBuilder('j')
                   ->select('m.libelle')
                   ->innerJoin('j.joursMois','m')
            ->andWhere('j.entreprise = :entreprise')
            ->andWhere('j.active = :active')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('active', 1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return JoursMoisEntreprise[] Returns an array of JoursMoisEntreprise objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('j.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?JoursMoisEntreprise
//    {
//        return $this->createQueryBuilder('j')
//            ->andWhere('j.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
