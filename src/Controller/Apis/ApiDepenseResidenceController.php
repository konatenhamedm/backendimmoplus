<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\DepenseResidence;
use App\Repository\DepenseResidenceRepository;
use App\Repository\ResidenceRepository;
use App\Repository\TypeDepenseRepository;
use App\Repository\AgenceRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/depense-residence')]
#[OA\Tag(name: 'DepenseResidence', description: 'Dépenses liées aux résidences')]
class ApiDepenseResidenceController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, DepenseResidenceRepository $repo): Response
    {
        try {
            $withPagination = $request->query->get('with_pagination', "false");
            $qb = $repo->createQueryBuilder('d')
                ->leftJoin('d.residence', 'r')
                ->leftJoin('d.typeDepense', 't')
                ->leftJoin('d.agence', 'a');

            if ($request->query->get('residence_id')) {
                $qb->andWhere('r.id = :rid')->setParameter('rid', (int)$request->query->get('residence_id'));
            }

            if ($request->query->get('agence_id')) {
                $qb->andWhere('a.id = :aid')->setParameter('aid', (int)$request->query->get('agence_id'));
            }

            $qb->orderBy('d.dateDepense', 'DESC');

            if ($withPagination === "true") {
                $depenses = $this->paginationService->paginate($qb);
                return $this->responseData($depenses, ['group1', 'group2'], [],  true);
            }

            $depenses = $qb->getQuery()->getResult();
            return $this->responseData($depenses, ['group1', 'group2']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, DepenseResidenceRepository $repo, ResidenceRepository $residenceRepo): Response
    {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (empty($data['residence_id']) || empty($data['libelle']) || empty($data['montant']) || empty($data['dateDepense'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Champs requis: residence_id, libelle, montant, dateDepense']);
            }

            $residence = $residenceRepo->find($data['residence_id']);
            if (!$residence) {
                $this->setStatusCode(404);
                return $this->response(['message' => 'Résidence introuvable']);
            }

            $depense = new DepenseResidence();
            $depense->setResidence($residence);
            $depense->setLibelle($data['libelle']);
            $depense->setMontant((int)$data['montant']);
            $depense->setDateDepense(new \DateTime($data['dateDepense']));
            $depense->setNotes($data['notes'] ?? null);

            if (!empty($data['type_depense_id'])) {
                $type = $this->em->getRepository(\App\Entity\TypeDepense::class)->find($data['type_depense_id']);
                if ($type) $depense->setTypeDepense($type);
            }

            if (!empty($data['agence_id'])) {
                $agence = $this->em->getRepository(\App\Entity\Agence::class)->find($data['agence_id']);
                if ($agence) $depense->setAgence($agence);
            }

            $justifFile = $request->files->get('justificatif');
            if ($justifFile) {
                $prefix  = $this->slugger->slug('depense_' . uniqid());
                $dirPath = $this->getUploadDir('depenses_residence', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $justifFile, 'depenses_residence')) {
                    $depense->setJustificatif($fichier);
                }
            }

            $this->updateAuditFields($depense, true);
            $repo->save($depense, true);

            return $this->responseData($depense, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(DepenseResidence $depense): Response
    {
        try {
            return $this->responseData($depense, ['group1', 'group2']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(Request $request, DepenseResidence $depense, DepenseResidenceRepository $repo): Response
    {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (!empty($data['libelle']))      $depense->setLibelle($data['libelle']);
            if (!empty($data['montant']))      $depense->setMontant((int)$data['montant']);
            if (!empty($data['dateDepense']))  $depense->setDateDepense(new \DateTime($data['dateDepense']));
            if (array_key_exists('notes', $data))     $depense->setNotes($data['notes'] ?: null);

            if (!empty($data['type_depense_id'])) {
                $type = $this->em->getRepository(\App\Entity\TypeDepense::class)->find($data['type_depense_id']);
                if ($type) $depense->setTypeDepense($type);
            }

            if (!empty($data['agence_id'])) {
                $agence = $this->em->getRepository(\App\Entity\Agence::class)->find($data['agence_id']);
                if ($agence) $depense->setAgence($agence);
            }

            $justifFile = $request->files->get('justificatif');
            if ($justifFile) {
                $prefix  = $this->slugger->slug('depense_' . uniqid());
                $dirPath = $this->getUploadDir('depenses_residence', true);
                if ($fichier = $this->utils->sauvegardeFichier($dirPath, $prefix, $justifFile, 'depenses_residence')) {
                    $depense->setJustificatif($fichier);
                }
            }

            $this->updateAuditFields($depense);
            $repo->save($depense, true);

            return $this->responseData($depense,['group1', 'group2']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(DepenseResidence $depense, DepenseResidenceRepository $repo): Response
    {
        try {
            $repo->remove($depense, true);
            return $this->response(['message' => 'Dépense supprimée']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }
}
