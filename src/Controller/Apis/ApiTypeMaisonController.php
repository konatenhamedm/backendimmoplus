<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\TypeMaison;
use App\Repository\TypeMaisonRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/type-maison')]
#[OA\Tag(name: 'typeMaison', description: 'Gestion des types de maison')]
class ApiTypeMaisonController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/type-maison/",
        summary: "Lister les types de maison",
        description: "Retourne la liste des types de maison.",
        tags: ['typeMaison']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, TypeMaisonRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $types = $repository->findAll();

            if ($withPagination == "true") {
                $types = $this->paginationService->paginate($types);
            }

            return $this->responseData($types, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/type-maison/create",
        summary: "Créer un type de maison",
        description: "Ajoute un nouveau type de maison.",
        tags: ['typeMaison']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libType"],
            properties: [
                new OA\Property(property: "libType", type: "string", example: "Villa")
            ]
        )
    )]
    public function create(Request $request, TypeMaisonRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $typeMaison = new TypeMaison();
            
            if (isset($data['libType'])) $typeMaison->setLibType($data['libType']);

            $repository->save($typeMaison, true);

            return $this->responseData($typeMaison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/type-maison/{id}",
        summary: "Modifier un type de maison",
        description: "Met à jour un type de maison existant.",
        tags: ['typeMaison']
    )]
    public function update(Request $request, TypeMaison $typeMaison, TypeMaisonRepository $repository): Response
    {
        try {
            if (!$typeMaison) return $this->errorResponse(null, "Type de maison non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libType'])) $typeMaison->setLibType($data['libType']);

            $repository->save($typeMaison, true);

            return $this->responseData($typeMaison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/type-maison/{id}",
        summary: "Supprimer un type de maison",
        description: "Supprime un type de maison.",
        tags: ['typeMaison']
    )]
    public function delete(TypeMaison $typeMaison, TypeMaisonRepository $repository): Response
    {
        try {
            if (!$typeMaison) return $this->errorResponse(null, "Type de maison non trouvé", 404);
            $repository->remove($typeMaison, true);
            return $this->response(['message' => 'Type de maison supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
