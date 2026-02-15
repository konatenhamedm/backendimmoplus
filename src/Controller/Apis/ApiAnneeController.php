<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Annee;
use App\Repository\AnneeRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/annee')]
#[OA\Tag(name: 'Annee', description: 'Gestion des années')]
class ApiAnneeController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/annee/",
        summary: "Lister les années",
        description: "Retourne la liste des années.",
        tags: ['Annee']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, AnneeRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $annees = $repository->findAll();

            if ($withPagination == "true") {
                $annees = $this->paginationService->paginate($annees);
            }

            return $this->responseData($annees, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/annee/create",
        summary: "Créer une année",
        description: "Ajoute une nouvelle année.",
        tags: ['Annee']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libelle", "date_debut", "date_fin"],
            properties: [
                new OA\Property(property: "libelle", type: "string", example: "2023"),
                new OA\Property(property: "date_debut", type: "string", format: "date", example: "2023-01-01"),
                new OA\Property(property: "date_fin", type: "string", format: "date", example: "2023-12-31"),
                new OA\Property(property: "etat", type: "integer", example: 1),
                new OA\Property(property: "autre_info", type: "string", example: "RAS")
            ]
        )
    )]
    public function create(Request $request, AnneeRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $annee = new Annee();
            
            if (isset($data['libelle'])) $annee->setLibelle($data['libelle']);
            if (isset($data['date_debut'])) $annee->setDateDebut(new \DateTime($data['date_debut']));
            if (isset($data['date_fin'])) $annee->setDateFin(new \DateTime($data['date_fin']));
            if (isset($data['autre_info'])) $annee->setAutreInfo($data['autre_info']);
            $this->updateAuditFields($annee, true);
            $repository->save($annee, true);

            return $this->responseData($annee);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/annee/{id}",
        summary: "Modifier une année",
        description: "Met à jour une année existante.",
        tags: ['Annee']
    )]
    public function update(Request $request, Annee $annee, AnneeRepository $repository): Response
    {
        try {
            if (!$annee) return $this->errorResponse(null, "Annee non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libelle'])) $annee->setLibelle($data['libelle']);
            if (isset($data['date_debut'])) $annee->setDateDebut(new \DateTime($data['date_debut']));
            if (isset($data['date_fin'])) $annee->setDateFin(new \DateTime($data['date_fin']));
            if (isset($data['autre_info'])) $annee->setAutreInfo($data['autre_info']);
            $this->updateAuditFields($annee);
            $repository->save($annee, true);

            return $this->responseData($annee);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/annee/{id}",
        summary: "Supprimer une année",
        description: "Supprime une année.",
        tags: ['Annee']
    )]
    public function delete(Annee $annee, AnneeRepository $repository): Response
    {
        try {
            if (!$annee) return $this->errorResponse(null, "Annee non trouvée", 404);
            $repository->remove($annee, true);
            return $this->response(['message' => 'Annee supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
