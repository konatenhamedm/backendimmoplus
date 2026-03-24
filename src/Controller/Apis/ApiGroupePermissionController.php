<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Groupe;
use App\Entity\Module;
use App\Entity\ModuleGroupePermition;
use App\Entity\Permition;
use App\Repository\GroupeRepository;
use App\Repository\ModuleGroupePermitionRepository;
use App\Repository\ModuleRepository;
use App\Repository\PermitionRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/groupe-permission')]
#[OA\Tag(name: 'Permissions Groupe', description: 'Gestion des permissions et accès aux modules par groupe')]
class ApiGroupePermissionController extends ApiInterface
{
    /**
     * Liste tous les modules disponibles
     */
    #[Route('/modules', methods: ['GET'])]
    #[OA\Get(
        path: "/api/groupe-permission/modules",
        summary: "Lister les modules",
        description: "Retourne la liste de tous les modules disponibles pour l'attribution de droits.",
        tags: ['Permissions Groupe']
    )]
    public function listModules(ModuleRepository $moduleRepository): Response
    {
        try {
            $modules = $moduleRepository->findAll();
            return $this->responseData($modules, null, [], false);
        } catch (\Exception $exception) {
             $this->setStatusCode(500);
            return $this->response([]);
        }
    }

    /**
     * Liste tous les types de permissions
     */
    #[Route('/permissions', methods: ['GET'])]
    #[OA\Get(
        path: "/api/groupe-permission/permissions",
        summary: "Lister les types de permissions",
        description: "Retourne la liste des types de permissions (Lecture, Écriture, etc.).",
        tags: ['Permissions Groupe']
    )]
    public function listPermissions(PermitionRepository $permitionRepository): Response
    {
        try {
            $permissions = $permitionRepository->findAll();
            return $this->responseData($permissions, null, [], false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response([]);
        }
    }

    /**
     * Liste toutes les catégories de modules (GroupeModule)
     */
    #[Route('/categories', methods: ['GET'])]
    #[OA\Get(
        path: "/api/groupe-permission/categories",
        summary: "Lister les catégories de modules",
        description: "Retourne la liste de toutes les catégories (GroupeModule) disponibles.",
        tags: ['Permissions Groupe']
    )]
    public function listCategories(\App\Repository\GroupeModuleRepository $repository): Response
    {
        try {
            $categories = $repository->findBy([], ['ordre' => 'ASC']);
            return $this->responseData($categories, 'group1', [], false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    /**
     * Liste les permissions d'un groupe spécifique
     */
    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: "/api/groupe-permission/{id}",
        summary: "Lister les permissions d'un groupe",
        description: "Retourne la liste des modules et permissions associées pour un groupe donné.",
        tags: ['Permissions Groupe']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID du groupe",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, Groupe $groupe): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if (!$groupe) {
                return $this->errorResponse(null, "Groupe non trouvé", 404);
            }

            // Récupérer les ModuleGroupePermition liés à ce groupe
            // L'entité Groupe a une relation OneToMany 'moduleGroupePermitions'
            $permissions = $groupe->getModuleGroupePermitions();

            // S'assurer que la sérialisation inclut les détails du module et de la permission
            // On peut définir un contexte de sérialisation spécifique ou utiliser 'group1' si configuré
            // Comme on ne connait pas les groupes de sérialisation exacts, on va essayer de limiter la profondeur
            // pour éviter les références circulaires.
            
            $context = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                    return $object->getId();
                },
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['groupeUser', 'utilisateurs', 'roles'] // On ignore le lien inverse vers user/groupe pour éviter la boucle
            ];

            // Filtrer par entreprise
            $userEntrepriseId = ($this->getUser() && method_exists($this->getUser(), 'getEntreprise') && $this->getUser()->getEntreprise()) ? $this->getUser()->getEntreprise()->getId() : null;
            $filteredPermissions = [];
            foreach ($permissions as $perm) {
                $permEntreprise = $perm->getEntreprise();
                if (($permEntreprise ? $permEntreprise->getId() : null) === $userEntrepriseId) {
                    $filteredPermissions[] = $perm;
                }
            }

            return $this->responseData($filteredPermissions, null, [], false); // false pour pagination car Collection
            
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    /**
     * Assigne une permission (Module + Action) à un groupe
     */
    #[Route('/assign/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/groupe-permission/assign/{id}",
        summary: "Assigner une permission à un groupe",
        description: "Crée une entrée ModuleGroupePermition liant un groupe, un module et un type de permission.",
        tags: ['Permissions Groupe']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["module_id", "permission_id"],
            properties: [
                new OA\Property(property: "module_id", type: "integer", description: "ID du module concerné"),
                new OA\Property(property: "permission_id", type: "integer", description: "ID du type de permission (Permition)"),
                new OA\Property(property: "ordre", type: "integer", description: "Ordre d'affichage (oprionnel)"),
                new OA\Property(property: "menu_principal", type: "boolean", description: "Afficher au menu principal (optionnel)")
            ]
        )
    )]
    public function assign(
        Request $request, 
        Groupe $groupe, 
        ModuleRepository $moduleRepository, 
        PermitionRepository $permitionRepository,
        ModuleGroupePermitionRepository $repository
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$groupe) {
                return $this->errorResponse(null, "Groupe non trouvé", 404);
            }

            $module = $moduleRepository->find($data['module_id']);
            if (!$module) {
                return $this->errorResponse(null, "Module non trouvé", 404);
            }

            $permition = $permitionRepository->find($data['permission_id']);
            if (!$permition) {
                return $this->errorResponse(null, "Type de permission non trouvé", 404);
            }

            $existing = $repository->findOneBy([
                'groupeUser' => $groupe,
                'module' => $module
            ]);
            
             $entity = new ModuleGroupePermition();
             if($existing){
                 $entity = $existing;
             }

            $entity->setGroupeUser($groupe);
            $entity->setModule($module);
            $entity->setPermition($permition);
            
            if ($this->getUser() && method_exists($this->getUser(), 'getEntreprise')) {
                $entity->setEntreprise($this->getUser()->getEntreprise());
            }
            
            if (isset($data['ordre'])) {
                $entity->setOrdre($data['ordre']);
            } else {
                 if(!$existing) $entity->setOrdre(0);
            }
            
            // ordreGroupe aussi
             if (isset($data['ordre'])) {
                $entity->setOrdreGroupe($data['ordre']);
            } else {
                if(!$existing) $entity->setOrdreGroupe(0);
            }

            if (isset($data['menu_principal'])) {
                $entity->setMenuPrincipal($data['menu_principal']);
            } else {
                if(!$existing) $entity->setMenuPrincipal(true); // Par défaut visible
            }

            $this->updateAuditFields($entity, !$existing);
            $repository->save($entity, true);

            return $this->responseData($entity, null, [], false);
            
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            return $this->response([]);
        }
    }

    /**
     * Supprimer une permission
     */
    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/groupe-permission/{id}",
        summary: "Supprimer une permission",
        description: "Supprime l'association ModuleGroupePermition par son ID.",
        tags: ['Permissions Groupe']
    )]
    public function delete(ModuleGroupePermition $permission, ModuleGroupePermitionRepository $repository): Response
    {
        try {
            if (!$permission) {
                return $this->errorResponse(null, "Permission non trouvée", 404);
            }
            
            $repository->remove($permission, true);

            return $this->response(['message' => 'Permission supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression");
            return $this->response([]);
        }
    }
    /**
     * Vérifier la permission sur une ressource (GroupeModule) pour un Groupe
     */
    #[Route('/check', methods: ['GET'])]
    #[OA\Get(
        path: "/api/groupe-permission/check",
        summary: "Vérifier la permission sur une ressource",
        description: "Retourne les détails de la permission pour un couple (GroupeUtilisateur, GroupeModule).",
        tags: ['Permissions Groupe']
    )]
    #[OA\Parameter(name: "groupe_id", in: "query", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "groupe_module_id", in: "query", required: true, schema: new OA\Schema(type: "integer"))]
    public function checkPermission(Request $request, ModuleGroupePermitionRepository $repository): Response
    {
        try {
            $groupeId = $request->query->get('groupe_id');
            $groupeModuleId = $request->query->get('groupe_module_id');

            if (!$groupeId || !$groupeModuleId) {
                return $this->errorResponse(null, "Paramètres groupe_id et groupe_module_id requis", 400);
            }

            $userEntrepriseId = ($this->getUser() && method_exists($this->getUser(), 'getEntreprise') && $this->getUser()->getEntreprise()) ? $this->getUser()->getEntreprise()->getId() : null;

            // Recherche de la permission spécifique testant aussi l'entreprise
            $permissions = $repository->findBy([
                'groupeUser' => $groupeId,
                'groupeModule' => $groupeModuleId
            ]);

            $permission = null;
            foreach ($permissions as $p) {
                $pEntrId = $p->getEntreprise() ? $p->getEntreprise()->getId() : null;
                if ($pEntrId === $userEntrepriseId) {
                    $permission = $p;
                    break;
                }
            }

            if (!$permission) {
                // Pas de permission explicite trouvée, ou accès refusé
                // On peut retourner un objet vide ou un status 404, mais 200 avec null est souvent mieux pour un 'check'
                return $this->response(['permission' => null, 'access' => false]);
            }

            // On retourne les détails de la permission trouvée
            // On peut aussi renvoyer 'access' => true
            // Attention aux références circulaires lors de la sérialisation de l'objet complet
            // On construit une réponse simple
            
            $permissionData = [
                'id' => $permission->getId(),
                'permission' => [
                    'id' => $permission->getPermition()->getId(),
                    'code' => $permission->getPermition()->getCode(),
                    'libelle' => $permission->getPermition()->getLibelle()
                ],
                'access' => true
            ];

            return $this->response($permissionData);

        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
