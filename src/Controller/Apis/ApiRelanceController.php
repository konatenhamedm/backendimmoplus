<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Relance;
use App\Repository\FactureLocationRepository;
use App\Repository\RelanceRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/relances')]
#[OA\Tag(name: 'Relance', description: 'Gestion des relances locataires')]
class ApiRelanceController extends ApiInterface
{
    #[Route('/', methods: ['POST'])]
    #[OA\Post(
        path: "/api/relances/",
        summary: "Enregistrer une relance",
        description: "Enregistre une action de relance effectuée auprès d'un locataire.",
        tags: ['Relance']
    )]
    public function create(Request $request, FactureLocationRepository $factureRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $user = $this->getUser();
            
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            if (!isset($data['facture_id']) || !isset($data['type'])) {
                return $this->errorResponse(null, "Données manquantes (facture_id, type)", 400);
            }

            $facture = $factureRepository->find($data['facture_id']);
            if (!$facture) {
                return $this->errorResponse(null, "Facture non trouvée", 404);
            }

            $relance = new Relance();
            $relance->setFacture($facture);
            $relance->setType($data['type']);
            $relance->setObservation($data['observation'] ?? null);
            $relance->setAgent($user);
            $relance->setEntreprise($user->getEntreprise());
            $relance->setAgence($user->getAgence());
            $relance->setDateEffective(new \DateTime());
            
            $this->em->persist($relance);
            $this->em->flush();

            return $this->responseData($relance, 'group1_relance');
        } catch (\Exception $exception) {
            return $this->errorResponse(null, $exception->getMessage(), 500);
        }
    }

    #[Route('/bulk', methods: ['POST'])]
    #[OA\Post(
        path: "/api/relances/bulk",
        summary: "Enregistrer plusieurs relances",
        tags: ['Relance']
    )]
    public function bulkCreate(Request $request, FactureLocationRepository $factureRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $user = $this->getUser();
            
            if (!$user || !isset($data['facture_ids']) || !isset($data['type'])) {
                return $this->errorResponse(null, "Données invalides", 400);
            }

            foreach ($data['facture_ids'] as $id) {
                $facture = $factureRepository->find($id);
                if ($facture) {
                    $relance = new Relance();
                    $relance->setFacture($facture);
                    $relance->setType($data['type']);
                    $relance->setObservation($data['observation'] ?? "Relance groupée");
                    $relance->setAgent($user);
                    $relance->setEntreprise($user->getEntreprise());
                    $relance->setAgence($user->getAgence());
                    $relance->setDateEffective(new \DateTime());
                    $this->em->persist($relance);
                }
            }
            
            $this->em->flush();

            return $this->response(['message' => 'Relances groupées enregistrées']);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/send-email', methods: ['POST'])]
    #[OA\Post(
        path: "/api/relances/send-email",
        summary: "Envoyer un email personnalisé et enregistrer la relance",
        tags: ['Relance']
    )]
    public function sendEmail(Request $request, FactureLocationRepository $factureRepository, \App\Service\SendMailService $mailService): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $user = $this->getUser();
            
            if (!$user || !isset($data['facture_id']) || !isset($data['to']) || !isset($data['subject']) || !isset($data['content'])) {
                return $this->errorResponse(null, "Données manquantes", 400);
            }

            $facture = $factureRepository->find($data['facture_id']);
            if (!$facture) return $this->errorResponse(null, "Facture non trouvée", 404);

            // 1. Envoyer le mail réel
            $from = $user->getEntreprise()?->getEmail() ?: 'noreply@immoplus.pro';
            try {
                $mailService->sendCustom(
                    $from,
                    $data['to'],
                    $data['subject'],
                    $data['content'],
                    $data['cc'] ?? null
                );
            } catch (\Exception $e) {
                return $this->errorResponse(null, "Échec de l'envoi de l'email : " . $e->getMessage(), 500);
            }

            // 2. Enregistrer la relance
            $relance = new Relance();
            $relance->setFacture($facture);
            $relance->setType('EMAIL');
            $relance->setObservation("Email envoyé à {$data['to']}. Objet: {$data['subject']}");
            $relance->setAgent($user);
            $relance->setEntreprise($user->getEntreprise());
            $relance->setAgence($user->getAgence());
            $relance->setDateEffective(new \DateTime());
            
            $this->em->persist($relance);
            $this->em->flush();

            return $this->response(['message' => 'Email envoyé et relance enregistrée']);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/facture/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/relances/facture/{id}",
        summary: "Historique des relances d'une facture",
        tags: ['Relance']
    )]
    public function getByFacture(int $id, RelanceRepository $repository): Response
    {
        try {
            $relances = $repository->findBy(['facture' => $id], ['dateEffective' => 'DESC']);
            return $this->responseData($relances, 'group1_relance');
        } catch (\Exception $exception) {
            return $this->errorResponse(null, $exception->getMessage(), 500);
        }
    }
}
