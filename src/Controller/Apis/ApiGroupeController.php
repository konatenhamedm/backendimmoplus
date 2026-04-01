<?php

namespace App\Controller\Apis;

use App\Entity\Groupe;
use App\Repository\GroupeRepository;
use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModuleGroupePermition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/groupe')]
#[OA\Tag(name: 'Groupe', description: 'Gestion des groupes d\'utilisateurs')]
class ApiGroupeController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/groupe/",
        summary: "Lister tous les groupes",
        description: "Retourne la liste paginée de tous les groupes d'utilisateurs.",
        tags: ['Groupe']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des groupes récupérée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(type: "object")
                ),
                new OA\Property(property: "pagination", type: "object")
            ]
        )
    )]
    public function index(Request $request, GroupeRepository $groupeRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            $user = $this->getUser();
            $userGroupCode = ($user && $user->getGroupe()) ? $user->getGroupe()->getCode() : null;

            if ($userGroupCode === 'SADM') {
                $groupes = $groupeRepository->findAll();
            } else {
                // Pour les non-SADM, on cache le groupe SADM
                $groupes = $groupeRepository->createQueryBuilder('g')
                    ->where('g.code != :code')
                    ->setParameter('code', 'SADM')
                    ->getQuery()
                    ->getResult();
            }

            if ($withPagination == "true") {
                $groupes = $this->paginationService->paginate($groupes);
            }

            // Using 'group1' context, ensure Entity has #[Groups(['group1'])] on properties
            $response = $this->responseData($groupes, 'group1', [], $withPagination == "true" ? true : false);
            return $this->filterPermissionsByEntreprise($response);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des groupes");
            return $this->response([]);
        }
    }

    private function filterPermissionsByEntreprise(Response $response): Response
    {
        $content = json_decode($response->getContent(), true);
        $userEntrepriseId = ($this->getUser() && method_exists($this->getUser(), 'getEntreprise') && $this->getUser()->getEntreprise()) ? $this->getUser()->getEntreprise()->getId() : null;

        if (isset($content['data'])) {
            if (isset($content['data']['id']) && isset($content['data']['moduleGroupePermitions'])) {
                // Un seul objet (Show)
                $content['data']['moduleGroupePermitions'] = array_values(array_filter($content['data']['moduleGroupePermitions'], function ($perm) use ($userEntrepriseId) {
                    return ($perm['entreprise']['id'] ?? null) === $userEntrepriseId;
                }));
            } elseif (is_array($content['data'])) {
                // Liste d'objets (Index)
                foreach ($content['data'] as &$groupe) {
                    if (isset($groupe['moduleGroupePermitions'])) {
                        $groupe['moduleGroupePermitions'] = array_values(array_filter($groupe['moduleGroupePermitions'], function ($perm) use ($userEntrepriseId) {
                            return ($perm['entreprise']['id'] ?? null) === $userEntrepriseId;
                        }));
                    }
                }
            }
        }
        $response->setContent(json_encode($content));
        return $response;
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/groupe/create",
        summary: "Créer un nouveau groupe",
        description: "Crée un groupe d'utilisateurs avec ses permissions de modules.",
        tags: ['Groupe']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["name", "code"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Gestionnaires Ventes"),
                new OA\Property(property: "description", type: "string", example: "Groupe pour les gestionnaires des ventes"),
                new OA\Property(property: "code", type: "string", example: "GRP_VENTES"),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "module_id", type: "integer", example: 1),
                            new OA\Property(property: "permition_id", type: "integer", example: 2),
                            new OA\Property(property: "groupe_module_id", type: "integer", example: 3),
                            new OA\Property(property: "ordre", type: "integer", example: 1),
                            new OA\Property(property: "ordre_groupe", type: "integer", example: 1),
                            new OA\Property(property: "menu_principal", type: "boolean", example: true)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Groupe créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Gestionnaires Ventes"),
                        new OA\Property(property: "code", type: "string", example: "GRP_VENTES"),
                        new OA\Property(property: "description", type: "string", example: "Groupe pour les gestionnaires des ventes")
                    ]
                )
            ]
        )
    )]
    public function create(
        Request $request, 
        GroupeRepository $groupeRepository,
        \App\Repository\ModuleRepository $moduleRepository,
        \App\Repository\PermitionRepository $permitionRepository,
        \App\Repository\GroupeModuleRepository $groupeModuleRepository
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $groupe = new Groupe();
            
            if (empty($data['name']) || empty($data['code'])) {
                return $this->errorResponse(null, "Le nom et le code sont obligatoires");
            }

            // Vérification unicité code
            if ($groupeRepository->findOneBy(['code' => $data['code']])) {
                 return $this->errorResponse(null, "Ce code de groupe existe déjà");
            }

            $groupe->setName($data['name']);
            $groupe->setCode($data['code']);
            
            if (isset($data['description'])) {
                $groupe->setDescription($data['description']);
            }
            
          

            // Handle permissions array
            if (isset($data['lignes']) && is_array($data['lignes'])) {
                foreach ($data['lignes'] as $permData) {
                    $permission = new ModuleGroupePermition();
                    
                    if (isset($permData['module_id'])) {
                        $module = $moduleRepository->find($permData['module_id']);
                        if ($module) $permission->setModule($module);
                    }
                    
                    if (isset($permData['permition_id'])) {
                        $permition = $permitionRepository->find($permData['permition_id']);
                        if ($permition) $permission->setPermition($permition);
                    }
                    
                    if (isset($permData['groupe_module_id'])) {
                        $groupeModule = $groupeModuleRepository->find($permData['groupe_module_id']);
                        if ($groupeModule) $permission->setGroupeModule($groupeModule);
                    }
                    
                    if (isset($permData['ordre'])) $permission->setOrdre($permData['ordre']);
                    if (isset($permData['ordre_groupe'])) $permission->setOrdreGroupe($permData['ordre_groupe']);
                    if (isset($permData['menu_principal'])) $permission->setMenuPrincipal($permData['menu_principal']);
                    
                    if ($this->getUser() && method_exists($this->getUser(), 'getEntreprise')) {
                        $permission->setEntreprise($this->getUser()->getEntreprise());
                    }

                    $this->updateAuditFields($permission, true);
                    $groupe->addModuleGroupePermition($permission);
                }
            }

            $this->updateAuditFields($groupe, true);
            $groupeRepository->add($groupe, true);

            $response = $this->responseData($groupe, 'group1', ['Content-Type' => 'application/json']);
            return $this->filterPermissionsByEntreprise($response);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            return $this->response([]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/groupe/{id}",
        summary: "Récupérer un groupe",
        tags: ['Groupe']
    )]
    public function show(Groupe $groupe): Response
    {
        try {
            if (!$groupe) {
                return $this->errorResponse(null, "Groupe non trouvé", 404);
            }
            $response = $this->responseData($groupe, 'group1');
            return $this->filterPermissionsByEntreprise($response);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response([]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/groupe/{id}",
        summary: "Mettre à jour un groupe",
        description: "Met à jour un groupe et ses permissions. Pour les lignes: si 'id' est fourni, la ligne existante sera mise à jour, sinon une nouvelle ligne sera créée.",
        tags: ['Groupe']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "name", type: "string", example: "Gestionnaires Ventes"),
                new OA\Property(property: "description", type: "string", example: "Groupe pour les gestionnaires des ventes"),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", nullable: true, example: 5, description: "ID de la ligne existante (null pour créer une nouvelle ligne)"),
                            new OA\Property(property: "module_id", type: "integer", example: 1),
                            new OA\Property(property: "permition_id", type: "integer", example: 2),
                            new OA\Property(property: "groupe_module_id", type: "integer", example: 3),
                            new OA\Property(property: "ordre", type: "integer", example: 1),
                            new OA\Property(property: "ordre_groupe", type: "integer", example: 1),
                            new OA\Property(property: "menu_principal", type: "boolean", example: true)
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Groupe mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Gestionnaires Ventes"),
                        new OA\Property(property: "code", type: "string", example: "GRP_VENTES"),
                        new OA\Property(property: "description", type: "string", example: "Groupe pour les gestionnaires des ventes")
                    ]
                )
            ]
        )
    )]
    public function update(
        Request $request, 
        Groupe $groupe, 
        GroupeRepository $groupeRepository,
        \App\Repository\ModuleRepository $moduleRepository,
        \App\Repository\PermitionRepository $permitionRepository,
        \App\Repository\GroupeModuleRepository $groupeModuleRepository,
        \App\Repository\ModuleGroupePermitionRepository $permissionRepository
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$groupe) {
                return $this->errorResponse(null, "Groupe non trouvé", 404);
            }

            if (isset($data['name'])) {
                $groupe->setName($data['name']);
            }
            
            if (isset($data['description'])) {
                $groupe->setDescription($data['description']);
            }
            
         

            // Handle permissions update
            if (isset($data['lignes']) && is_array($data['lignes'])) {

                // ── 1. Supprimer les lignes qui ont été retirées côté frontend ──────────
                // Collecter les IDs des lignes qui arrivent dans la payload
                $incomingIds = array_filter(
                    array_map(fn($l) => $l['id'] ?? null, $data['lignes']),
                    fn($id) => $id !== null && $id !== 0
                );

                // Pour chaque permission existante du groupe (de cette entreprise), si son
                // ID n'est plus dans la payload → on la supprime
                $userEntrId = ($this->getUser() && method_exists($this->getUser(), 'getEntreprise') && $this->getUser()->getEntreprise())
                    ? $this->getUser()->getEntreprise()->getId()
                    : null;

                foreach ($groupe->getModuleGroupePermitions() as $existingPerm) {
                    $permEntrId = $existingPerm->getEntreprise() ? $existingPerm->getEntreprise()->getId() : null;
                    // Ne toucher qu'aux permissions de l'entreprise de l'utilisateur courant
                    if ($permEntrId !== $userEntrId) {
                        continue;
                    }
                    if (!in_array($existingPerm->getId(), $incomingIds, true)) {
                        $groupe->removeModuleGroupePermition($existingPerm);
                        $permissionRepository->getEntityManager()->remove($existingPerm);
                    }
                }

                // ── 2. Upsert des lignes reçues ──────────────────────────────────────
                foreach ($data['lignes'] as $permData) {
                    $permission = null;

                    // Si un ID est fourni, on cherche la permission existante
                    if (isset($permData['id']) && $permData['id'] !== null && $permData['id'] !== 0) {
                        $permission = $permissionRepository->find($permData['id']);

                        if ($permission) {
                            // Vérifier qu'elle appartient bien à ce groupe
                            if ($permission->getGroupeUser() !== $groupe) {
                                continue;
                            }
                            $permEntrId2 = $permission->getEntreprise() ? $permission->getEntreprise()->getId() : null;
                            if ($permEntrId2 !== $userEntrId) {
                                $permission = null; // Crée une nouvelle pour cette entreprise
                            }
                        }
                    }

                    // Création si pas trouvée
                    if (!$permission) {
                        $permission = new ModuleGroupePermition();
                        $groupe->addModuleGroupePermition($permission);
                    }

                    // Mise à jour des champs
                    if (isset($permData['module_id'])) {
                        $module = $moduleRepository->find($permData['module_id']);
                        if ($module) $permission->setModule($module);
                    }

                    if (isset($permData['permition_id'])) {
                        $permition = $permitionRepository->find($permData['permition_id']);
                        if ($permition) $permission->setPermition($permition);
                    }

                    if (isset($permData['groupe_module_id'])) {
                        $groupeModule = $groupeModuleRepository->find($permData['groupe_module_id']);
                        if ($groupeModule) $permission->setGroupeModule($groupeModule);
                    }

                    if (isset($permData['ordre'])) $permission->setOrdre($permData['ordre']);
                    if (isset($permData['ordre_groupe'])) $permission->setOrdreGroupe($permData['ordre_groupe']);
                    if (isset($permData['menu_principal'])) $permission->setMenuPrincipal($permData['menu_principal']);

                    if ($this->getUser() && method_exists($this->getUser(), 'getEntreprise')) {
                        $permission->setEntreprise($this->getUser()->getEntreprise());
                    }

                    $this->updateAuditFields($permission);
                }
            }

            $this->updateAuditFields($groupe);
            $groupeRepository->add($groupe, true);

            $response = $this->responseData($groupe, 'group1');
            return $this->filterPermissionsByEntreprise($response);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            return $this->response([]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/groupe/{id}",
        summary: "Supprimer un groupe",
        tags: ['Groupe']
    )]
    public function delete(Groupe $groupe, GroupeRepository $groupeRepository): Response
    {
        try {
            if (!$groupe) {
                return $this->errorResponse(null, "Groupe non trouvé", 404);
            }
            
            // Vérifier s'il a des utilisateurs ou des permissions liées ?
            // L'entité a orphanRemoval=true pour moduleGroupePermitions donc ça devrait aller pour les perms.
            // Pour les utilisateurs, c'est OneToMany mappedBy='groupe', donc Users ont setGroupe(null) si le groupe est supprimé ?
            // Non, 'removeUtilisateur' fait setGroupe(null).
            // Mais attention, si le côté owning est User, alors User.groupe_id deviendra NULL ou contrainte FK.
            // On peut tenter la suppression.
            
            $groupeRepository->remove($groupe, true);

            return $this->response(['message' => 'Groupe supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression du groupe");
            return $this->response([]);
        }
    }
}
