<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Ville;
use App\Repository\PaysRepository;
use App\Repository\VilleRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/ville')]
#[OA\Tag(name: 'Ville', description: 'Gestion des villes')]
class ApiVilleController extends ApiInterface
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
        path: "/api/ville/",
        summary: "Lister les villes",
        description: "Retourne la liste des villes du pays de l'entreprise de l'utilisateur connecté. Le paramètre pays_id permet de filtrer par un autre pays.",
        tags: ['Ville']
    )]
    #[OA\Parameter(name: "pays_id", in: "query", description: "Filtrer par Pays ID (optionnel, sinon utilise le pays de l'entreprise)", schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, VilleRepository $repository, PaysRepository $paysRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
           
                $villes = $repository->findBy(['pays' => $this->getUser()->getEntreprise()->getPays()->getId()]);
           

            if ($withPagination == "true") {
                $villes = $this->paginationService->paginate($villes);
            }
            
            return $this->responseData($villes, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/ville/create",
        summary: "Créer une ville",
        description: "Ajoute une nouvelle ville pour un pays donné.",
        tags: ['Ville']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["libVille", "abrege_ville", "pays_id"],
            properties: [
                new OA\Property(property: "libVille", type: "string", example: "Abidjan"),
                new OA\Property(property: "abrege_ville", type: "string", example: "ABJ"),
                new OA\Property(property: "pays_id", type: "integer", example: 1)
            ]
        )
    )]
    public function create(Request $request, VilleRepository $repository, PaysRepository $paysRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $ville = new Ville();
            
            if (isset($data['libVille'])) $ville->setLibVille($data['libVille']);
            if (isset($data['abrege_ville'])) $ville->setAbregeVille($data['abrege_ville']);
            
            if (isset($data['pays_id'])) {
                $pays = $paysRepository->find($data['pays_id']);
                if (!$pays) return $this->errorResponse(null, "Pays non trouvé", 404);
                $ville->setPays($pays);
            }

            $repository->save($ville, true);

            return $this->responseData($ville, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/ville/{id}",
        summary: "Modifier une ville",
        description: "Met à jour une ville existante.",
        tags: ['Ville']
    )]
    public function update(Request $request, Ville $ville, VilleRepository $repository, PaysRepository $paysRepository): Response
    {
        try {
            if (!$ville) return $this->errorResponse(null, "Ville non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libVille'])) $ville->setLibVille($data['libVille']);
            if (isset($data['abrege_ville'])) $ville->setAbregeVille($data['abrege_ville']);

            if (isset($data['pays_id'])) {
                $pays = $paysRepository->find($data['pays_id']);
                if (!$pays) return $this->errorResponse(null, "Pays non trouvé", 404);
                $ville->setPays($pays);
            }

            $repository->save($ville, true);

            return $this->responseData($ville, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/ville/{id}",
        summary: "Supprimer une ville",
        description: "Supprime une ville.",
        tags: ['Ville']
    )]
    public function delete(Ville $ville, VilleRepository $repository): Response
    {
        try {
            if (!$ville) return $this->errorResponse(null, "Ville non trouvée", 404);
            $repository->remove($ville, true);
            return $this->response(['message' => 'Ville supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
