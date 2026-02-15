<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Service;
use App\Repository\ServiceRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/service')]
#[OA\Tag(name: 'Service', description: 'Gestion des services')]
class ApiServiceController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/service/",
        summary: "Lister les services",
        description: "Retourne la liste des services.",
        tags: ['Service']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, ServiceRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $services = $repository->findAll();

            if ($withPagination == "true") {
                $services = $this->paginationService->paginate($services);
            }

            return $this->responseData($services, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/service/create",
        summary: "Créer un service",
        description: "Ajoute un nouveau service.",
        tags: ['Service']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "libelle"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "INFO"),
                new OA\Property(property: "libelle", type: "string", example: "Informatique")
            ]
        )
    )]
    public function create(Request $request, ServiceRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $service = new Service();
            
            if (isset($data['code'])) $service->setCode($data['code']);
            if (isset($data['libelle'])) $service->setLibelle($data['libelle']);

            $this->updateAuditFields($service, true);
            $repository->save($service, true);

            return $this->responseData($service);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/service/{id}",
        summary: "Modifier un service",
        description: "Met à jour un service existant.",
        tags: ['Service']
    )]
    public function update(Request $request, Service $service, ServiceRepository $repository): Response
    {
        try {
            if (!$service) return $this->errorResponse(null, "Service non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['code'])) $service->setCode($data['code']);
            if (isset($data['libelle'])) $service->setLibelle($data['libelle']);

            $this->updateAuditFields($service);
            $repository->save($service, true);

            return $this->responseData($service);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/service/{id}",
        summary: "Supprimer un service",
        description: "Supprime un service.",
        tags: ['Service']
    )]
    public function delete(Service $service, ServiceRepository $repository): Response
    {
        try {
            if (!$service) return $this->errorResponse(null, "Service non trouvé", 404);
            $repository->remove($service, true);
            return $this->response(['message' => 'Service supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
