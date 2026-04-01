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
    public function index(Request $request, ModuleAbonnementRepository $repository): Response
    {
        try {
            $withPagination = $request->query->get('with_pagination', "false");
            $qb = $repository->createQueryBuilder('m')->orderBy('m.id', 'DESC');

            if ($withPagination === "true") {
                $modules = $this->paginationService->paginate($qb);
                return $this->responseData($modules, 'group_abonnement', [], true);
            }

            $modules = $qb->getQuery()->getResult();
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
            $module->setMaxBiens((int)($data['maxBiens'] ?? 0));
            $module->setMaxAgences((int)($data['maxAgences'] ?? 1));
            $module->setMaxEmployes((int)($data['maxEmployes'] ?? 10));
            $module->setMaxLocatairesMobileApp((int)($data['maxLocatairesMobileApp'] ?? 40));
            $module->setMaxResidences((int)($data['maxResidences'] ?? 0));
            $module->setHasFacturationAuto((bool)($data['hasFacturationAuto'] ?? false));
            $module->setHasRelancesAuto((bool)($data['hasRelancesAuto'] ?? false));
            $module->setHasMobileMoney((bool)($data['hasMobileMoney'] ?? false));
            $module->setHasRapportsAvances((bool)($data['hasRapportsAvances'] ?? false));
            $module->setHasGestionDepenses((bool)($data['hasGestionDepenses'] ?? false));
            $module->setHasMultiAgences((bool)($data['hasMultiAgences'] ?? false));
            $module->setHasApiIntegrations((bool)($data['hasApiIntegrations'] ?? false));
            $module->setSignatureElectronique($data['signatureElectronique'] ?? 'NONE');

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
            if (isset($data['maxBiens'])) $module->setMaxBiens((int)$data['maxBiens']);
            if (isset($data['maxAgences'])) $module->setMaxAgences((int)$data['maxAgences']);
            if (isset($data['maxEmployes'])) $module->setMaxEmployes((int)$data['maxEmployes']);
            if (isset($data['maxLocatairesMobileApp'])) $module->setMaxLocatairesMobileApp((int)$data['maxLocatairesMobileApp']);
            if (isset($data['maxResidences'])) $module->setMaxResidences((int)$data['maxResidences']);
            if (isset($data['hasFacturationAuto'])) $module->setHasFacturationAuto((bool)$data['hasFacturationAuto']);
            if (isset($data['hasRelancesAuto'])) $module->setHasRelancesAuto((bool)$data['hasRelancesAuto']);
            if (isset($data['hasMobileMoney'])) $module->setHasMobileMoney((bool)$data['hasMobileMoney']);
            if (isset($data['hasRapportsAvances'])) $module->setHasRapportsAvances((bool)$data['hasRapportsAvances']);
            if (isset($data['hasGestionDepenses'])) $module->setHasGestionDepenses((bool)$data['hasGestionDepenses']);
            if (isset($data['hasMultiAgences'])) $module->setHasMultiAgences((bool)$data['hasMultiAgences']);
            if (isset($data['hasApiIntegrations'])) $module->setHasApiIntegrations((bool)$data['hasApiIntegrations']);
            if (isset($data['signatureElectronique'])) $module->setSignatureElectronique($data['signatureElectronique']);

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
