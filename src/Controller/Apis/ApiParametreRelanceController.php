<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ParametreRelance;
use App\Service\RelanceService;
use App\Service\Sms\SmsService;
use App\Service\SubscriptionService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/parametre-relance')]
#[OA\Tag(name: 'Relance', description: 'Gestion des relances locataires')]
class ApiParametreRelanceController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/parametre-relance/",
        summary: "Paramètres de relance de l'agence",
        description: "Mode (automatique / manuel), canaux et délais de l'agence active, avec l'état de l'offre SMS de l'abonnement.",
        tags: ['Relance']
    )]
    #[OA\Parameter(name: "agence_id", in: "query", schema: new OA\Schema(type: "integer"))]
    public function show(Request $request, RelanceService $relanceService, SubscriptionService $subscriptionService, SmsService $smsService): Response
    {
        try {
            $agence = $relanceService->resolveAgence($this->getUser(), $request->query->get('agence_id'));
            if (!$agence) {
                return $this->errorResponse(null, "Agence introuvable", 404);
            }

            return $this->response($this->serialize($relanceService->getParametres($agence), $subscriptionService, $smsService));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/parametre-relance/",
        summary: "Enregistrer les paramètres de relance de l'agence",
        tags: ['Relance']
    )]
    public function save(Request $request, RelanceService $relanceService, SubscriptionService $subscriptionService, SmsService $smsService): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $agence = $relanceService->resolveAgence($this->getUser(), $data['agence_id'] ?? null);
            if (!$agence) {
                return $this->errorResponse(null, "Agence introuvable", 404);
            }

            $parametres = $relanceService->getParametres($agence);
            $isNew = $parametres->getId() === null;

            if (isset($data['mode'])) $parametres->setMode($data['mode']);
            if (isset($data['canaux']) && is_array($data['canaux'])) $parametres->setCanaux($data['canaux']);
            if (isset($data['rappelActif'])) $parametres->setRappelActif((bool) $data['rappelActif']);
            if (isset($data['joursAvantEcheance'])) $parametres->setJoursAvantEcheance((int) $data['joursAvantEcheance']);
            if (isset($data['relanceActif'])) $parametres->setRelanceActif((bool) $data['relanceActif']);
            if (isset($data['joursApresEcheance']) && is_array($data['joursApresEcheance'])) $parametres->setJoursApresEcheance($data['joursApresEcheance']);

            if ($parametres->isAutomatique() && !$subscriptionService->hasRelancesAuto($agence->getEntreprise())) {
                return $this->errorResponse(null, "Les relances automatiques ne sont pas incluses dans votre abonnement", 403);
            }
            if ($parametres->isAutomatique() && !$parametres->getCanaux()) {
                return $this->errorResponse(null, "Choisissez au moins un canal d'envoi pour le mode automatique", 400);
            }
            if ($parametres->isRelanceActif() && !$parametres->getJoursApresEcheance()) {
                return $this->errorResponse(null, "Ajoutez au moins un palier de relance (jours après l'échéance)", 400);
            }

            $this->updateAuditFields($parametres, $isNew);
            $this->em->persist($parametres);
            $this->em->flush();

            return $this->response($this->serialize($parametres, $subscriptionService, $smsService));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    private function serialize(ParametreRelance $p, SubscriptionService $subscriptionService, SmsService $smsService): array
    {
        $entreprise = $p->getAgence()->getEntreprise();

        return [
            'id' => $p->getId(),
            'agence_id' => $p->getAgence()->getId(),
            'mode' => $p->getMode(),
            'canaux' => $p->getCanaux(),
            'rappelActif' => $p->isRappelActif(),
            'joursAvantEcheance' => $p->getJoursAvantEcheance(),
            'relanceActif' => $p->isRelanceActif(),
            'joursApresEcheance' => $p->getJoursApresEcheance(),
            'automatiqueAutorise' => $entreprise && $subscriptionService->hasRelancesAuto($entreprise),
            'sms' => $entreprise ? $smsService->getOffre($entreprise) : null,
        ];
    }
}
