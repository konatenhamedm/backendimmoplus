<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Repository\FactureLocationRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/point-paiement')]
#[OA\Tag(name: 'PointPaiement', description: 'État des paiements (Locataires et Propriétaires)')]
class ApiPointpaiementController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    #[Route('/locataires', methods: ['GET'])]
    #[OA\Get(
        path: "/api/point-paiement/locataires",
        summary: "État des paiements des locataires",
        description: "Liste les factures de location avec détails locataire et maison.",
        tags: ['PointPaiement']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination", schema: new OA\Schema(type: "string"))]
    public function indexLocataire(Request $request, FactureLocationRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            $qb = $repository->createQueryBuilder('f')
                ->select('f', 'l', 'a', 'm', 'c')
                ->join('f.locataire', 'l')
                ->join('f.appartement', 'a')
                ->join('a.maisson', 'm')
                ->join('f.compagne', 'c');

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $qb->andWhere('l.entreprise = :entreprise')
                   ->setParameter('entreprise', $this->getUser()->getEntreprise());
            }

            $factures = $qb->getQuery()->getResult();

            if ($withPagination == "true") {
                $factures = $this->paginationService->paginate($factures);
            }

            return $this->responseData($factures, 'group1', [], $withPagination == "true");
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/proprietaires', methods: ['GET'])]
    #[OA\Get(
        path: "/api/point-paiement/proprietaires",
        summary: "État des reversements propriétaires",
        description: "Liste les factures groupées par propriétaire pour voir les montants à reverser.",
        tags: ['PointPaiement']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination", schema: new OA\Schema(type: "string"))]
    public function indexProprietaire(Request $request, FactureLocationRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            $qb = $repository->createQueryBuilder('f')
                ->select('f', 'm', 'p', 'c')
                ->join('f.appartement', 'a')
                ->join('a.maisson', 'm')
                ->join('m.proprio', 'p')
                ->join('f.compagne', 'c');

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $qb->andWhere('p.entreprise = :entreprise')
                   ->setParameter('entreprise', $this->getUser()->getEntreprise());
            }

            $factures = $qb->getQuery()->getResult();

            if ($withPagination == "true") {
                $factures = $this->paginationService->paginate($factures);
            }

            // Note: Grouping could be done here or on frontend. 
            // The original used DataTables grouping. We return flat list for now.
            return $this->responseData($factures, 'group1', [], $withPagination == "true");
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
