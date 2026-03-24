<?php

namespace App\Repository;

use App\Entity\GroupeModule;
use App\Entity\Groupe;
use App\Entity\Icon;
use App\Entity\ModuleGroupePermition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleGroupePermition>
 *
 * @method ModuleGroupePermition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleGroupePermition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleGroupePermition[]    findAll()
 * @method ModuleGroupePermition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleGroupePermitionRepository extends ServiceEntityRepository
{

    use TableInfoTrait;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleGroupePermition::class);
    }

    public function save(ModuleGroupePermition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ModuleGroupePermition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPermission($groupe, $lien)
    {
        $resultat = $this->createQueryBuilder('m')
            ->select('p.code', 'm.menuPrincipal')
            //            ->where('m.groupeUser = : val')
            ->innerJoin('m.permition', 'p')
            ->innerJoin('m.groupeModule', 'gm')
            ->innerJoin('m.groupeUser', 'gu')
            ->andWhere('gm.lien = :lien')
            ->andWhere('gu.id = :val')
            ->setParameters(['val' => $groupe, 'lien' => $lien])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();


        return $resultat;
    }

    public function afficheModule($groupe)
    {
        return $this->createQueryBuilder('m')
            ->select('md.id', 'md.titre', 'md.ordre')
            //            ->where('m.groupeUser = : val')
            ->innerJoin('m.module', 'md')
            ->innerJoin('m.groupeUser', 'gu')
            ->andWhere('gu.id = :val')
            ->setParameter('val', $groupe)
            ->groupBy('md.id')
            ->orderBy('md.ordre', 'ASC')
            /*  ->setMaxResults(10)*/
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Récupère la structure complète du menu pour un groupe d'utilisateurs
     * Module = Grand titre (Paramétrage, Gestion Immobilière, etc.)
     * GroupeModule = Ressource/Page (/locataire, /maison, /civilite, etc.)
     */
    public function getMenuStructure($groupeId, $entrepriseId = null): array
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $tableModuleGroupePermition = $this->getTableName(ModuleGroupePermition::class, $em);
        $tablegGroupeModule = $this->getTableName(GroupeModule::class, $em);
        $tablegGroupe = $this->getTableName(Groupe::class, $em);
        $tableModule = $this->getTableName(\App\Entity\Module::class, $em);
        $tablegIcon = $this->getTableName(Icon::class, $em);

        $sql = <<<SQL
            SELECT  
                m.id as module_id,
                m.titre as module_titre,
                m.ordre as module_ordre,
                mi.code as module_icon,
                gm.id as groupe_module_id,
                gm.titre as ressource_titre,
                gm.lien as ressource_lien,
                i.code as ressource_icon,
                mgp.ordre as ressource_ordre,
                mgp.menu_principal
            FROM {$tableModuleGroupePermition} as mgp
            INNER JOIN {$tableModule} as m ON m.id = mgp.module_id
            INNER JOIN {$tablegGroupeModule} as gm ON gm.id = mgp.groupe_module_id
            INNER JOIN {$tablegGroupe} as gu ON gu.id = mgp.groupe_user_id
            LEFT JOIN {$tablegIcon} as i ON gm.icon_id = i.id
            LEFT JOIN {$tablegIcon} as mi ON m.icon_id = mi.id
            WHERE gu.id = :groupe
            AND (mgp.entreprise_id IS NULL OR mgp.entreprise_id = :entreprise)
            ORDER BY m.ordre ASC, mgp.ordre ASC
        SQL;

        $stmt = $connection->executeQuery($sql, ['groupe' => $groupeId, 'entreprise' => $entrepriseId]);

        return $stmt->fetchAllAssociative();
    }

    public function affiche($groupe, $menuPrincipal): array
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $tableModuleGroupePermition = $this->getTableName(ModuleGroupePermition::class, $em);
        $tablegGroupeModule = $this->getTableName(GroupeModule::class, $em);
        $tablegGroupe = $this->getTableName(Groupe::class, $em);
        $tablegIcon = $this->getTableName(Icon::class, $em);

        $sql = <<<SQL
            SELECT  m.groupe_module_id,m.module_id,g.titre,g.lien,i.code as icon
           FROM {$tableModuleGroupePermition} as m
            INNER JOIN {$tablegGroupeModule} as g  on g.id=m.groupe_module_id
            INNER JOIN {$tablegGroupe} as gu on gu.id=m.groupe_user_id
            INNER JOIN {$tablegIcon} as i on g.icon_id = i.id
            where gu.id =:groupe and m.menu_principal =1
             order by m.ordre_groupe ASC

           SQL;


        $ands = [];



        if ($ands) {
            $sql .= ' AND ';
        }


        $sql .= implode(' AND ', $ands);

        $params['groupe'] = $groupe;
        $params['menuPrincipal'] = $menuPrincipal;


        $stmt = $connection->executeQuery($sql, $params);

        return $stmt->fetchAllAssociative();
    }


    public function afficheGroupe()
    {
        return $this->createQueryBuilder('m')
            ->select('g.id', 'g.titre', 'g.ordre', 'md.id')
            ->innerJoin('m.groupeModule', 'g')
            ->leftJoin('m.module', 'md')
            /* ->innerJoin('m.module','md')*/
            /* ->groupBy('g.id')*/
            ->orderBy('g.ordre', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
