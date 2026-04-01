<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Residence;
use App\Repository\ResidenceRepository;
use App\Repository\AgenceRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/residence')]
#[OA\Tag(name: 'Residence', description: 'Gestion des résidences agence')]
class ApiResidenceController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, ResidenceRepository $repo): Response
    {
        try {
            $user = $this->getUser();
            $isSuperAdmin = $user->getGroupe() && in_array($user->getGroupe()->getCode(), ['ADMIN', 'SUPER_ADMIN', 'SADM']);

            if ($isSuperAdmin && $request->query->get('agence_id')) {
                $agenceId = (int) $request->query->get('agence_id');
                $residences = $repo->createQueryBuilder('r')
                    ->leftJoin('r.agence', 'a')
                    ->leftJoin('r.reservations', 'res')
                    ->leftJoin('r.loyerPaiements', 'lp')
                    ->where('a.id = :aid')
                    ->setParameter('aid', $agenceId)
                    ->getQuery()->getResult();
            } else {
                $agence = $user->getAgence();
                if (!$agence && !$isSuperAdmin) {
                    return $this->response(['data' => []]);
                }
                $qb = $repo->createQueryBuilder('r');
                if ($agence) {
                    $qb->where('r.agence = :agence')->setParameter('agence', $agence);
                }
                $residences = $qb->getQuery()->getResult();
            }

            return $this->responseData($residences, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, ResidenceRepository $repo, AgenceRepository $agenceRepo): Response
    {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);
            $user = $this->getUser();

            if (empty($data['libelle'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le libellé est requis']);
            }

            $isSuperAdmin = $user->getGroupe() && in_array($user->getGroupe()->getCode(), ['ADMIN', 'SUPER_ADMIN', 'SADM']);

            if ($isSuperAdmin && !empty($data['agence_id'])) {
                $agence = $agenceRepo->find($data['agence_id']);
            } else {
                $agence = $user->getAgence();
            }

            $residence = new Residence();
            $residence->setLibelle($data['libelle']);
            $residence->setAdresse($data['adresse'] ?? null);
            $residence->setMontantLocation((int)($data['montantLocation'] ?? 0));
            $residence->setEtat($data['etat'] ?? 'DISPONIBLE');
            $residence->setChargeLoyer(!empty($data['chargeLoyer']) && $data['chargeLoyer'] !== 'false');
            $residence->setMontantLoyer(!empty($data['montantLoyer']) ? (int)$data['montantLoyer'] : null);
            $residence->setPeriodiciteLoyer($data['periodiciteLoyer'] ?? null);
            $residence->setAgence($agence);
            $residence->setEntreprise($user->getEntreprise());

            // Photo upload
            $photoFile = $request->files->get('photo');
            if ($photoFile) {
                $prefix  = $this->slugger->slug('residence_' . uniqid());
                $dirPath = $this->getUploadDir('residences', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $photoFile, 'residences')) {
                    $residence->setPhoto($fichier);
                }
            }

            $this->updateAuditFields($residence, true);
            $repo->save($residence, true);

            return $this->responseData($residence, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Residence $residence): Response
    {
        try {
            return $this->responseData($residence, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(Request $request, Residence $residence, ResidenceRepository $repo): Response
    {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (!empty($data['libelle'])) $residence->setLibelle($data['libelle']);
            if (array_key_exists('adresse', $data)) $residence->setAdresse($data['adresse']);
            if (!empty($data['montantLocation'])) $residence->setMontantLocation((int)$data['montantLocation']);
            if (!empty($data['etat'])) $residence->setEtat($data['etat']);
            if (array_key_exists('chargeLoyer', $data)) $residence->setChargeLoyer($data['chargeLoyer'] && $data['chargeLoyer'] !== 'false');
            if (array_key_exists('montantLoyer', $data)) $residence->setMontantLoyer(!empty($data['montantLoyer']) ? (int)$data['montantLoyer'] : null);
            if (array_key_exists('periodiciteLoyer', $data)) $residence->setPeriodiciteLoyer($data['periodiciteLoyer'] ?: null);

            // Photo update
            $photoFile = $request->files->get('photo');
            if ($photoFile) {
                $prefix  = $this->slugger->slug('residence_' . uniqid());
                $dirPath = $this->getUploadDir('residences', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $photoFile, 'residences')) {
                    $residence->setPhoto($fichier);
                }
            }

            $this->updateAuditFields($residence);
            $repo->save($residence, true);

            return $this->responseData($residence, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Residence $residence, ResidenceRepository $repo): Response
    {
        try {
            if ($residence->getReservations()->count() > 0) {
                $this->setStatusCode(409);
                return $this->response(['message' => 'Impossible : des réservations sont liées à cette résidence.']);
            }
            $repo->remove($residence, true);
            return $this->response(['message' => 'Résidence supprimée']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }
}
