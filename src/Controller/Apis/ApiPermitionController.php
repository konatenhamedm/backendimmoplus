<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Permition;
use App\Repository\PermitionRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/permition')]
#[OA\Tag(name: 'Permission', description: 'Gestion des types de permissions')]
class ApiPermitionController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/permition/",
        summary: "Lister les permissions",
        description: "Retourne la liste des types de permissions.",
        tags: ['Permission']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, PermitionRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $permitions = $repository->findAll();

            if ($withPagination == "true") {
                $permitions = $this->paginationService->paginate($permitions);
            }

            return $this->responseData($permitions, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/permition/create",
        summary: "Créer une permission",
        description: "Ajoute un nouveau type de permission.",
        tags: ['Permission']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "libelle"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "READ"),
                new OA\Property(property: "libelle", type: "string", example: "Lecture seule")
            ]
        )
    )]
    public function create(Request $request, PermitionRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $permition = new Permition();
            
            if (isset($data['code'])) $permition->setCode($data['code']);
            if (isset($data['libelle'])) $permition->setLibelle($data['libelle']);

            $repository->save($permition, true);

            return $this->responseData($permition);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/permition/{id}",
        summary: "Modifier une permission",
        description: "Met à jour un type de permission existant.",
        tags: ['Permission']
    )]
    public function update(Request $request, Permition $permition, PermitionRepository $repository): Response
    {
        try {
            if (!$permition) return $this->errorResponse(null, "Permission non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['code'])) $permition->setCode($data['code']);
            if (isset($data['libelle'])) $permition->setLibelle($data['libelle']);

            $repository->save($permition, true);

            return $this->responseData($permition);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/permition/{id}",
        summary: "Supprimer une permission",
        description: "Supprime un type de permission.",
        tags: ['Permission']
    )]
    public function delete(Permition $permition, PermitionRepository $repository): Response
    {
        try {
            if (!$permition) return $this->errorResponse(null, "Permission non trouvée", 404);
            $repository->remove($permition, true);
            return $this->response(['message' => 'Permission supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
