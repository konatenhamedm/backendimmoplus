<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ContratLocation;
use App\Entity\ModeleRelance;
use App\Repository\ModeleRelanceRepository;
use App\Service\RelanceService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/modele-relance')]
#[OA\Tag(name: 'Relance', description: 'Gestion des relances locataires')]
class ApiModeleRelanceController extends ApiInterface
{
    private const CHAMPS_TEXTE = ['rappelSujet', 'rappelMessage', 'rappelSms', 'relanceSujet', 'relanceMessage', 'relanceSms'];

    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/modele-relance/",
        summary: "Modèles de relance de l'agence",
        description: "Liste les modèles de l'agence (le modèle par défaut est créé s'il n'existe pas), avec les variables utilisables.",
        tags: ['Relance']
    )]
    #[OA\Parameter(name: "agence_id", in: "query", schema: new OA\Schema(type: "integer"))]
    public function index(Request $request, RelanceService $relanceService, ModeleRelanceRepository $repository): Response
    {
        try {
            $agence = $relanceService->resolveAgence($this->getUser(), $request->query->get('agence_id'));
            if (!$agence) {
                return $this->errorResponse(null, "Agence introuvable", 404);
            }

            $relanceService->getModeleParDefaut($agence);
            $modeles = $repository->findByAgence($agence);

            $nbContrats = [];
            foreach ($this->em->createQueryBuilder()
                ->select('IDENTITY(c.modeleRelance) AS modele_id', 'COUNT(c.id) AS nb')
                ->from(ContratLocation::class, 'c')
                ->where('c.modeleRelance IN (:modeles)')
                ->setParameter('modeles', $modeles)
                ->groupBy('c.modeleRelance')
                ->getQuery()
                ->getArrayResult() as $ligne) {
                $nbContrats[(int) $ligne['modele_id']] = (int) $ligne['nb'];
            }

            return $this->response([
                'modeles' => array_map(fn (ModeleRelance $m) => $this->serialize($m, $nbContrats[$m->getId()] ?? 0), $modeles),
                'variables' => RelanceService::VARIABLES,
                'defauts' => [
                    'rappelSujet' => ModeleRelance::DEFAULT_RAPPEL_SUJET,
                    'rappelMessage' => ModeleRelance::DEFAULT_RAPPEL_MESSAGE,
                    'rappelSms' => ModeleRelance::DEFAULT_RAPPEL_SMS,
                    'relanceSujet' => ModeleRelance::DEFAULT_RELANCE_SUJET,
                    'relanceMessage' => ModeleRelance::DEFAULT_RELANCE_MESSAGE,
                    'relanceSms' => ModeleRelance::DEFAULT_RELANCE_SMS,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(path: "/api/modele-relance/create", summary: "Créer un modèle de relance", tags: ['Relance'])]
    public function create(Request $request, RelanceService $relanceService): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $agence = $relanceService->resolveAgence($this->getUser(), $data['agence_id'] ?? null);
            if (!$agence) {
                return $this->errorResponse(null, "Agence introuvable", 404);
            }
            if (trim($data['libelle'] ?? '') === '') {
                return $this->errorResponse(null, "Le nom du modèle est requis", 400);
            }

            $modele = (new ModeleRelance())->setAgence($agence)->setEntreprise($agence->getEntreprise());
            $this->hydrate($modele, $data);
            $this->updateAuditFields($modele, true);
            $this->em->persist($modele);
            $this->em->flush();

            return $this->response($this->serialize($modele));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'], requirements: ['id' => '\d+'])]
    #[OA\Put(path: "/api/modele-relance/{id}", summary: "Modifier un modèle de relance", tags: ['Relance'])]
    public function update(int $id, Request $request, ModeleRelanceRepository $repository): Response
    {
        try {
            $modele = $this->findModele($id, $repository);
            if (!$modele) {
                return $this->errorResponse(null, "Modèle introuvable", 404);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            if (array_key_exists('actif', $data) && !$data['actif'] && $modele->isParDefaut()) {
                return $this->errorResponse(null, "Le modèle par défaut ne peut pas être désactivé", 400);
            }

            $this->hydrate($modele, $data);
            $this->updateAuditFields($modele);
            $this->em->flush();

            return $this->response($this->serialize($modele));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/{id}/defaut', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[OA\Post(
        path: "/api/modele-relance/{id}/defaut",
        summary: "Définir le modèle par défaut de l'agence",
        description: "Ce modèle sera utilisé pour tous les contrats de l'agence qui n'ont pas de modèle propre.",
        tags: ['Relance']
    )]
    public function setDefaut(int $id, ModeleRelanceRepository $repository): Response
    {
        try {
            $modele = $this->findModele($id, $repository);
            if (!$modele) {
                return $this->errorResponse(null, "Modèle introuvable", 404);
            }

            $repository->findDefaut($modele->getAgence())?->setParDefaut(false);
            $modele->setParDefaut(true)->setActif(true);
            $this->em->flush();

            return $this->response($this->serialize($modele));
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: "/api/modele-relance/{id}",
        summary: "Supprimer un modèle de relance",
        description: "Les contrats liés à ce modèle repassent sur le modèle par défaut de l'agence.",
        tags: ['Relance']
    )]
    public function delete(int $id, ModeleRelanceRepository $repository): Response
    {
        try {
            $modele = $this->findModele($id, $repository);
            if (!$modele) {
                return $this->errorResponse(null, "Modèle introuvable", 404);
            }
            if ($modele->isParDefaut()) {
                return $this->errorResponse(null, "Le modèle par défaut ne peut pas être supprimé : définissez d'abord un autre modèle par défaut", 400);
            }

            $this->em->remove($modele);
            $this->em->flush();

            return $this->response(['message' => 'Modèle supprimé']);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }

    private function findModele(int $id, ModeleRelanceRepository $repository): ?ModeleRelance
    {
        $modele = $repository->find($id);
        $entreprise = $this->getUser()?->getEntreprise();

        return $modele && $entreprise && $modele->getAgence()->getEntreprise() === $entreprise ? $modele : null;
    }

    private function hydrate(ModeleRelance $modele, array $data): void
    {
        if (trim($data['libelle'] ?? '') !== '') {
            $modele->setLibelle(mb_substr(trim($data['libelle']), 0, 150));
        }
        if (array_key_exists('actif', $data)) {
            $modele->setActif((bool) $data['actif']);
        }
        foreach (self::CHAMPS_TEXTE as $champ) {
            if (isset($data[$champ]) && trim($data[$champ]) !== '') {
                $modele->{'set' . ucfirst($champ)}(trim($data[$champ]));
            }
        }
    }

    private function serialize(ModeleRelance $m, ?int $nbContrats = null): array
    {
        return [
            'id' => $m->getId(),
            'libelle' => $m->getLibelle(),
            'parDefaut' => $m->isParDefaut(),
            'actif' => $m->isActif(),
            'rappelSujet' => $m->getRappelSujet(),
            'rappelMessage' => $m->getRappelMessage(),
            'rappelSms' => $m->getRappelSms(),
            'relanceSujet' => $m->getRelanceSujet(),
            'relanceMessage' => $m->getRelanceMessage(),
            'relanceSms' => $m->getRelanceSms(),
            'nbContrats' => $nbContrats,
        ];
    }
}
