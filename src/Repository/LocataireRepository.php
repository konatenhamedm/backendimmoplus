<?php

namespace App\Repository;

use App\Entity\Locataire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Locataire>
 *
 * @method Locataire|null find($id, $lockMode = null, $lockVersion = null)
 * @method Locataire|null findOneBy(array $criteria, array $orderBy = null)
 * @method Locataire[]    findAll()
 * @method Locataire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocataireRepository extends ServiceEntityRepository
{
    private $entreprise;
    private $user;

    public function __construct(ManagerRegistry $registry, \Symfony\Bundle\SecurityBundle\Security $security)
    {
        parent::__construct($registry, Locataire::class);
        $user = $security->getUser();
        if ($user instanceof \App\Entity\User) {
            $this->user = $user;
            $this->entreprise = $user->getEntreprise();
        }
    }

    public function save(Locataire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Locataire $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllByEntreprise($entreprise)
    {
        $qb = $this->createQueryBuilder('l');

        if ($this->user && $this->user->getAgence()) {
            $qb->andWhere('l.agence = :agence')
                ->setParameter('agence', $this->user->getAgence());
        }

        if ($entreprise) {
            $qb->andWhere('l.entreprise = :entreprise')
                ->setParameter('entreprise', $entreprise);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByAgence($agence)
    {
        return $this->findBy(['agence' => $agence], ['id' => 'DESC']);
    }

    /**
     * Centralized query for locataires with multiple filters
     */
    public function findWithFilters($entreprise, $agence = null, $search = null)
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        if ($agence && $agence !== 'all' && $agence !== 'null') {
            $qb->andWhere('l.agence = :agence')
               ->setParameter('agence', $agence);
        }

        if ($search) {
            $qb->andWhere('l.nom LIKE :search OR l.prenoms LIKE :search OR l.profession LIKE :search OR l.contacts LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('l.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function withoutAccount()
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('l')
            ->leftJoin('l.user', 'u')
            ->andWhere('u.id IS NULL');

        if ($this->user->getAgence()) {
            $qb->andWhere('l.agence = :agence')
                ->setParameter('agence', $this->user->getAgence());
        }

        if ($this->entreprise) {
            $qb->andWhere('l.entreprise = :entreprise')
                ->setParameter('entreprise', $this->entreprise);
        }

        return $qb;
    }



//    /**
//     * @return Locataire[] Returns an array of Locataire objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Locataire
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
