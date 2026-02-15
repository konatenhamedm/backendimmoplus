<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Pays;
use App\Repository\PaysRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pays')]
#[OA\Tag(name: 'Pays', description: 'Gestion des pays')]
class ApiPaysController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/pays/",
        summary: "Lister les pays",
        description: "Retourne la liste des pays.",
        tags: ['Pays']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, PaysRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $pays = $repository->findAll();

            if ($withPagination == "true") {
                $pays = $this->paginationService->paginate($pays);
            }

            return $this->responseData($pays, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/pays/create",
        summary: "Créer un pays",
        description: "Ajoute un nouveau pays.",
        tags: ['Pays']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "libelle"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "CI"),
                new OA\Property(property: "libelle", type: "string", example: "Côte d'Ivoire")
            ]
        )
    )]
    public function create(Request $request, PaysRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $pays = new Pays();
            
            if (isset($data['code'])) $pays->setCode($data['code']);
            if (isset($data['libelle'])) $pays->setLibelle($data['libelle']);

            $repository->save($pays, true);

            return $this->responseData($pays);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/pays/{id}",
        summary: "Modifier un pays",
        description: "Met à jour un pays existant.",
        tags: ['Pays']
    )]
    public function update(Request $request, Pays $pays, PaysRepository $repository): Response
    {
        try {
            if (!$pays) return $this->errorResponse(null, "Pays non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['code'])) $pays->setCode($data['code']);
            if (isset($data['libelle'])) $pays->setLibelle($data['libelle']);

            $repository->save($pays, true);

            return $this->responseData($pays);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/pays/{id}",
        summary: "Supprimer un pays",
        description: "Supprime un pays.",
        tags: ['Pays']
    )]
    public function delete(Pays $pays, PaysRepository $repository): Response
    {
        try {
            if (!$pays) return $this->errorResponse(null, "Pays non trouvé", 404);
            $repository->remove($pays, true);
            return $this->response(['message' => 'Pays supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
