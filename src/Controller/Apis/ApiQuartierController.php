<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Quartier;
use App\Repository\EntrepriseRepository;
use App\Repository\QuartierRepository;
use App\Repository\VilleRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/quartier')]
#[OA\Tag(name: 'Quartier', description: 'Gestion des quartiers')]
class ApiQuartierController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    /**
     * Liste les quartiers. Peut être filtré par ville.
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/quartier/",
        summary: "Lister les quartiers",
        description: "Retourne la liste des quartiers, filtrable par ville et filtrée par l'entreprise de l'utilisateur.",
        tags: ['Quartier']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, QuartierRepository $repository, VilleRepository $villeRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
        
            $entreprise = ($this->getUser() && $this->getUser()->getEntreprise()) ? $this->getUser()->getEntreprise() : null;

            $quartiers = $repository->findBy(['entreprise' => $entreprise]);

            if ($withPagination == "true") {
                $quartiers = $this->paginationService->paginate($quartiers);
            }
            
            return $this->responseData($quartiers, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/quartier/create",
        summary: "Créer un quartier",
        description: "Ajoute un nouveau quartier. L'entreprise est automatiquement récupérée depuis l'utilisateur connecté.",
        tags: ['Quartier']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libQuartier", "ville_id"],
            properties: [
                new OA\Property(property: "libQuartier", type: "string", example: "Cocody"),
                new OA\Property(property: "ville_id", type: "integer", example: 1)
            ]
        )
    )]
    public function create(Request $request, QuartierRepository $repository, VilleRepository $villeRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $quartier = new Quartier();
            
            if (isset($data['libQuartier'])) $quartier->setLibQuartier($data['libQuartier']);
            
            if (isset($data['ville_id'])) {
                $ville = $villeRepository->find($data['ville_id']);
                if (!$ville) return $this->errorResponse(null, "Ville non trouvée", 404);
                $quartier->setVille($ville);
            }

            // Récupérer l'entreprise de l'utilisateur connecté
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $quartier->setEntreprise($this->getUser()->getEntreprise());
            }

            $repository->save($quartier, true);

            return $this->responseData($quartier, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/quartier/{id}",
        summary: "Modifier un quartier",
        description: "Met à jour un quartier existant.",
        tags: ['Quartier']
    )]
    public function update(Request $request, Quartier $quartier, QuartierRepository $repository, VilleRepository $villeRepository, EntrepriseRepository $entrepriseRepository): Response
    {
        try {
            if (!$quartier) return $this->errorResponse(null, "Quartier non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libQuartier'])) $quartier->setLibQuartier($data['libQuartier']);

            if (isset($data['ville_id'])) {
                $ville = $villeRepository->find($data['ville_id']);
                if (!$ville) return $this->errorResponse(null, "Ville non trouvée", 404);
                $quartier->setVille($ville);
            }

            if (isset($data['entreprise_id'])) {
                $entreprise = $entrepriseRepository->find($data['entreprise_id']);
                if ($entreprise) $quartier->setEntreprise($entreprise);
            }

            $repository->save($quartier, true);

            return $this->responseData($quartier, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/quartier/{id}",
        summary: "Supprimer un quartier",
        description: "Supprime un quartier.",
        tags: ['Quartier']
    )]
    public function delete(Quartier $quartier, QuartierRepository $repository): Response
    {
        try {
            if (!$quartier) return $this->errorResponse(null, "Quartier non trouvé", 404);
            $repository->remove($quartier, true);
            return $this->response(['message' => 'Quartier supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
