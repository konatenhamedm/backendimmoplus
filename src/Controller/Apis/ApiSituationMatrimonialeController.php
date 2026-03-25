<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\SituationMatrimoniale;
use App\Repository\SituationMatrimonialeRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/situation-matrimoniale')]
#[OA\Tag(name: 'SituationMatrimoniale', description: 'Gestion des situations matrimoniales')]
class ApiSituationMatrimonialeController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/situation-matrimoniale/",
        summary: "Lister les situations matrimoniales",
        description: "Retourne la liste des situations matrimoniales.",
        tags: ['SituationMatrimoniale']
    )]
    public function index(Request $request, SituationMatrimonialeRepository $repository): Response
    {
        try {
            $situations = $repository->findAll();
            return $this->responseData($situations, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/situation-matrimoniale/create",
        summary: "Créer une situation matrimoniale",
        description: "Ajoute une nouvelle situation matrimoniale.",
        tags: ['SituationMatrimoniale']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libSituation"],
            properties: [
                new OA\Property(property: "libSituation", type: "string", example: "Célibataire")
            ]
        )
    )]
    public function create(Request $request, SituationMatrimonialeRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $situation = new SituationMatrimoniale();
            
            if (isset($data['libSituation'])) $situation->setLibSituation($data['libSituation']);

            $repository->save($situation, true);

            return $this->responseData($situation, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/situation-matrimoniale/{id}",
        summary: "Modifier une situation matrimoniale",
        description: "Met à jour une situation matrimoniale existante.",
        tags: ['SituationMatrimoniale']
    )]
    public function update(Request $request, SituationMatrimoniale $situation, SituationMatrimonialeRepository $repository): Response
    {
        try {
            if (!$situation) return $this->errorResponse(null, "Situation non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libSituation'])) $situation->setLibSituation($data['libSituation']);

            $repository->save($situation, true);

            return $this->responseData($situation, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/situation-matrimoniale/{id}",
        summary: "Supprimer une situation matrimoniale",
        description: "Supprime une situation matrimoniale.",
        tags: ['SituationMatrimoniale']
    )]
    public function delete(SituationMatrimoniale $situation, SituationMatrimonialeRepository $repository): Response
    {
        try {
            if (!$situation) return $this->errorResponse(null, "Situation non trouvée", 404);
            $repository->remove($situation, true);
            return $this->response(['message' => 'Situation supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
