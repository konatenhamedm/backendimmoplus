<?php

namespace App\Controller\Apis;

use App\Entity\Groupe;
use App\Repository\ModuleGroupePermitionRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Controller\Apis\Config\ApiInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface; // Added for dynamic path generation
use Symfony\Component\Routing\Exception\RouteNotFoundException; // Added for error handling
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/api/menu')]
#[OA\Tag(name: 'Menu', description: 'Génération du menu dynamique')]
class ApiMenuController extends ApiInterface
{
    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/menu/{id}",
        summary: "Récupérer le menu pour un groupe",
        description: "Génère la structure du menu (modules et sous-menus) pour un groupe d'utilisateur donné.",
        tags: ['Menu']
    )]
    #[OA\Parameter( // Added OpenAPI parameter description for 'id'
        name: "id",
        in: "path",
        description: "ID du groupe utilisateur",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Menu généré avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "title", type: "string"),
                            new OA\Property(property: "icon", type: "string", nullable: true), // Icon can be null for parent modules
                            new OA\Property(property: "path", type: "string", nullable: true),
                            new OA\Property(
                                property: "subItems",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "title", type: "string"),
                                        new OA\Property(property: "path", type: "string"),
                                        new OA\Property(property: "icon", type: "string", nullable: true) // Icon can be null for sub-items
                                    ]
                                )
                            )
                        ]
                    )
                )
            ]
        )
    )]
    public function getMenu(Groupe $groupe, ModuleGroupePermitionRepository $repository, ModuleAbonnementRepository $abonnementRepository, RouterInterface $router): Response
    {
        try {
            if (!$groupe) {
                return $this->errorResponse(null, "Groupe non trouvé", 404);
            }

            $entreprise = $this->getUser() && method_exists($this->getUser(), 'getEntreprise') ? $this->getUser()->getEntreprise() : null;
            $entrepriseId = $entreprise ? $entreprise->getId() : null;
            // Récupérer la structure complète du menu
            $menuData = $repository->getMenuStructure($groupe->getId(), $entrepriseId);

            // Fetch the active ModuleAbonnement for the current entreprise
            $activeAbonnement = null;
            if ($entreprise && $entreprise->getAbonnement()) {
                $activeAbonnement = $abonnementRepository->findOneBy(['code' => $entreprise->getAbonnement()]);
            }

            // Organiser les données par module_id (grands titres)
            $menuByModule = [];
            
            foreach ($menuData as $item) {
                $moduleId = $item['module_id'];
                $moduleTitre = $item['module_titre'];

                // Filtrage basé sur l'abonnement
                if ($activeAbonnement) {
                    if ($moduleTitre === 'Gestion Terrains' && !$activeAbonnement->isHasGestionTerrains()) {
                        continue;
                    }
                    if ($moduleTitre === 'Gestion Immobilière' && !$activeAbonnement->isHasGestionImmobiliere()) {
                        continue;
                    }
                    // Adapt the title to what you have in DB for "Gestion Résidence" or "Gestion Locative"
                    if (($moduleTitre === 'Gestion Résidence' || $moduleTitre === 'Gestion Locative') && !$activeAbonnement->isHasGestionResidence()) {
                        continue;
                    }
                }
                
                if (!isset($menuByModule[$moduleId])) {
                    $menuByModule[$moduleId] = [
                        'title' => $moduleTitre,
                        'icon' => $item['module_icon'], // Icône dynamique depuis la base !
                        'path' => '#', // Les modules sont des menus déroulants
                        'ordre' => $item['module_ordre'],
                        'subItems' => []
                    ];
                }
                
                // Ajouter la ressource (GroupeModule) au module
                $menuByModule[$moduleId]['subItems'][] = [
                    'title' => $item['ressource_titre'],
                    'path' => $item['ressource_lien'] ?? '#',
                    'icon' => $item['ressource_icon'], // Les ressources ont aussi des icônes
                    'ordre' => $item['ressource_ordre'],
                    'permission' => $item['permission_code'] ?? 'R'
                ];
            }

            // Convertir en tableau indexé et trier par ordre
            $finalMenu = array_values($menuByModule);
            usort($finalMenu, function($a, $b) {
                return $a['ordre'] <=> $b['ordre'];
            });

            // Supprimer le champ 'ordre' de la réponse finale
            foreach ($finalMenu as &$group) {
                unset($group['ordre']);
                
                // Trier les sous-items par ordre
                usort($group['subItems'], function($a, $b) {
                    return $a['ordre'] <=> $b['ordre'];
                });
                
                // Supprimer le champ 'ordre' des sous-items
                foreach ($group['subItems'] as &$subItem) {
                    unset($subItem['ordre']);
                }
            }

            return $this->responseData($finalMenu);

        } catch (\Exception $exception) {
             $this->setStatusCode(500);
             return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
