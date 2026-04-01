<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ReservationResidence;
use App\Entity\Residence;
use App\Repository\ReservationResidenceRepository;
use App\Repository\ResidenceRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reservation-residence')]
#[OA\Tag(name: 'ReservationResidence', description: 'Réservations de résidences')]
class ApiReservationResidenceController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, ReservationResidenceRepository $repo): Response
    {
        try {
            $withPagination = $request->query->get('with_pagination', "false");
            $qb = $repo->createQueryBuilder('r')
                ->leftJoin('r.residence', 'res');

            if ($request->query->get('agence_id')) {
                $qb->andWhere('res.agence = :aid')->setParameter('aid', (int)$request->query->get('agence_id'));
            }

            if ($request->query->get('residence_id')) {
                $qb->andWhere('res.id = :rid')->setParameter('rid', (int)$request->query->get('residence_id'));
            }

            if ($request->query->get('etat')) {
                $qb->andWhere('r.etat = :etat')->setParameter('etat', $request->query->get('etat'));
            }

            $qb->orderBy('r.dateDebut', 'DESC');

            if ($withPagination === "true") {
                $reservations = $this->paginationService->paginate($qb);
                return $this->responseData($reservations, ['group1', 'group2'], [], $withPagination == "true" ? true : false);
            }

            $reservations = $qb->getQuery()->getResult();
            return $this->responseData($reservations, ['group1', 'group2']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Endpoint de vérification de disponibilité sans créer de réservation */
    #[Route('/disponibilite', methods: ['GET'])]
    public function disponibilite(Request $request, ReservationResidenceRepository $repo): Response
    {
        try {
            $residenceId = (int) $request->query->get('residence_id');
            $dateDebut   = \DateTime::createFromFormat('Y-m-d', $request->query->get('date_debut'));
            $dateFin     = \DateTime::createFromFormat('Y-m-d', $request->query->get('date_fin'));
            $excludeId   = $request->query->get('exclude_id') ? (int)$request->query->get('exclude_id') : null;

            if (!$residenceId || !$dateDebut || !$dateFin) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Paramètres manquants: residence_id, date_debut, date_fin']);
            }

            if ($dateDebut >= $dateFin) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'La date de début doit être antérieure à la date de fin']);
            }

            $conflicts = $repo->findConflictingPeriods($residenceId, $dateDebut, $dateFin, $excludeId);

            return $this->response([
                'disponible' => count($conflicts) === 0,
                'conflits'   => array_map(fn($c) => [
                    'id'        => $c->getId(),
                    'debut'     => $c->getDateDebut()?->format('Y-m-d'),
                    'fin'       => $c->getDateFin()?->format('Y-m-d'),
                    'locataire' => $c->getNomLocataire().' '.$c->getPrenomLocataire(),
                    'etat'      => $c->getEtat(),
                ], $conflicts),
            ]);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(
        Request $request,
        ReservationResidenceRepository $repo,
        ResidenceRepository $residenceRepo
    ): Response {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);
            $user = $this->getUser();

            if (empty($data['residence_id']) || empty($data['nomLocataire']) || empty($data['dateDebut']) || empty($data['dateFin'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Champs requis: residence_id, nomLocataire, dateDebut, dateFin']);
            }

            $residence = $residenceRepo->find($data['residence_id']);
            if (!$residence) {
                $this->setStatusCode(404);
                return $this->response(['message' => 'Résidence introuvable']);
            }

            $debut = \DateTime::createFromFormat('Y-m-d', $data['dateDebut']);
            $fin   = \DateTime::createFromFormat('Y-m-d', $data['dateFin']);

            if ($debut >= $fin) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'La date de début doit être antérieure à la date de fin']);
            }

            // ── Vérification conflits de période ──────────────────────────
            $conflicts = $repo->findConflictingPeriods($residence->getId(), $debut, $fin);
            if (count($conflicts) > 0) {
                $this->setStatusCode(409);
                return $this->response([
                    'message' => 'La résidence est déjà réservée sur une partie de cette période.',
                    'conflits' => array_map(fn($c) => [
                        'debut' => $c->getDateDebut()?->format('d/m/Y'),
                        'fin'   => $c->getDateFin()?->format('d/m/Y'),
                        'nom'   => $c->getNomLocataire().' '.$c->getPrenomLocataire(),
                    ], $conflicts),
                ]);
            }

            $reservation = new ReservationResidence();
            $reservation->setResidence($residence);
            $reservation->setNomLocataire($data['nomLocataire']);
            $reservation->setPrenomLocataire($data['prenomLocataire'] ?? '');
            $reservation->setTelephone($data['telephone'] ?? null);
            $reservation->setEmail($data['email'] ?? null);
            $reservation->setDateDebut($debut);
            $reservation->setDateFin($fin);
            $reservation->setMontant((int)($data['montant'] ?? 0));
            $reservation->setEtat($data['etat'] ?? 'EN_ATTENTE');
            $reservation->setNotes($data['notes'] ?? null);

            // Upload carte d'identité
            $ciFile = $request->files->get('carteIdentite');
            if ($ciFile) {
                $prefix  = $this->slugger->slug('ci_' . uniqid());
                $dirPath = $this->getUploadDir('cartes_identite', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $ciFile, 'cartes_identite')) {
                    $reservation->setCarteIdentite($fichier);
                }
            }

            $this->updateAuditFields($reservation, true);
            $repo->save($reservation, true);

            // Mettre à jour l'état de la résidence si confirmée
            if ($reservation->getEtat() === 'CONFIRMEE') {
                $residence->setEtat('OCCUPEE');
                $residenceRepo->save($residence, true);
            }

            return $this->responseData($reservation, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(ReservationResidence $reservation): Response
    {
        try {
            return $this->responseData($reservation, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(
        Request $request,
        ReservationResidence $reservation,
        ReservationResidenceRepository $repo,
        ResidenceRepository $residenceRepo
    ): Response {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (!empty($data['nomLocataire'])) $reservation->setNomLocataire($data['nomLocataire']);
            if (array_key_exists('prenomLocataire', $data)) $reservation->setPrenomLocataire($data['prenomLocataire']);
            if (array_key_exists('telephone', $data)) $reservation->setTelephone($data['telephone']);
            if (array_key_exists('email', $data)) $reservation->setEmail($data['email']);
            if (array_key_exists('notes', $data)) $reservation->setNotes($data['notes']);
            if (!empty($data['montant'])) $reservation->setMontant((int)$data['montant']);

            if (!empty($data['dateDebut']) && !empty($data['dateFin'])) {
                $debut = \DateTime::createFromFormat('Y-m-d', $data['dateDebut']);
                $fin   = \DateTime::createFromFormat('Y-m-d', $data['dateFin']);
                if ($debut && $fin && $debut < $fin) {
                    $conflicts = $repo->findConflictingPeriods($reservation->getResidence()->getId(), $debut, $fin, $reservation->getId());
                    if (count($conflicts) > 0) {
                        $this->setStatusCode(409);
                        return $this->response(['message' => 'Conflit de période détecté.']);
                    }
                    $reservation->setDateDebut($debut);
                    $reservation->setDateFin($fin);
                }
            }

            if (!empty($data['etat'])) {
                $oldEtat = $reservation->getEtat();
                $reservation->setEtat($data['etat']);
                // Libérer la résidence si annulée ou terminée
                $residence = $reservation->getResidence();
                if (in_array($data['etat'], ['ANNULEE', 'TERMINEE']) && in_array($oldEtat, ['CONFIRMEE'])) {
                    $residence->setEtat('DISPONIBLE');
                    $residenceRepo->save($residence, true);
                } elseif ($data['etat'] === 'CONFIRMEE') {
                    $residence->setEtat('OCCUPEE');
                    $residenceRepo->save($residence, true);
                }
            }

            // Upload CI
            $ciFile = $request->files->get('carteIdentite');
            if ($ciFile) {
                $prefix  = $this->slugger->slug('ci_' . uniqid());
                $dirPath = $this->getUploadDir('cartes_identite', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $ciFile, 'cartes_identite')) {
                    $reservation->setCarteIdentite($fichier);
                }
            }

            $this->updateAuditFields($reservation);
            $repo->save($reservation, true);

            return $this->responseData($reservation, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(ReservationResidence $reservation, ReservationResidenceRepository $repo): Response
    {
        try {
            $repo->remove($reservation, true);
            return $this->response(['message' => 'Réservation supprimée']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }
}
