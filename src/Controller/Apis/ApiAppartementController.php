<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Appartement;
use App\Repository\AppartementRepository;
use App\Repository\MaisonRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/appartement')]
#[OA\Tag(name: 'Appartement', description: 'Gestion des appartements')]
class ApiAppartementController extends ApiInterface
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
        path: "/api/appartement/",
        summary: "Lister les appartements",
        description: "Retourne la liste des appartements (filtrée par entreprise).",
        tags: ['Appartement']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, AppartementRepository $repository, MaisonRepository $maisonRepository, \App\Repository\AgenceRepository $agenceRepository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $agenceId = $request->get('agence_id');
            $maisonId = $request->get('maison_id');
            $user = $this->getUser();
    
            if ($user && $user->getEntreprise()) {
                $entreprise = $user->getEntreprise();
                $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
                
                $qb = $repository->createQueryBuilder('a')
                    ->join('a.maisson', 'm')
                    ->join('m.agence', 'ag')
                    ->andWhere('ag.entreprise = :entreprise')
                    ->setParameter('entreprise', $entreprise);

                if ($isSuperAdmin) {
                    if ($agenceId && $agenceId !== 'null' && $agenceId !== 'all') {
                        $qb->andWhere('m.agence = :agence')
                           ->setParameter('agence', $agenceId);
                    }
                } else {
                    $qb->andWhere('m.agence = :agence')
                       ->setParameter('agence', $user->getAgence());
                }

                if ($maisonId && $maisonId !== 'null') {
                    $qb->andWhere('a.maisson = :maison')
                       ->setParameter('maison', $maisonId);
                }

                $search = $request->get('search');
                if ($search) {
                    $qb->andWhere('a.libAppart LIKE :search OR m.libMaison LIKE :search')
                       ->setParameter('search', '%'.$search.'%');
                }

                $appartements = $qb->getQuery()->getResult();
            } else {
                $appartements = [];
            }

            if ($withPagination == "true") {
                $appartements = $this->paginationService->paginate($appartements);
            }
            
            return $this->responseData($appartements, 'appartement-groupe', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/disponible', methods: ['GET'])]
    #[OA\Get(
        path: "/api/appartement/disponible",
        summary: "Lister les appartements disponibles",
        description: "Retourne la liste des appartements libres (Oqp = 0) filtrés par agence.",
        tags: ['Appartement']
    )]
    public function getFreeAppartements(Request $request, AppartementRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            $agenceId = $request->get('agence_id');
            
            if (!$user || !$user->getEntreprise()) {
                return $this->responseData([], 'group1');
            }

            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            
            $qb = $repository->createQueryBuilder('a')
                ->join('a.maisson', 'm')
                ->join('m.agence', 'ag')
                ->andWhere('a.oqp = :status')
                ->andWhere('ag.entreprise = :entreprise')
                ->setParameter('status', 0)
                ->setParameter('entreprise', $user->getEntreprise());

            if ($isSuperAdmin) {
                if ($agenceId && $agenceId !== 'null' && $agenceId !== 'all') {
                    $qb->andWhere('m.agence = :agence')
                       ->setParameter('agence', $agenceId);
                }
            } else {
                $qb->andWhere('m.agence = :agence')
                   ->setParameter('agence', $user->getAgence());
            }
            
            $appartements = $qb->getQuery()->getResult();
            
            return $this->responseData($appartements, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/appartement/create",
        summary: "Créer un appartement",
        description: "Ajoute un nouvel appartement à une maison.",
        tags: ['Appartement']
    )]
    public function create(Request $request, AppartementRepository $repository, MaisonRepository $maisonRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $appartement = new Appartement();
            
            if (isset($data['libAppart'])) $appartement->setLibAppart($data['libAppart']);
            if (isset($data['nbrePieces'])) $appartement->setNbrePieces($data['nbrePieces']);
            if (isset($data['numEtage'])) $appartement->setNumEtage($data['numEtage']);
            if (isset($data['loyer'])) $appartement->setLoyer($data['loyer']);
            if (isset($data['caution'])) $appartement->setCaution($data['caution']);
            if (isset($data['details'])) $appartement->setDetails($data['details']);
            if (isset($data['oqp'])) $appartement->setOqp($data['oqp']);

            if (isset($data['maison_id'])) {
                $maison = $maisonRepository->find($data['maison_id']);
                if (!$maison) return $this->errorResponse(null, "Maison non trouvée", 404);
                $appartement->setMaisson($maison);
            }

            $this->updateAuditFields($appartement, true);

            $repository->save($appartement, true);

            return $this->responseData($appartement);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/appartement/{id}",
        summary: "Modifier un appartement",
        description: "Met à jour un appartement existant.",
        tags: ['Appartement']
    )]
    public function update(Request $request, Appartement $appartement, AppartementRepository $repository, MaisonRepository $maisonRepository): Response
    {
        try {
            if (!$appartement) return $this->errorResponse(null, "Appartement non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['libAppart'])) $appartement->setLibAppart($data['libAppart']);
            if (isset($data['nbrePieces'])) $appartement->setNbrePieces($data['nbrePieces']);
            if (isset($data['numEtage'])) $appartement->setNumEtage($data['numEtage']);
            if (isset($data['loyer'])) $appartement->setLoyer($data['loyer']);
            if (isset($data['caution'])) $appartement->setCaution($data['caution']);
            if (isset($data['details'])) $appartement->setDetails($data['details']);
            if (isset($data['oqp'])) $appartement->setOqp($data['oqp']);

             if (isset($data['maison_id'])) {
                $maison = $maisonRepository->find($data['maison_id']);
                if (!$maison) return $this->errorResponse(null, "Maison non trouvée", 404);
                $appartement->setMaisson($maison);
            }

            $this->updateAuditFields($appartement);

            $repository->save($appartement, true);

            return $this->responseData($appartement);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/appartement/{id}",
        summary: "Supprimer un appartement",
        description: "Supprime un appartement.",
        tags: ['Appartement']
    )]
    public function delete(Appartement $appartement, AppartementRepository $repository): Response
    {
        try {
            if (!$appartement) return $this->errorResponse(null, "Appartement non trouvé", 404);
            $repository->remove($appartement, true);
            return $this->response(['message' => 'Appartement supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
