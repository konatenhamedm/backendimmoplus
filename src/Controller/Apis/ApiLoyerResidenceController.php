<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\LoyerResidence;
use App\Repository\LoyerResidenceRepository;
use App\Repository\ResidenceRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/loyer-residence')]
#[OA\Tag(name: 'LoyerResidence', description: 'Suivi des loyers payés par l\'agence pour ses résidences')]
class ApiLoyerResidenceController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, LoyerResidenceRepository $repo): Response
    {
        try {
            $qb = $repo->createQueryBuilder('l')->leftJoin('l.residence', 'r');

            if ($request->query->get('residence_id')) {
                $qb->where('r.id = :rid')->setParameter('rid', (int)$request->query->get('residence_id'));
            }
            if ($request->query->get('etat')) {
                $qb->andWhere('l.etat = :etat')->setParameter('etat', $request->query->get('etat'));
            }

            $loyers = $qb->orderBy('l.datePaiement', 'DESC')->getQuery()->getResult();
            return $this->responseData($loyers, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(
        Request $request,
        LoyerResidenceRepository $repo,
        ResidenceRepository $residenceRepo
    ): Response {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (empty($data['residence_id']) || empty($data['montant']) || empty($data['datePaiement'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Champs requis: residence_id, montant, datePaiement']);
            }

            $residence = $residenceRepo->find($data['residence_id']);
            if (!$residence) {
                $this->setStatusCode(404);
                return $this->response(['message' => 'Résidence introuvable']);
            }

            $loyer = new LoyerResidence();
            $loyer->setResidence($residence);
            $loyer->setMontant((int)$data['montant']);
            $loyer->setDatePaiement(new \DateTime($data['datePaiement']));
            $loyer->setPeriodeLabel($data['periodeLabel'] ?? null);
            $loyer->setPeriodicite($data['periodicite'] ?? $residence->getPeriodiciteLoyer());
            $loyer->setEtat($data['etat'] ?? 'PAYE');
            $loyer->setNotes($data['notes'] ?? null);

            // Upload reçu
            $scanFile = $request->files->get('scan');
            if ($scanFile) {
                $prefix  = $this->slugger->slug('loyer_' . uniqid());
                $dirPath = $this->getUploadDir('loyers', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $scanFile, 'loyers')) {
                    $loyer->setScan($fichier);
                }
            }

            $this->updateAuditFields($loyer, true);
            $repo->save($loyer, true);

            return $this->responseData($loyer, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(LoyerResidence $loyer): Response
    {
        try {
            return $this->responseData($loyer, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(Request $request, LoyerResidence $loyer, LoyerResidenceRepository $repo): Response
    {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (!empty($data['montant'])) $loyer->setMontant((int)$data['montant']);
            if (!empty($data['datePaiement'])) $loyer->setDatePaiement(new \DateTime($data['datePaiement']));
            if (array_key_exists('periodeLabel', $data)) $loyer->setPeriodeLabel($data['periodeLabel']);
            if (array_key_exists('periodicite', $data)) $loyer->setPeriodicite($data['periodicite']);
            if (!empty($data['etat'])) $loyer->setEtat($data['etat']);
            if (array_key_exists('notes', $data)) $loyer->setNotes($data['notes']);

            $scanFile = $request->files->get('scan');
            if ($scanFile) {
                $prefix  = $this->slugger->slug('loyer_' . uniqid());
                $dirPath = $this->getUploadDir('loyers', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $scanFile, 'loyers')) {
                    $loyer->setScan($fichier);
                }
            }

            $this->updateAuditFields($loyer);
            $repo->save($loyer, true);

            return $this->responseData($loyer, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(LoyerResidence $loyer, LoyerResidenceRepository $repo): Response
    {
        try {
            $repo->remove($loyer, true);
            return $this->response(['message' => 'Paiement supprimé']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }
}
