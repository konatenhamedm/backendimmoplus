<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Nature;
use App\Repository\NatureRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/nature')]
#[OA\Tag(name: 'nature', description: 'Gestion des natures')]
class ApiNatureController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/nature/",
        summary: "Lister les natures",
        description: "Retourne la liste des types de maison.",
        tags: ['nature']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, NatureRepository $repository): Response
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
        tags: ['nature']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle"],
            properties: [
                new OA\Property(property: "libelle", type: "string", example: "Villa")
            ]
        )
    )]
    public function create(Request $request, NatureRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $nature = new Nature();
            
            if (isset($data['libNature'])) $nature->setLibNature($data['libNature']);

            $repository->save($nature, true);

            return $this->responseData($nature, 'group1');
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
        tags: ['nature']
    )]
    public function update(Request $request, Nature $nature, NatureRepository $repository): Response
    {
        try {
            if (!$nature) return $this->errorResponse(null, "Type de maison non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libNature'])) $nature->setLibNature($data['libNature']);

            $repository->save($nature, true);

            return $this->responseData($nature, 'group1');
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
        tags: ['nature']
    )]
    public function delete(Nature $nature, NatureRepository $repository): Response
    {
        try {
            if (!$nature) return $this->errorResponse(null, "Type de maison non trouvé", 404);
            $repository->remove($nature, true);
            return $this->response(['message' => 'Type de maison supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
