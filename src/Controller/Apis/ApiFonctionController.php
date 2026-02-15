<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Fonction;
use App\Repository\EntrepriseRepository;
use App\Repository\FonctionRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/fonction')]
#[OA\Tag(name: 'Fonction', description: 'Gestion des fonctions')]
class ApiFonctionController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/fonction/",
        summary: "Lister les fonctions",
        description: "Retourne la liste des fonctions.",
        tags: ['Fonction']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, FonctionRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $fonctions = $repository->findBy(['entreprise' => $this->getUser()->getEntreprise()], ['id' => 'DESC']);
            } else {
                $fonctions = $repository->findBy([], ['id' => 'DESC']);
            }

            if ($withPagination == "true") {
                $fonctions = $this->paginationService->paginate($fonctions);
            }

            return $this->responseData($fonctions, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fonction/create",
        summary: "Créer une fonction",
        description: "Ajoute une nouvelle fonction.",
        tags: ['Fonction']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["code", "libelle"],
            properties: [
                new OA\Property(property: "code", type: "string", example: "DEV"),
                new OA\Property(property: "libelle", type: "string", example: "Développeur"),
                new OA\Property(property: "entreprise_id", type: "integer", example: 1)
            ]
        )
    )]
    public function create(Request $request, FonctionRepository $repository, EntrepriseRepository $entrepriseRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $fonction = new Fonction();
            
            if (isset($data['code'])) $fonction->setCode($data['code']);
            if (isset($data['libelle'])) $fonction->setLibelle($data['libelle']);

            if (isset($data['entreprise_id'])) {
                $entreprise = $entrepriseRepository->find($data['entreprise_id']);
                if ($entreprise) $fonction->setEntreprise($entreprise);
            }

            $this->updateAuditFields($fonction, true);
            $repository->save($fonction, true);

            return $this->responseData($fonction);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/fonction/{id}",
        summary: "Modifier une fonction",
        description: "Met à jour une fonction existante.",
        tags: ['Fonction']
    )]
    public function update(Request $request, Fonction $fonction, FonctionRepository $repository, EntrepriseRepository $entrepriseRepository): Response
    {
        try {
            if (!$fonction) return $this->errorResponse(null, "Fonction non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['code'])) $fonction->setCode($data['code']);
            if (isset($data['libelle'])) $fonction->setLibelle($data['libelle']);

            if (isset($data['entreprise_id'])) {
                $entreprise = $entrepriseRepository->find($data['entreprise_id']);
                if ($entreprise) $fonction->setEntreprise($entreprise);
            }

            $this->updateAuditFields($fonction);
            $repository->save($fonction, true);

            return $this->responseData($fonction);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/fonction/{id}",
        summary: "Supprimer une fonction",
        description: "Supprime une fonction.",
        tags: ['Fonction']
    )]
    public function delete(Fonction $fonction, FonctionRepository $repository): Response
    {
        try {
            if (!$fonction) return $this->errorResponse(null, "Fonction non trouvée", 404);
            $repository->remove($fonction, true);
            return $this->response(['message' => 'Fonction supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
