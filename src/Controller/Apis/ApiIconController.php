<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Icon;
use App\Repository\IconRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/icon')]
#[OA\Tag(name: 'Icon', description: 'Gestion des icônes')]
class ApiIconController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/icon/",
        summary: "Lister les icônes",
        description: "Retourne la liste des icônes disponibles.",
        tags: ['Icon']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des icônes récupérée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 101),
                            new OA\Property(property: "code", type: "string", example: "LayoutDashboard"),
                            new OA\Property(property: "libelle", type: "string", example: "Dashboard Icon"),
                            new OA\Property(property: "image", type: "string", nullable: true)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, IconRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $icons = $repository->findAll();

            if ($withPagination == "true") {
                $icons = $this->paginationService->paginate($icons);
            }

            return $this->responseData($icons, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/icon/create",
        summary: "Créer une icône",
        description: "Ajoute une nouvelle icône.",
        tags: ['Icon']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "libelle"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "Home"),
                new OA\Property(property: "libelle", type: "string", example: "Icône Accueil"),
                new OA\Property(property: "image", type: "string", description: "URL ou chemin de l'image (optionnel)")
            ]
        )
    )]
    public function create(Request $request, IconRepository $repository, ValidatorInterface $validator): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $icon = new Icon();
            
            if (isset($data['code'])) $icon->setCode($data['code']);
            if (isset($data['libelle'])) $icon->setLibelle($data['libelle']);
            if (isset($data['image'])) $icon->setImage($data['image']);

            $errors = $validator->validate($icon);
            if (count($errors) > 0) {
                return $this->errorResponse(null, (string) $errors, 400);
            }

            $this->updateAuditFields($icon, true);
            $repository->save($icon, true);

            return $this->responseData($icon);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/icon/{id}",
        summary: "Modifier une icône",
        description: "Met à jour une icône existante.",
        tags: ['Icon']
    )]
    public function update(Request $request, Icon $icon, IconRepository $repository, ValidatorInterface $validator): Response
    {
        try {
            if (!$icon) return $this->errorResponse(null, "Icone non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['code'])) $icon->setCode($data['code']);
            if (isset($data['libelle'])) $icon->setLibelle($data['libelle']);
            if (isset($data['image'])) $icon->setImage($data['image']);

            $errors = $validator->validate($icon);
            if (count($errors) > 0) {
                return $this->errorResponse(null, (string) $errors, 400);
            }

            $this->updateAuditFields($icon);
            $repository->save($icon, true);

            return $this->responseData($icon);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/icon/{id}",
        summary: "Supprimer une icône",
        description: "Supprime une icône.",
        tags: ['Icon']
    )]
    public function delete(Icon $icon, IconRepository $repository): Response
    {
        try {
            if (!$icon) return $this->errorResponse(null, "Icone non trouvée", 404);
            $repository->remove($icon, true);
            return $this->response(['message' => 'Icone supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
