<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Employe;
use App\Repository\EmployeRepository;
use App\Repository\FonctionRepository;
use App\Repository\CiviliteRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/employe')]
#[OA\Tag(name: 'Employe', description: 'Gestion des employés')]
class ApiEmployeController extends ApiInterface
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
        path: "/api/employe/",
        summary: "Lister les employés",
        description: "Retourne la liste des employés de l'entreprise connectée.",
        tags: ['Employe']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, EmployeRepository $repository, \App\Repository\AgenceRepository $agenceRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $agenceId = $request->get('agence_id');
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) return $this->errorResponse(null, "Non autorisé", 401);
            
            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            
            if ($isSuperAdmin) {
                if ($agenceId && $agenceId !== 'null' && $agenceId !== 'all') {
                    $agence = $agenceRepository->find((int)$agenceId);
                    if ($agence && $agence->getEntreprise() === $user->getEntreprise()) {
                        $employes = $repository->findByAgence($agence);
                    } else {
                        $employes = [];
                    }
                } else {
                    $employes = $repository->findBy(['entreprise' => $user->getEntreprise()], ['id' => 'DESC']);
                }
            } else {
                $employes = $repository->findByAgence($user->getAgence());
            }

            if ($withPagination == "true") {
                $employes = $this->paginationService->paginate($employes);
            }

            return $this->responseData($employes, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/sans-compte', methods: ['GET'])]
    #[OA\Get(
        path: "/api/employe/sans-compte",
        summary: "Lister les employés sans compte utilisateur",
        description: "Retourne la liste des employés qui n'ont pas encore de compte utilisateur associé.",
        tags: ['Employe']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function getEmployesSansCompte(Request $request, EmployeRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non authentifié", 401);

            // Use the withoutAccount method from repository
            $qb = $repository->withoutAccount();
            $employes = $qb->getQuery()->getResult();

            if ($withPagination == "true") {
                $employes = $this->paginationService->paginate($employes);
            }
            
            return $this->responseData($employes, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/employe/create",
        summary: "Créer un employé",
        description: "Ajoute un nouvel employé.",
        tags: ['Employe']
    )]
    public function create(Request $request, EmployeRepository $repository, CiviliteRepository $civiliteRepository, \App\Service\SubscriptionService $subscriptionService): Response
    {
        try {
            $user = $this->getUser();
            if (!$subscriptionService->canAddEmploye($user->getEntreprise())) {
                return $this->errorResponse(null, "Limite d'employés atteinte pour votre abonnement actuel.", 403);
            }
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $employe = new Employe();
            
            if (isset($data['nom'])) $employe->setNom($data['nom']);
            if (isset($data['prenom'])) $employe->setPrenom($data['prenom']);
            if (isset($data['matricule'])) $employe->setMatricule($data['matricule']);
            if (isset($data['contact'])) $employe->setContact($data['contact']);
            if (isset($data['adresseMail'])) $employe->setAdresseMail($data['adresseMail']);
            if (isset($data['numPiece'])) $employe->setNumPiece($data['numPiece']);
            if (isset($data['contacts'])) $employe->setContacts($data['contacts']); // Note: Employe entity has both contact and contacts
            if (isset($data['residence'])) $employe->setResidence($data['residence']);

            if (isset($data['fonction'])) {
                $employe->setFonction($data['fonction']);
            }
            if (isset($data['civilite_id'])) {
                $civilite = $civiliteRepository->find($data['civilite_id']);
                if ($civilite) $employe->setCivilite($civilite);
            }

            // Upload piece
            $uploadedFile = $request->files->get('piece');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('piece_'.uniqid());
                $filePath = $this->getUploadDir('pieces_employes', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'pieces_employes')) {
                    $employe->setPiece($fichier);
                }
            }

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $user = $this->getUser();
                $employe->setEntreprise($user->getEntreprise());
                
                // Set Agence
                if (isset($data['agence_id'])) {
                    $agence = $this->em->getRepository(\App\Entity\Agence::class)->find((int)$data['agence_id']);
                    if ($agence && $agence->getEntreprise() === $user->getEntreprise()) {
                        $employe->setAgence($agence);
                    } else {
                        return $this->errorResponse(null, "Agence non autorisée ou introuvable", 400);
                    }
                } else {
                    if (!$user->getAgence()) {
                        return $this->errorResponse(null, "Vous devez être rattaché à une agence pour créer un employé", 400);
                    }
                    $employe->setAgence($user->getAgence());
                }
            }

            $this->updateAuditFields($employe, true);

            $repository->save($employe, true);

            return $this->responseData($employe, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    #[OA\Put(
        path: "/api/employe/{id}",
        summary: "Modifier un employé",
        description: "Met à jour un employé existant.",
        tags: ['Employe']
    )]
    public function update(Request $request, Employe $employe, EmployeRepository $repository, CiviliteRepository $civiliteRepository): Response
    {
        try {
            if (!$employe) return $this->errorResponse(null, "Employé non trouvé", 404);

            $data = json_decode($request->getContent(), true);
             if (null === $data) {
                $data = $request->request->all();
            }
            
            if (isset($data['nom'])) $employe->setNom($data['nom']);
            if (isset($data['prenom'])) $employe->setPrenom($data['prenom']);
            if (isset($data['matricule'])) $employe->setMatricule($data['matricule']);
            if (isset($data['contact'])) $employe->setContact($data['contact']);
            if (isset($data['adresseMail'])) $employe->setAdresseMail($data['adresseMail']);
            if (isset($data['numPiece'])) $employe->setNumPiece($data['numPiece']);
            if (isset($data['contacts'])) $employe->setContacts($data['contacts']);
            if (isset($data['residence'])) $employe->setResidence($data['residence']);

            if (isset($data['fonction'])) {
                $employe->setFonction($data['fonction']);
            }
            if (isset($data['civilite_id'])) {
                $civilite = $civiliteRepository->find($data['civilite_id']);
                if ($civilite) $employe->setCivilite($civilite);
            }

            // Upload piece
            $uploadedFile = $request->files->get('piece');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('piece_'.uniqid());
                $filePath = $this->getUploadDir('pieces_employes', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'pieces_employes')) {
                    $employe->setPiece($fichier);
                }
            }

            $this->updateAuditFields($employe);

            $repository->save($employe, true);

            return $this->responseData($employe, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/employe/{id}",
        summary: "Supprimer un employé",
        description: "Supprime un employé.",
        tags: ['Employe']
    )]
    public function delete(Employe $employe, EmployeRepository $repository): Response
    {
        try {
            if (!$employe) return $this->errorResponse(null, "Employé non trouvé", 404);
            $repository->remove($employe, true);
            return $this->response(['message' => 'Employé supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
