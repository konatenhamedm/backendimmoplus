<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\CompteCltT;
use App\Repository\CompteCltTRepository;
use App\Repository\TerrainRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/compte-clt-t')]
#[OA\Tag(name: 'CompteCltT', description: 'Gestion des comptes clients pour les terrains')]
class ApiCompteCltTController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/compte-clt-t/",
        summary: "Lister les comptes clients terrains",
        tags: ['CompteCltT']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, CompteCltTRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $comptes = $repository->findAll();

            if ($withPagination == "true") {
                $comptes = $this->paginationService->paginate($comptes);
            }

            return $this->responseData($comptes, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/compte-clt-t/create",
        summary: "Créer un compte client terrain",
        tags: ['CompteCltT']
    )]
    public function create(Request $request, CompteCltTRepository $repository, TerrainRepository $terrainRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $compte = new CompteCltT();

            if (isset($data['montant'])) $compte->setMontant($data['montant']);
            if (isset($data['solde'])) $compte->setSolde($data['solde']);

            if (isset($data['terrain_id'])) {
                $terrain = $terrainRepository->find($data['terrain_id']);
                if ($terrain) $compte->setTerrain($terrain);
            }

            if (isset($data['datecreation'])) {
                $compte->setDatecreation(new \DateTime($data['datecreation']));
            }

            $this->updateAuditFields($compte, true);
            $repository->save($compte, true);

            return $this->responseData($compte);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/compte-clt-t/{id}",
        summary: "Détails d'un compte client terrain",
        tags: ['CompteCltT']
    )]
    public function show(CompteCltT $compte): Response
    {
        try {
            if (!$compte) return $this->errorResponse(null, "Compte non trouvé", 404);
            return $this->responseData($compte);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/compte-clt-t/{id}",
        summary: "Modifier un compte client terrain",
        tags: ['CompteCltT']
    )]
    public function update(Request $request, CompteCltT $compte, CompteCltTRepository $repository, TerrainRepository $terrainRepository): Response
    {
        try {
            if (!$compte) return $this->errorResponse(null, "Compte non trouvé", 404);
            $data = json_decode($request->getContent(), true);

            if (isset($data['montant'])) $compte->setMontant($data['montant']);
            if (isset($data['solde'])) $compte->setSolde($data['solde']);

            if (isset($data['terrain_id'])) {
                $terrain = $terrainRepository->find($data['terrain_id']);
                if ($terrain) $compte->setTerrain($terrain);
            }

            if (isset($data['datecreation'])) {
                $compte->setDatecreation(new \DateTime($data['datecreation']));
            }

            $this->updateAuditFields($compte);
            $repository->save($compte, true);

            return $this->responseData($compte);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/compte-clt-t/{id}",
        summary: "Supprimer un compte client terrain",
        tags: ['CompteCltT']
    )]
    public function delete(CompteCltT $compte, CompteCltTRepository $repository): Response
    {
        try {
            if (!$compte) return $this->errorResponse(null, "Compte non trouvé", 404);
            $repository->remove($compte, true);
            return $this->response(['message' => 'Compte supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
