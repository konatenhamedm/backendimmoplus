<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\TypeDepense;
use App\Entity\Depenses;
use App\Repository\TypeDepenseRepository;
use App\Repository\DepensesRepository;
use App\Repository\AgenceRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/depenses')]
#[OA\Tag(name: 'Depenses', description: 'Gestion des dépenses agence')]
class ApiDepensesController extends ApiInterface
{
    // ─── TYPES DE DEPENSES ─────────────────────────────────────────────

    #[Route('/types', methods: ['GET'])]
    public function listTypes(TypeDepenseRepository $repo): Response
    {
        try {
            $entreprise = $this->getUser()->getEntreprise();
            $types = $repo->findBy(['entreprise' => $entreprise], ['libelle' => 'ASC']);
            return $this->responseData($types, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/types/create', methods: ['POST'])]
    public function createType(Request $request, TypeDepenseRepository $repo): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (empty($data['libelle'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le libellé est requis']);
            }

            $type = new TypeDepense();
            $type->setLibelle($data['libelle']);
            $type->setDescription($data['description'] ?? null);
            $type->setEntreprise($this->getUser()->getEntreprise());

            $this->updateAuditFields($type, true);
            $repo->save($type, true);

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/types/{id}', methods: ['PUT', 'POST'])]
    public function updateType(Request $request, TypeDepense $type, TypeDepenseRepository $repo): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['libelle'])) $type->setLibelle($data['libelle']);
            if (isset($data['description'])) $type->setDescription($data['description']);

            $this->updateAuditFields($type);
            $repo->save($type, true);

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/types/{id}', methods: ['DELETE'])]
    public function deleteType(TypeDepense $type, TypeDepenseRepository $repo): Response
    {
        try {
            $repo->remove($type, true);
            return $this->response(['message' => 'Type supprimé avec succès']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    // ─── DEPENSES ──────────────────────────────────────────────────────

    #[Route('', methods: ['GET'])]
    public function listDepenses(Request $request, DepensesRepository $repo): Response
    {
        try {
            $user = $this->getUser();
            $entreprise = $user->getEntreprise();
            $agenceId = $request->query->get('agence_id');
            $typeId = $request->query->get('type_id');
            $dateStart = $request->query->get('date_start');
            $dateEnd = $request->query->get('date_end');

            $qb = $this->em->getRepository(Depenses::class)->createQueryBuilder('d')
                ->where('d.entreprise = :ent')
                ->setParameter('ent', $entreprise)
                ->orderBy('d.createdAt', 'DESC');

            if ($agenceId) {
                $qb->andWhere('d.agence = :agence')->setParameter('agence', $agenceId);
            }
            if ($typeId) {
                $qb->andWhere('d.typeDepense = :type')->setParameter('type', $typeId);
            }
            if ($dateStart) {
                $qb->andWhere('d.date >= :dateStart')->setParameter('dateStart', $dateStart);
            }
            if ($dateEnd) {
                $qb->andWhere('d.date <= :dateEnd')->setParameter('dateEnd', $dateEnd);
            }

            $depenses = $qb->getQuery()->getResult();
            return $this->responseData($depenses, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function createDepense(
        Request $request,
        DepensesRepository $repo,
        TypeDepenseRepository $typeRepo,
        AgenceRepository $agenceRepo
    ): Response {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (empty($data['libDepense'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le libellé est requis']);
            }
            if (empty($data['montantTTC'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le montant est requis']);
            }

            $depense = new Depenses();
            $depense->setLibDepense($data['libDepense']);
            $depense->setMontantTTC((int)$data['montantTTC']);
            $depense->setDate($data['date'] ?? date('Y-m-d'));
            $depense->setDetails($data['details'] ?? null);
            $depense->setEntreprise($this->getUser()->getEntreprise());

            if (!empty($data['type_depense_id'])) {
                $type = $typeRepo->find($data['type_depense_id']);
                if ($type) $depense->setTypeDepense($type);
            }

            if (!empty($data['agence_id'])) {
                $agence = $agenceRepo->find($data['agence_id']);
                if ($agence) $depense->setAgence($agence);
            }

            // Upload scan/justificatif
            $uploadedScan = $request->files->get('scan');
            if ($uploadedScan) {
                $filePrefix = $this->slugger->slug('depense_' . uniqid());
                $filePath = $this->getUploadDir('depenses', true);
                $depense->setScan($this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedScan, 'depenses'));
            }

            $this->updateAuditFields($depense, true);
            $repo->save($depense, true);

            return $this->responseData($depense, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function updateDepense(
        Request $request,
        Depenses $depense,
        DepensesRepository $repo,
        TypeDepenseRepository $typeRepo,
        AgenceRepository $agenceRepo
    ): Response {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['libDepense'])) $depense->setLibDepense($data['libDepense']);
            if (isset($data['montantTTC'])) $depense->setMontantTTC((int)$data['montantTTC']);
            if (isset($data['date'])) $depense->setDate($data['date']);
            if (isset($data['details'])) $depense->setDetails($data['details']);

            if (!empty($data['type_depense_id'])) {
                $type = $typeRepo->find($data['type_depense_id']);
                if ($type) $depense->setTypeDepense($type);
            }
            if (!empty($data['agence_id'])) {
                $agence = $agenceRepo->find($data['agence_id']);
                if ($agence) $depense->setAgence($agence);
            }

            $this->updateAuditFields($depense);
            $repo->save($depense, true);

            return $this->responseData($depense, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deleteDepense(Depenses $depense, DepensesRepository $repo): Response
    {
        try {
            $repo->remove($depense, true);
            return $this->response(['message' => 'Dépense supprimée avec succès']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }
}
