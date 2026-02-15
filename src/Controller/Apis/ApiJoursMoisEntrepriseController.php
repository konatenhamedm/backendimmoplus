<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\JoursMoisEntreprise;
use App\Repository\EntrepriseRepository;
use App\Repository\JoursMoisEntrepriseRepository;
use App\Repository\JoursMoisRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/jours-mois-entreprise')]
#[OA\Tag(name: 'JoursMoisEntreprise', description: 'Gestion des jours actifs par entreprise')]
class ApiJoursMoisEntrepriseController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/jours-mois-entreprise/",
        summary: "Lister les configurations jours/mois",
        description: "Retourne la liste des configurations jours/mois, filtrable par entreprise.",
        tags: ['JoursMoisEntreprise']
    )]
    #[OA\Parameter(name: "entreprise_id", in: "query", description: "Filtrer par Entreprise ID", schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, JoursMoisEntrepriseRepository $repository, EntrepriseRepository $entrepriseRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $entrepriseId = $request->query->get('entreprise_id');
            
            if ($entrepriseId) {
                $entreprise = $entrepriseRepository->find($entrepriseId);
                if (!$entreprise) return $this->errorResponse(null, "Entreprise non trouvée", 404);
                $configs = $repository->findBy(['entreprise' => $entreprise]);
            } else {
                if ($this->getUser() && $this->getUser()->getEntreprise()) {
                    $configs = $repository->findBy(['entreprise' => $this->getUser()->getEntreprise()]);
                } else {
                    $configs = $repository->findAll();
                }
            }

            if ($withPagination == "true") {
                $configs = $this->paginationService->paginate($configs);
            }

            return $this->responseData($configs, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/jours-mois-entreprise/create",
        summary: "Créer une config jours/mois",
        description: "Ajoute une nouvelle configuration jours/mois.",
        tags: ['JoursMoisEntreprise']
    )]
    public function create(Request $request, JoursMoisEntrepriseRepository $repository, EntrepriseRepository $entrepriseRepository, JoursMoisRepository $joursMoisRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $config = new JoursMoisEntreprise();
            
            if (isset($data['active'])) $config->setActive((bool)$data['active']);
            if (isset($data['dateDebut'])) $config->setDateDebut(new \DateTime($data['dateDebut']));
            if (isset($data['dateFin'])) $config->setDateFin(new \DateTime($data['dateFin']));

            if (isset($data['entreprise_id'])) {
                $entreprise = $entrepriseRepository->find($data['entreprise_id']);
                if ($entreprise) $config->setEntreprise($entreprise);
            } elseif ($this->getUser() && $this->getUser()->getEntreprise()) {
                $config->setEntreprise($this->getUser()->getEntreprise());
            }

            if (isset($data['jours_mois_id'])) {
                $joursMois = $joursMoisRepository->find($data['jours_mois_id']);
                if ($joursMois) $config->setJoursMois($joursMois);
            }

            $this->updateAuditFields($config, true);
            $repository->save($config, true);

            return $this->responseData($config);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/jours-mois-entreprise/{id}",
        summary: "Modifier une config",
        description: "Met à jour une configuration existante.",
        tags: ['JoursMoisEntreprise']
    )]
    public function update(Request $request, JoursMoisEntreprise $config, JoursMoisEntrepriseRepository $repository): Response
    {
        try {
            if (!$config) return $this->errorResponse(null, "Configuration non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['active'])) $config->setActive((bool)$data['active']);
            if (isset($data['dateDebut'])) $config->setDateDebut(new \DateTime($data['dateDebut']));
            if (isset($data['dateFin'])) $config->setDateFin(new \DateTime($data['dateFin']));

            // On évite de changer l'entreprise ou le jour une fois créé, sauf si explicite
            
            $this->updateAuditFields($config);
            $repository->save($config, true);

            return $this->responseData($config);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/jours-mois-entreprise/{id}",
        summary: "Supprimer une config",
        description: "Supprime une configuration.",
        tags: ['JoursMoisEntreprise']
    )]
    public function delete(JoursMoisEntreprise $config, JoursMoisEntrepriseRepository $repository): Response
    {
        try {
            if (!$config) return $this->errorResponse(null, "Configuration non trouvée", 404);
            $repository->remove($config, true);
            return $this->response(['message' => 'Configuration supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
