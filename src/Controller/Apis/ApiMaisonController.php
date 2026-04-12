<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Appartement;
use App\Entity\Maison;
use App\Repository\MaisonRepository;
use App\Repository\ProprioRepository;
use App\Repository\QuartierRepository;
use App\Repository\TypeMaisonRepository;
use App\Service\SubscriptionService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/maison')]
#[OA\Tag(name: 'Maison', description: 'Gestion des maisons')]
class ApiMaisonController extends ApiInterface
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
        path: "/api/maison/",
        summary: "Lister les maisons",
        description: "Retourne la liste des maisons (filtrées par entreprise du propriétaire).",
        tags: ['Maison']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, MaisonRepository $repository, \App\Repository\AgenceRepository $agenceRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $agenceId = $request->get('agence_id');
            $user = $this->getUser();
            
            if ($user && $user->getEntreprise()) {
                $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
                $agence = $isSuperAdmin ? $agenceId : $user->getAgence();
                $search = $request->get('search');
                
                $maisons = $repository->findWithFilters(
                    $user->getEntreprise(),
                    $agence,
                    $search
                );
            } else {
                $maisons = [];
            }

            if ($withPagination == "true") {
                $maisons = $this->paginationService->paginate($maisons);
            }

            return $this->responseData($maisons, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/maison/create",
        summary: "Créer une maison",
        description: "Ajoute une nouvelle maison.",
        tags: ['Maison']
    )]
    public function create(Request $request, MaisonRepository $repository, QuartierRepository $quartierRepository, ProprioRepository $proprioRepository, TypeMaisonRepository $typeMaisonRepository, SubscriptionService $subscriptionService): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Vous devez être rattaché à une entreprise", 403);
            }

            // Vérifier la limite de l'abonnement
            if (!$subscriptionService->canAddMaison($user->getEntreprise())) {
                $planName = $subscriptionService->getCurrentPlanName($user->getEntreprise());
                return $this->errorResponse(null, "Limite de biens atteinte pour votre abonnement actuel ($planName). Veuillez passer à un plan supérieur.", 403);
            }

            $data = json_decode($request->getContent(), true);
            $maison = new Maison();
            
            // Set Agence
            if (isset($data['agence_id'])) {
                $agence = $this->em->getRepository(\App\Entity\Agence::class)->find((int)$data['agence_id']);
                if ($agence && $agence->getEntreprise() && $user->getEntreprise() && $agence->getEntreprise()->getId() === $user->getEntreprise()->getId()) {
                    $maison->setAgence($agence);
                } else {
                    return $this->errorResponse(null, "Agence introuvable ou non autorisée", 400);
                }
            } else {
                if (!$user->getAgence()) {
                    return $this->errorResponse(null, "Vous devez être rattaché à une agence pour créer une maison", 400);
                }
                $maison->setAgence($user->getAgence());
            }
            if (isset($data['libMaison'])) $maison->setLibMaison($data['libMaison']);
            if (isset($data['lot'])) $maison->setLot($data['lot']);
            if (isset($data['ilot'])) $maison->setIlot($data['ilot']);
            if (isset($data['mntCom'])) $maison->setMntCom($data['mntCom']);
            if (isset($data['localisation'])) $maison->setLocalisation($data['localisation']);
            if (isset($data['tFoncier'])) $maison->setTFoncier($data['tFoncier']);

            if (isset($data['quartier_id'])) {
                $quartier = $quartierRepository->find($data['quartier_id']);
                if (!$quartier) return $this->errorResponse(null, "Quartier non trouvé", 404);
                $maison->setQuartier($quartier);
            }
            if (isset($data['proprio_id'])) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
                $maison->setProprio($proprio);
            }
            if (isset($data['type_maison_id'])) {
                $typeMaison = $typeMaisonRepository->find($data['type_maison_id']);
                if (!$typeMaison) return $this->errorResponse(null, "Type de maison non trouvé", 404);
                $maison->setTypeMaison($typeMaison);
            }

            if (isset($data['agent_id'])) {
                $agent = $this->em->getRepository(\App\Entity\User::class)->find((int)$data['agent_id']);
                if ($agent) $maison->setIdAgent($agent);
            } elseif ($this->getUser()) {
                $maison->setIdAgent($this->getUser());
            }

            // Gestion des appartements inclus
            if (isset($data['appartements']) && is_array($data['appartements'])) {
                foreach ($data['appartements'] as $appartData) {
                    $appartement = new Appartement();
                    if (isset($appartData['libAppart'])) $appartement->setLibAppart($appartData['libAppart']);
                    if (isset($appartData['nbrePieces'])) $appartement->setNbrePieces($appartData['nbrePieces']);
                    if (isset($appartData['numEtage'])) $appartement->setNumEtage($appartData['numEtage']);
                    if (isset($appartData['loyer'])) $appartement->setLoyer($appartData['loyer']);
                    if (isset($appartData['details'])) $appartement->setDetails($appartData['details']);
                    //if (isset($appartData['oqp'])) $appartement->setOqp($appartData['oqp']);
                    
                    $appartement->setMaisson($maison);
                    $this->updateAuditFields($appartement, true);
                    $maison->addAppartement($appartement);
                }
            }

            $this->updateAuditFields($maison, true);

            $repository->save($maison, true);

            return $this->responseData($maison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/maison/{id}",
        summary: "Modifier une maison",
        description: "Met à jour une maison existante et ses appartements.",
        tags: ['Maison']
    )]
    public function update(Request $request, Maison $maison, MaisonRepository $repository, QuartierRepository $quartierRepository, ProprioRepository $proprioRepository, TypeMaisonRepository $typeMaisonRepository, \App\Repository\AppartementRepository $appartementRepository): Response
    {
        try {
            if (!$maison) return $this->errorResponse(null, "Maison non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libMaison'])) $maison->setLibMaison($data['libMaison']);
            if (isset($data['lot'])) $maison->setLot($data['lot']);
            if (isset($data['ilot'])) $maison->setIlot($data['ilot']);
            if (isset($data['mntCom'])) $maison->setMntCom($data['mntCom']);
            if (isset($data['localisation'])) $maison->setLocalisation($data['localisation']);
            if (isset($data['tFoncier'])) $maison->setTFoncier($data['tFoncier']);

            if (isset($data['quartier_id'])) {
                $quartier = $quartierRepository->find($data['quartier_id']);
                if (!$quartier) return $this->errorResponse(null, "Quartier non trouvé", 404);
                $maison->setQuartier($quartier);
            }
            if (isset($data['proprio_id'])) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
                $maison->setProprio($proprio);
            }
             if (isset($data['type_maison_id'])) {
                $typeMaison = $typeMaisonRepository->find($data['type_maison_id']);
                if (!$typeMaison) return $this->errorResponse(null, "Type de maison non trouvé", 404);
                $maison->setTypeMaison($typeMaison);
            }

            if (isset($data['agent_id'])) {
                $agent = $this->em->getRepository(\App\Entity\User::class)->find((int)$data['agent_id']);
                if ($agent) $maison->setIdAgent($agent);
            }

            // Gestion des appartements inclus (Mise à jour ou Ajout)
            if (isset($data['appartements']) && is_array($data['appartements'])) {
                foreach ($data['appartements'] as $appartData) {
                    if (isset($appartData['id'])) {
                        // Update existing apartment if it belongs to this maison
                        $appartement = $appartementRepository->find($appartData['id']);
                        if ($appartement && $appartement->getMaisson() && $appartement->getMaisson()->getId() === $maison->getId()) {
                            if (isset($appartData['libAppart'])) $appartement->setLibAppart($appartData['libAppart']);
                            if (isset($appartData['nbrePieces'])) $appartement->setNbrePieces($appartData['nbrePieces']);
                            if (isset($appartData['numEtage'])) $appartement->setNumEtage($appartData['numEtage']);
                            if (isset($appartData['loyer'])) $appartement->setLoyer($appartData['loyer']);
                            if (isset($appartData['details'])) $appartement->setDetails($appartData['details']);
                             // Only update 'oqp' if explicitely provided
                            if (isset($appartData['oqp'])) $appartement->setOqp($appartData['oqp']);
                            
                            $this->updateAuditFields($appartement);
                        }
                    } else {
                        // Create new apartment
                        $appartement = new Appartement();
                        if (isset($appartData['libAppart'])) $appartement->setLibAppart($appartData['libAppart']);
                        if (isset($appartData['nbrePieces'])) $appartement->setNbrePieces($appartData['nbrePieces']);
                        if (isset($appartData['numEtage'])) $appartement->setNumEtage($appartData['numEtage']);
                        if (isset($appartData['loyer'])) $appartement->setLoyer($appartData['loyer']);
                        if (isset($appartData['details'])) $appartement->setDetails($appartData['details']);
                        if (isset($appartData['oqp'])) $appartement->setOqp($appartData['oqp']);
                        
                        $this->updateAuditFields($appartement, true);
                        $maison->addAppartement($appartement);
                    }
                }
            }

            $this->updateAuditFields($maison);

            $repository->save($maison, true);

            return $this->responseData($maison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/affecter-agent', methods: ['POST'])]
    #[OA\Post(
        path: "/api/maison/{id}/affecter-agent",
        summary: "Affecter un agent à une maison",
        description: "Associe un agent à une maison pour la collecte des loyers.",
        tags: ['Maison']
    )]
    public function affecterAgent(Request $request, Maison $maison, MaisonRepository $repository, \App\Repository\UserRepository $userRepository): Response
    {
        try {
            if (!$maison) return $this->errorResponse(null, "Maison non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            if (!isset($data['agent_id'])) {
                return $this->errorResponse(null, "L'ID de l'agent est requis", 400);
            }

            $agent = $userRepository->find($data['agent_id']);
            if (!$agent) {
                return $this->errorResponse(null, "Agent non trouvé", 404);
            }

            $maison->setIdAgent($agent);
            $repository->save($maison, true);

            return $this->responseData($maison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/mes-maisons', methods: ['GET'])]
    #[OA\Get(
        path: "/api/maison/mes-maisons",
        summary: "Lister mes maisons (Assignées à l'agent)",
        tags: ['Maison']
    )]
    public function mesMaisons(MaisonRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non autorisé", 403);
            
            $maisons = $repository->findBy(['idAgent' => $user->getId()]);
            return $this->responseData($maisons, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
