<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Motif;
use App\Repository\MotifRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/motif')]
#[OA\Tag(name: 'Motif', description: 'Gestion des motifs')]
class ApiMotifController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/motif/",
        summary: "Lister les motifs",
        description: "Retourne la liste des motifs.",
        tags: ['Motif']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, MotifRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $motifs = $repository->findAll();

            if ($withPagination == "true") {
                $motifs = $this->paginationService->paginate($motifs);
            }

            return $this->responseData($motifs, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/motif/create",
        summary: "Créer un motif",
        description: "Ajoute un nouveau motif.",
        tags: ['Motif']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libMotif"],
            properties: [
                new OA\Property(property: "libMotif", type: "string", example: "Demande de congé")
            ]
        )
    )]
    public function create(Request $request, MotifRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $motif = new Motif();
            
            if (isset($data['libMotif'])) $motif->setLibMotif($data['libMotif']);

            $repository->save($motif, true);

            return $this->responseData($motif);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/motif/{id}",
        summary: "Modifier un motif",
        description: "Met à jour un motif existant.",
        tags: ['Motif']
    )]
    public function update(Request $request, Motif $motif, MotifRepository $repository): Response
    {
        try {
            if (!$motif) return $this->errorResponse(null, "Motif non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libMotif'])) $motif->setLibMotif($data['libMotif']);

            $repository->save($motif, true);

            return $this->responseData($motif);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/motif/{id}",
        summary: "Supprimer un motif",
        description: "Supprime un motif.",
        tags: ['Motif']
    )]
    public function delete(Motif $motif, MotifRepository $repository): Response
    {
        try {
            if (!$motif) return $this->errorResponse(null, "Motif non trouvé", 404);
            $repository->remove($motif, true);
            return $this->response(['message' => 'Motif supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
