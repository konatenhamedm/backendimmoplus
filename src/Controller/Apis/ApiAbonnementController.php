<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Abonnement;
use App\Repository\AbonnementRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ModuleAbonnementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/abonnement')]
#[OA\Tag(name: 'Abonnement', description: 'Gestion des abonnements')]
class ApiAbonnementController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/abonnement/",
        summary: "Lister les abonnements",
        description: "Retourne l'historique de tous les abonnements.",
    )]
    #[OA\Parameter(name: "entreprise_id", in: "query", description: "Filtrer par entreprise", schema: new OA\Schema(type: "integer"))]
    public function index(Request $request, AbonnementRepository $repository): Response
    {
        try {
            $entrepriseId = $request->query->get('entreprise_id');
            
            if ($entrepriseId) {
                $abonnements = $repository->findBy(['entreprise' => $entrepriseId], ['id' => 'DESC']);
            } else {
                $abonnements = $repository->findBy([], ['id' => 'DESC']);
            }

            return $this->responseData($abonnements, 'group_abonnement');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/abonnement/create",
        summary: "Créer un abonnement manuellement",
        description: "Crée manuellement une ligne d'abonnement (admin)."
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "entreprise_id", type: "integer"),
                new OA\Property(property: "module_abonnement_id", type: "integer"),
                new OA\Property(property: "type", type: "string", description: "ESSAI, RENOUVELLEMENT, etc"),
                new OA\Property(property: "etat", type: "string", description: "ACTIF, EXPIRE"),
                new OA\Property(property: "date_fin", type: "string", format: "date-time")
            ]
        )
    )]
    public function create(Request $request, EntityManagerInterface $em, EntrepriseRepository $entrepriseRepo, ModuleAbonnementRepository $moduleRepo): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['entreprise_id']) || !isset($data['etat'])) {
                return $this->errorResponse(null, "entreprise_id et etat sont obligatoires", 400);
            }

            $entreprise = $entrepriseRepo->find($data['entreprise_id']);
            if (!$entreprise) {
                return $this->errorResponse(null, "Entreprise introuvable", 404);
            }

            $abonnement = new Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setEtat($data['etat']);
            $abonnement->setType($data['type'] ?? 'RENOUVELLEMENT');

            if (isset($data['module_abonnement_id'])) {
                $module = $moduleRepo->find($data['module_abonnement_id']);
                if ($module) {
                    $abonnement->setModuleAbonnement($module);
                }
            }

            if (isset($data['date_fin'])) {
                $abonnement->setDateFin(new \DateTime($data['date_fin']));
            } else {
                // Par défaut, définir dans 30 jours
                $dateFin = new \DateTime();
                $dateFin->modify('+30 days');
                $abonnement->setDateFin($dateFin);
            }

            // Met potentiellement à jour la date de fin sur l'entreprise globale
            if ($abonnement->getEtat() === 'ACTIF') {
                $entreprise->setDateFinAbonnement(clone $abonnement->getDateFin());
                
                // Expirer les autres abonnements actifs
                $oldAbonnements = $em->getRepository(Abonnement::class)->findBy([
                    'entreprise' => $entreprise,
                    'etat' => 'ACTIF'
                ]);
                foreach ($oldAbonnements as $oldAb) {
                    // On ne modifie pas celui qu'on vient juste de créer s'il était déjà flush (mais ici il n'est pas encore persisté)
                    $oldAb->setEtat('EXPIRE');
                    $em->persist($oldAb);
                }
            }

            $em->persist($abonnement);
            $em->persist($entreprise);
            $em->flush();

            return $this->responseData($abonnement, 'group_abonnement', ['message' => 'Abonnement enregistré.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/abonnement/{id}",
        summary: "Afficher un abonnement",
        description: "Détails d'un abonnement."
    )]
    public function show(Abonnement $abonnement): Response
    {
        try {
            if (!$abonnement) return $this->errorResponse(null, "Abonnement non trouvé", 404);
            return $this->responseData($abonnement, 'group_abonnement');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/abonnement/{id}",
        summary: "Modifier l'état/date d'un abonnement",
        description: "Met à jour un abonnement (par ex. pour forcer la désactivation)."
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "etat", type: "string", description: "ACTIF, ANNULE, EXPIRE"),
                new OA\Property(property: "date_fin", type: "string", format: "date-time")
            ]
        )
    )]
    public function update(Request $request, Abonnement $abonnement, EntityManagerInterface $em): Response
    {
        try {
            if (!$abonnement) return $this->errorResponse(null, "Abonnement non trouvé", 404);

            $data = json_decode($request->getContent(), true);

            if (isset($data['etat'])) $abonnement->setEtat($data['etat']);
            if (isset($data['date_fin'])) $abonnement->setDateFin(new \DateTime($data['date_fin']));

            $em->persist($abonnement);
            
            // Re-sync Entreprise si activé
            if ($abonnement->getEntreprise() && $abonnement->getEtat() === 'ACTIF') {
                $entreprise = $abonnement->getEntreprise();
                $entreprise->setDateFinAbonnement(clone $abonnement->getDateFin());
                $em->persist($entreprise);
            }
            
            $em->flush();

            return $this->responseData($abonnement, 'group_abonnement', ['message' => 'Abonnement mis à jour.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/abonnement/{id}",
        summary: "Supprimer un abonnement",
        description: "Supprime une ligne d'abonnement."
    )]
    public function delete(Abonnement $abonnement, EntityManagerInterface $em): Response
    {
        try {
            if (!$abonnement) return $this->errorResponse(null, "Abonnement non trouvé", 404);
            $em->remove($abonnement);
            $em->flush();
            return $this->response(['message' => 'Abonnement supprimé.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => "Erreur : " . $exception->getMessage()]);
        }
    }
}
