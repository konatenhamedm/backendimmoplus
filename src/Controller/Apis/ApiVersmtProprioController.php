<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\VersmtProprio;
use App\Repository\LocataireRepository;
use App\Repository\MaisonRepository;
use App\Repository\ProprioRepository;
use App\Repository\TypeVersementsRepository;
use App\Repository\VersmtProprioRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/versement-proprio')]
#[OA\Tag(name: 'VersmtProprio', description: 'Gestion des versements (propriétaire/locataire)')]
class ApiVersmtProprioController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        path: "/api/versement-proprio",
        summary: "Liste des versements",
        description: "Retourne la liste de tous les versements.",
        tags: ['VersmtProprio']
    )]
    public function index(Request $request, VersmtProprioRepository $repository): Response
    {
        try {
            $versements = $repository->findBy([], ['id' => 'DESC']);
            return $this->responseData($versements, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/locataire/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/versement-proprio/locataire/{id}",
        summary: "Versements d'un locataire",
        description: "Retourne les versements liés à un locataire spécifique.",
        tags: ['VersmtProprio']
    )]
    public function getByLocataire(VersmtProprioRepository $repository, int $id): Response
    {
        try {
            $versements = $repository->findAllByLocataire($id);
            return $this->responseData($versements, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/versement-proprio/create",
        summary: "Créer un versement",
        description: "Enregistre un nouveau versement.",
        tags: ['VersmtProprio']
    )]
    public function create(Request $request, VersmtProprioRepository $repository, LocataireRepository $locataireRepository, ProprioRepository $proprioRepository, MaisonRepository $maisonRepository, TypeVersementsRepository $typeRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) $data = $request->request->all();

            $versement = new VersmtProprio();
            
            if (isset($data['locataire_id']) && $data['locataire_id']) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if ($locataire) $versement->setLocataire($locataire);
            }
            
            if (isset($data['proprio_id']) && $data['proprio_id']) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
                $versement->setProprio($proprio);
            } else {
                return $this->errorResponse(null, "Le propriétaire est requis", 400);
            }

            if (isset($data['maison_id']) && $data['maison_id']) {
                $maison = $maisonRepository->find($data['maison_id']);
                if ($maison) $versement->setMaison($maison);
            }

            if (isset($data['type_versement_id']) && $data['type_versement_id']) {
                $type = $typeRepository->find($data['type_versement_id']);
                if ($type) $versement->setTypeVersement($type);
            }

            if (!isset($data['libelle']) || !$data['libelle']) return $this->errorResponse(null, "Libellé requis", 400);
            if (!isset($data['montant']) || !$data['montant']) return $this->errorResponse(null, "Montant requis", 400);

            $versement->setLibelle($data['libelle']);
            $versement->setMontant($data['montant']);
            $versement->setNumero($data['numero'] ?? 'VP-'.time());
            
            $date = isset($data['dateVersement']) ? new \DateTime($data['dateVersement']) : new \DateTime();
            $versement->setDateVersement($date);

            $this->updateAuditFields($versement, true);
            $repository->save($versement, true);

            return $this->responseData($versement, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/versement-proprio/{id}",
        summary: "Modifier un versement",
        description: "Met à jour un versement existant.",
        tags: ['VersmtProprio']
    )]
    public function update(Request $request, VersmtProprio $versement, VersmtProprioRepository $repository, LocataireRepository $locataireRepository, ProprioRepository $proprioRepository, MaisonRepository $maisonRepository, TypeVersementsRepository $typeRepository): Response
    {
        try {
            if (!$versement) return $this->errorResponse(null, "Versement non trouvé", 404);

            $data = json_decode($request->getContent(), true);

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if ($locataire) $versement->setLocataire($locataire);
            }
            if (isset($data['proprio_id'])) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if ($proprio) $versement->setProprio($proprio);
            }
            if (isset($data['maison_id'])) {
                $maison = $maisonRepository->find($data['maison_id']);
                if ($maison) $versement->setMaison($maison);
            }
            if (isset($data['type_versement_id'])) {
                $type = $typeRepository->find($data['type_versement_id']);
                if ($type) $versement->setTypeVersement($type);
            }

            if (isset($data['libelle'])) $versement->setLibelle($data['libelle']);
            if (isset($data['montant'])) $versement->setMontant($data['montant']);
            if (isset($data['numero'])) $versement->setNumero($data['numero']);
            if (isset($data['dateVersement'])) $versement->setDateVersement(new \DateTime($data['dateVersement']));

            $this->updateAuditFields($versement);
            $repository->save($versement, true);

            return $this->responseData($versement, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/versement-proprio/{id}",
        summary: "Supprimer un versement",
        description: "Supprime un versement.",
        tags: ['VersmtProprio']
    )]
    public function delete(VersmtProprio $versement, VersmtProprioRepository $repository): Response
    {
        try {
             if (!$versement) return $this->errorResponse(null, "Versement non trouvé", 404);
            $repository->remove($versement, true);
            return $this->response(['message' => 'Versement supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
