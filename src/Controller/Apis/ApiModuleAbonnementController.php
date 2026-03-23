<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModuleAbonnement;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\PaysRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/module-abonnement')]
#[OA\Tag(name: 'ModuleAbonnement', description: 'Gestion des modules d\'abonnement')]
class ApiModuleAbonnementController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/module-abonnement/",
        summary: "Lister les modules d'abonnement",
        description: "Retourne la liste complète des modules d'abonnement disponibles.",
    )]
    public function index(ModuleAbonnementRepository $repository): Response
    {
        try {
            $modules = $repository->findAll();
            return $this->responseData($modules, 'group_abonnement');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/module-abonnement/create",
        summary: "Créer un module d'abonnement",
        description: "Permet de créer un nouveau module."
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "string"),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "montant", type: "string"),
                new OA\Property(property: "duree", type: "string", description: "Durée en jours"),
                new OA\Property(property: "etat", type: "boolean"),
                new OA\Property(property: "numero", type: "integer"),
                new OA\Property(property: "pays_id", type: "integer")
            ]
        )
    )]
    public function create(Request $request, EntityManagerInterface $em, PaysRepository $paysRepo): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['code']) || !isset($data['montant']) || !isset($data['duree'])) {
                return $this->errorResponse(null, "Données manquantes : code, montant, et duree sont obligatoires", 400);
            }

            $module = new ModuleAbonnement();
            $module->setCode($data['code']);
            $module->setDescription($data['description'] ?? '');
            $module->setMontant($data['montant']);
            $module->setDuree($data['duree']);
            $module->setEtat($data['etat'] ?? true);
            
            if (isset($data['numero'])) {
                $module->setNumero($data['numero']);
            }

            if (isset($data['pays_id'])) {
                $pays = $paysRepo->find($data['pays_id']);
                if ($pays) {
                    $module->setPays($pays);
                }
            }

            $em->persist($module);
            $em->flush();

            return $this->responseData($module, 'group_abonnement', ['message' => 'Module créé avec succès.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/module-abonnement/{id}",
        summary: "Afficher un module",
        description: "Retourne les détails d'un module d'abonnement."
    )]
    public function show(ModuleAbonnement $module): Response
    {
        try {
            if (!$module) return $this->errorResponse(null, "Module non trouvé", 404);
            return $this->responseData($module, 'group_abonnement');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/module-abonnement/{id}",
        summary: "Modifier un module",
        description: "Met à jour un module existant."
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "string"),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "montant", type: "string"),
                new OA\Property(property: "duree", type: "string"),
                new OA\Property(property: "etat", type: "boolean"),
                new OA\Property(property: "numero", type: "integer"),
                new OA\Property(property: "pays_id", type: "integer")
            ]
        )
    )]
    public function update(Request $request, ModuleAbonnement $module, EntityManagerInterface $em, PaysRepository $paysRepo): Response
    {
        try {
            if (!$module) return $this->errorResponse(null, "Module non trouvé", 404);

            $data = json_decode($request->getContent(), true);

            if (isset($data['code'])) $module->setCode($data['code']);
            if (isset($data['description'])) $module->setDescription($data['description']);
            if (isset($data['montant'])) $module->setMontant($data['montant']);
            if (isset($data['duree'])) $module->setDuree($data['duree']);
            if (isset($data['etat'])) $module->setEtat($data['etat']);
            if (isset($data['numero'])) $module->setNumero($data['numero']);

            if (isset($data['pays_id'])) {
                $pays = $paysRepo->find($data['pays_id']);
                if ($pays) {
                    $module->setPays($pays);
                } else {
                    $module->setPays(null);
                }
            }

            $em->persist($module);
            $em->flush();

            return $this->responseData($module, 'group_abonnement', ['message' => 'Module mis à jour.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/module-abonnement/{id}",
        summary: "Supprimer un module",
        description: "Supprime un module d'abonnement."
    )]
    public function delete(ModuleAbonnement $module, EntityManagerInterface $em): Response
    {
        try {
            if (!$module) return $this->errorResponse(null, "Module non trouvé", 404);
            $em->remove($module);
            $em->flush();
            return $this->response(['message' => 'Module supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => "Erreur lors de la suppression : " . $exception->getMessage()]);
        }
    }
}
