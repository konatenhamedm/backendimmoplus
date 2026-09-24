<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Agence;
use App\Entity\ParametrePenalite;
use App\Service\PenaliteService;
use App\Service\RelanceService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/parametre-penalite')]
#[OA\Tag(name: 'Penalite', description: 'Pénalités de retard sur les factures de loyer')]
class ApiParametrePenaliteController extends ApiInterface
{
    private const GROUPES_ADMIN = ['SADM', 'ADMIN', 'ADMINAG'];

    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/parametre-penalite/",
        summary: "Pénalités de retard de l'agence",
        description: "Réglages (fixe ou pourcentage, délai de grâce, récurrence mensuelle, plafond) et exemple de calcul.",
        tags: ['Penalite']
    )]
    #[OA\Parameter(name: "agence_id", in: "query", schema: new OA\Schema(type: "integer"))]
    public function show(Request $request, RelanceService $relanceService, PenaliteService $penalites): Response
    {
        try {
            $agence = $relanceService->resolveAgence($this->getUser(), $request->query->get('agence_id'));
            if (!$agence) {
                return $this->errorResponse(null, "Choisissez une agence", 404);
            }

            return $this->response($this->serialize($penalites->getParametres($agence), $penalites));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/bilan', methods: ['GET'])]
    #[OA\Get(
        path: "/api/parametre-penalite/bilan",
        summary: "Pénalités appliquées sur une année, mois par mois",
        description: "Pour l'agence agence_id, ou pour toute l'entreprise (administrateurs) sans agence_id.",
        tags: ['Penalite']
    )]
    #[OA\Parameter(name: "agence_id", in: "query", schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "annee", in: "query", schema: new OA\Schema(type: "integer"))]
    public function bilan(Request $request, RelanceService $relanceService, PenaliteService $penalites): Response
    {
        try {
            $user = $this->getUser();
            if (!$user?->getEntreprise()) {
                return $this->errorResponse(null, "Accès refusé", 403);
            }

            $agenceId = $request->query->get('agence_id');
            $global = in_array($user->getGroupe()?->getCode(), self::GROUPES_ADMIN, true) && in_array($agenceId, [null, '', 'all', 'null'], true);
            $agence = $global ? null : $relanceService->resolveAgence($user, $agenceId);
            if (!$global && !$agence) {
                return $this->errorResponse(null, "Choisissez une agence", 404);
            }

            $annee = (int) ($request->query->get('annee') ?: date('Y'));

            return $this->response($penalites->bilan($user->getEntreprise(), $agence, $annee));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/parametre-penalite/",
        summary: "Enregistrer les pénalités de retard",
        description: "Pour l'agence agence_id, ou pour toutes les agences de l'entreprise avec toutes = true. Les pénalités dues sont appliquées immédiatement.",
        tags: ['Penalite']
    )]
    public function save(Request $request, RelanceService $relanceService, PenaliteService $penalites): Response
    {
        try {
            $user = $this->getUser();
            if (!in_array($user?->getGroupe()?->getCode(), self::GROUPES_ADMIN, true)) {
                return $this->errorResponse(null, "Réservé aux administrateurs", 403);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            if (!empty($data['actif']) && (float) ($data['valeur'] ?? 0) <= 0) {
                return $this->errorResponse(null, "Indiquez le montant ou le pourcentage de la pénalité", 400);
            }
            if (($data['type'] ?? null) === ParametrePenalite::TYPE_POURCENTAGE && (float) ($data['valeur'] ?? 0) > 100) {
                return $this->errorResponse(null, "Le pourcentage ne peut pas dépasser 100 %", 400);
            }

            if (!empty($data['toutes'])) {
                $agences = $this->em->getRepository(Agence::class)->findBy(['entreprise' => $user->getEntreprise()]);
            } else {
                $agence = $relanceService->resolveAgence($user, $data['agence_id'] ?? null);
                $agences = $agence ? [$agence] : [];
            }
            if (!$agences) {
                return $this->errorResponse(null, "Choisissez une agence", 404);
            }

            $applique = ['factures' => 0, 'montant' => 0];
            $dernier = null;
            foreach ($agences as $agence) {
                $p = $penalites->getParametres($agence);
                $isNew = $p->getId() === null;
                if (array_key_exists('actif', $data)) $p->setActif((bool) $data['actif']);
                if (isset($data['type'])) $p->setType($data['type']);
                if (isset($data['valeur'])) $p->setValeur((float) $data['valeur']);
                if (isset($data['delaiGrace'])) $p->setDelaiGrace((int) $data['delaiGrace']);
                if (array_key_exists('recurrenceMensuelle', $data)) $p->setRecurrenceMensuelle((bool) $data['recurrenceMensuelle']);
                if (array_key_exists('plafond', $data)) $p->setPlafond($data['plafond'] !== null ? (int) $data['plafond'] : null);
                $this->updateAuditFields($p, $isNew);
                $this->em->persist($p);
                $this->em->flush();

                $r = $penalites->appliquer($p);
                $applique['factures'] += $r['factures'];
                $applique['montant'] += $r['montant'];
                $dernier = $p;
            }

            return $this->response($this->serialize($dernier, $penalites) + ['applique' => $applique, 'nbAgences' => count($agences)]);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    private function serialize(ParametrePenalite $p, PenaliteService $penalites): array
    {
        return [
            'agence_id' => $p->getAgence()?->getId(),
            'actif' => $p->isActif(),
            'type' => $p->getType(),
            'valeur' => $p->getValeur(),
            'delaiGrace' => $p->getDelaiGrace(),
            'recurrenceMensuelle' => $p->isRecurrenceMensuelle(),
            'plafond' => $p->getPlafond(),
            'exemple' => $penalites->exemple($p),
        ];
    }
}
