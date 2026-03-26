<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\PlanningRecouvrement;
use App\Repository\LocataireRepository;
use App\Repository\PlanningRecouvrementRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/planning-recouvrement')]
#[OA\Tag(name: 'PlanningRecouvrement', description: 'Agenda des recouvrements terrain')]
class ApiPlanningRecouvrementController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(summary: "Lister les RDV", tags: ['PlanningRecouvrement'])]
    public function index(PlanningRecouvrementRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non authentifié", 401);

            if ($user->getEntreprise()) {
                $plannings = $repository->findAllByEntreprise($user->getEntreprise());
            } else {
                $plannings = $repository->findAll();
            }

            return $this->responseData($plannings, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(summary: "Créer un RDV", tags: ['PlanningRecouvrement'])]
    public function create(Request $request, PlanningRecouvrementRepository $repository, LocataireRepository $locataireRepository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non authentifié", 401);

            $data = json_decode($request->getContent(), true);
            $planning = new PlanningRecouvrement();

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $planning->setLocataire($locataire);
            }

            $planning->setAgent($user);
            $planning->setEntreprise($user->getEntreprise());
            if (isset($data['titre'])) $planning->setTitre($data['titre']);
            if (isset($data['details'])) $planning->setDetails($data['details']);
            if (isset($data['startDate'])) $planning->setStartDate(new \DateTime($data['startDate']));
            if (isset($data['endDate'])) $planning->setEndDate(new \DateTime($data['endDate']));
            if (isset($data['location'])) $planning->setLocation($data['location']);
            if (isset($data['status'])) $planning->setStatus($data['status']);

            $repository->save($planning, true);

            return $this->responseData($planning, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(summary: "Modifier un RDV", tags: ['PlanningRecouvrement'])]
    public function update(Request $request, PlanningRecouvrement $planning, PlanningRecouvrementRepository $repository): Response
    {
        try {
            if (!$planning) return $this->errorResponse(null, "RDV non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            if (isset($data['titre'])) $planning->setTitre($data['titre']);
            if (isset($data['details'])) $planning->setDetails($data['details']);
            if (isset($data['startDate'])) $planning->setStartDate(new \DateTime($data['startDate']));
            if (isset($data['endDate'])) $planning->setEndDate(new \DateTime($data['endDate']));
            if (isset($data['location'])) $planning->setLocation($data['location']);
            if (isset($data['status'])) $planning->setStatus($data['status']);

            $repository->save($planning, true);

            return $this->responseData($planning, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(summary: "Supprimer un RDV", tags: ['PlanningRecouvrement'])]
    public function delete(PlanningRecouvrement $planning, PlanningRecouvrementRepository $repository): Response
    {
        try {
            if (!$planning) return $this->errorResponse(null, "RDV non trouvé", 404);
            $repository->remove($planning, true);
            return $this->response(['message' => 'RDV supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
