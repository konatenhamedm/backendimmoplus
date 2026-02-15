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
            $groupes = $groupeRepository->findAll();

            if ($withPagination == "true") {
                $groupes = $this->paginationService->paginate($groupes);
            }

            // Using 'group1' context, ensure Entity has #[Groups(['group1'])] on properties
            return $this->responseData($groupes, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des groupes");
            return $this->response([]);
        }
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
                    
                    $this->updateAuditFields($permission, true);
                    $groupe->addModuleGroupePermition($permission);
                }
            }

            $this->updateAuditFields($groupe, true);
            $groupeRepository->add($groupe, true);

            return $this->responseData($groupe, 'group1', ['Content-Type' => 'application/json']);
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
            return $this->responseData($groupe, 'group1');
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
                foreach ($data['lignes'] as $permData) {
                    $permission = null;
                    
                    // If ID is provided, try to find and update existing permission
                    if (isset($permData['id']) && $permData['id'] !== null) {
                        $permission = $permissionRepository->find($permData['id']);
                        
                        // Verify the permission belongs to this groupe
                        if ($permission && $permission->getGroupeUser() !== $groupe) {
                            continue; // Skip if permission doesn't belong to this groupe
                        }
                    }
                    
                    // If no existing permission found, create a new one
                    if (!$permission) {
                        $permission = new ModuleGroupePermition();
                        $groupe->addModuleGroupePermition($permission);
                    }
                    
                    // Update permission fields
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
                    $this->updateAuditFields($permission);
                }
            }

            $this->updateAuditFields($groupe);
            $groupeRepository->add($groupe, true);

            return $this->responseData($groupe, 'group1');
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
