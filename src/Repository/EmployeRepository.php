<?php

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Employe>
 *
 * @method Employe|null find($id, $lockMode = null, $lockVersion = null)
 * @method Employe|null findOneBy(array $criteria, array $orderBy = null)
 * @method Employe[]    findAll()
 * @method Employe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeRepository extends ServiceEntityRepository
{
    private $entreprise;
    private $user;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Employe::class);
        $user = $security->getUser();
        if ($user instanceof User) {
             $this->user = $user;
             $this->entreprise = $user->getEntreprise();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        $qb = $this->createQueryBuilder('e');
        
        $qb->andWhere('e.entreprise = :entreprise')
           ->setParameter('entreprise', $entreprise);

        if ($this->user && $this->user->getAgence()) {
            $qb->andWhere('e.agence = :agence')
               ->setParameter('agence', $this->user->getAgence());
        }

        return $qb->getQuery()->getResult();
    }

    public function findByAgence($agence)
    {
        return $this->findBy(['agence' => $agence], ['id' => 'DESC']);
    }

    public function add(Employe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Employe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function save(Employe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * @return mixed
     */
   /*  public function withoutAccount()
    {
        $qb = $this->createQueryBuilder('e');
        
        $qb->select('e')
            ->andWhere('e.user IS NULL');
            
        if ($this->entreprise) {
            $qb->andWhere('e.entreprise = :entreprise')
               ->setParameter('entreprise', $this->entreprise);
        }

        return $qb;
    } */

        public function withoutAccount()
    {
        $qb = $this->createQueryBuilder('e');
       
            $qb->select('e')
                ->leftJoin('e.user', 'u2')
                ->andWhere('u2.employe IS NULL')
                ->andWhere('e.entreprise = :entreprise')
                ->setParameter('entreprise', $this->entreprise);
        


        return $qb;
    }


//    /**
//     * @return Employe[] Returns an array of Employe objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Employe
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
