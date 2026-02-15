<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Civilite;
use App\Repository\CiviliteRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/civilite')]
#[OA\Tag(name: 'Civilite', description: 'Gestion des civilités')]
class ApiCiviliteController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/civilite/",
        summary: "Lister les civilités",
        description: "Retourne la liste des civilités.",
        tags: ['Civilite']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, CiviliteRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $civilites = $repository->findAll();

            if ($withPagination == "true") {
                $civilites = $this->paginationService->paginate($civilites);
            }

            return $this->responseData($civilites, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/civilite/create",
        summary: "Créer une civilité",
        description: "Ajoute une nouvelle civilité.",
        tags: ['Civilite']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "libelle"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "M"),
                new OA\Property(property: "libelle", type: "string", example: "Monsieur")
            ]
        )
    )]
    public function create(Request $request, CiviliteRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $civilite = new Civilite();
            
            if (isset($data['code'])) $civilite->setCode($data['code']);
            if (isset($data['libelle'])) $civilite->setLibelle($data['libelle']);
            $this->updateAuditFields($civilite, true);
            $repository->save($civilite, true);

            return $this->responseData($civilite, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/civilite/{id}",
        summary: "Modifier une civilité",
        description: "Met à jour une civilité existante.",
        tags: ['Civilite']
    )]
    public function update(Request $request, Civilite $civilite, CiviliteRepository $repository): Response
    {
        try {
            if (!$civilite) return $this->errorResponse(null, "Civilite non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['code'])) $civilite->setCode($data['code']);
            if (isset($data['libelle'])) $civilite->setLibelle($data['libelle']);
            $this->updateAuditFields($civilite);
            $repository->save($civilite, true);

            return $this->responseData($civilite, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/civilite/{id}",
        summary: "Supprimer une civilité",
        description: "Supprime une civilité.",
        tags: ['Civilite']
    )]
    public function delete(Civilite $civilite, CiviliteRepository $repository): Response
    {
        try {
            if (!$civilite) return $this->errorResponse(null, "Civilite non trouvée", 404);
            $repository->remove($civilite, true);
            return $this->response(['message' => 'Civilite supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
