<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Module;
use App\Repository\ModuleRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/module')]
#[OA\Tag(name: 'Module', description: 'Gestion des modules applicatifs')]
class ApiModuleController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/module/",
        summary: "Lister les modules",
        description: "Retourne la liste des modules.",
        tags: ['Module']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, ModuleRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $modules = $repository->findBy([], ['ordre' => 'ASC']);

            if ($withPagination == "true") {
                $modules = $this->paginationService->paginate($modules);
            }

            return $this->responseData($modules, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/module/create",
        summary: "Créer un module",
        description: "Ajoute un nouveau module.",
        tags: ['Module']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["titre", "ordre"],
            properties: [
                new OA\Property(property: "titre", type: "string", example: "Gestion Commerciale"),
                new OA\Property(property: "ordre", type: "integer", example: 1)
            ]
        )
    )]
    public function create(Request $request, ModuleRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $module = new Module();
            
            if (isset($data['titre'])) $module->setTitre($data['titre']);
            if (isset($data['ordre'])) $module->setOrdre((int)$data['ordre']);

            $repository->save($module, true);

            return $this->responseData($module);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/module/{id}",
        summary: "Modifier un module",
        description: "Met à jour un module existant.",
        tags: ['Module']
    )]
    public function update(Request $request, Module $module, ModuleRepository $repository): Response
    {
        try {
            if (!$module) return $this->errorResponse(null, "Module non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['titre'])) $module->setTitre($data['titre']);
            if (isset($data['ordre'])) $module->setOrdre((int)$data['ordre']);

            $repository->save($module, true);

            return $this->responseData($module);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/module/{id}",
        summary: "Supprimer un module",
        description: "Supprime un module.",
        tags: ['Module']
    )]
    public function delete(Module $module, ModuleRepository $repository): Response
    {
        try {
            if (!$module) return $this->errorResponse(null, "Module non trouvé", 404);
            $repository->remove($module, true);
            return $this->response(['message' => 'Module supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
