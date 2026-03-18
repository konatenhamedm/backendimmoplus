<?php

namespace App\Controller\Apis;

use App\Entity\ContratLocation;
use App\Entity\EtatLieux;
use App\Repository\ContratLocationRepository;
use App\Repository\EtatLieuxRepository;
use App\Controller\Apis\Config\ApiInterface;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/etat-lieux')]
#[OA\Tag(name: 'EtatLieux')]
class ApiEtatLieuxController extends ApiInterface
{

    #[Route('/contrat/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/etat-lieux/contrat/{id}",
        summary: "Récupère les états des lieux d'un contrat",
        tags: ['EtatLieux']
    )]
    public function getByContrat(ContratLocation $contrat, EtatLieuxRepository $repository): Response
    {
        try {
            $etats = $repository->findBy(['contratLocation' => $contrat], ['dateEtatLieux' => 'DESC']);
            return $this->responseData($etats, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/etat-lieux/{id}",
        summary: "Détails d'un état des lieux",
        tags: ['EtatLieux']
    )]
    public function show(EtatLieux $etatLieux): Response
    {
        return $this->responseData($etatLieux, 'group1');
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/etat-lieux/create",
        summary: "Créer un état des lieux",
        tags: ['EtatLieux']
    )]
    public function create(Request $request, ContratLocationRepository $contratRepo): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            if (!isset($data['contratLocation_id'])) {
                return $this->errorResponse(null, "L'ID du contrat est requis", 400);
            }

            $contrat = $contratRepo->find($data['contratLocation_id']);
            if (!$contrat) {
                return $this->errorResponse(null, "Contrat de location introuvable", 404);
            }

            $etatLieux = new EtatLieux();
            $etatLieux->setContratLocation($contrat);
            
            if (isset($data['dateEtatLieux'])) {
                $etatLieux->setDateEtatLieux(new \DateTime($data['dateEtatLieux']));
            } else {
                $etatLieux->setDateEtatLieux(new \DateTime());
            }

            if (isset($data['type'])) $etatLieux->setType($data['type']);
            if (isset($data['compteurs'])) $etatLieux->setCompteurs($data['compteurs']);
            if (isset($data['cles'])) $etatLieux->setCles($data['cles']);
            if (isset($data['pieces'])) $etatLieux->setPieces($data['pieces']);
            if (isset($data['observations'])) $etatLieux->setObservations($data['observations']);

            $this->em->persist($etatLieux);
            $this->em->flush();

            return $this->responseData($etatLieux, 'group1', ['message' => 'État des lieux enregistré avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    #[OA\Post(
        path: "/api/etat-lieux/{id}",
        summary: "Modifier un état des lieux",
        tags: ['EtatLieux']
    )]
    public function update(Request $request, EtatLieux $etatLieux): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            if (isset($data['dateEtatLieux'])) {
                $etatLieux->setDateEtatLieux(new \DateTime($data['dateEtatLieux']));
            }
            if (isset($data['type'])) $etatLieux->setType($data['type']);
            if (isset($data['compteurs'])) $etatLieux->setCompteurs($data['compteurs']);
            if (isset($data['cles'])) $etatLieux->setCles($data['cles']);
            if (isset($data['pieces'])) $etatLieux->setPieces($data['pieces']);
            if (isset($data['observations'])) $etatLieux->setObservations($data['observations']);

            $this->em->flush();

            return $this->responseData($etatLieux, 'group1', ['message' => 'État des lieux modifié avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/etat-lieux/{id}",
        summary: "Supprimer un état des lieux",
        tags: ['EtatLieux']
    )]
    public function delete(EtatLieux $etatLieux): Response
    {
        try {
            $this->em->remove($etatLieux);
            $this->em->flush();
            return $this->response(['message' => 'Opération succès']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => 'Impossible de supprimer cet enregistrement']);
        }
    }
}
