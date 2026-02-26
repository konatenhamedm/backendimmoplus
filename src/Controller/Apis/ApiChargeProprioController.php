<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ChargeAppartement;
use App\Entity\ChargeProprio;
use App\Repository\AppartementRepository;
use App\Repository\ChargeProprioRepository;
use App\Repository\MaisonRepository;
use App\Repository\ProprioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/charge-proprio')]
#[OA\Tag(name: 'ChargeProprio', description: 'Gestion des charges propriétaires')]
class ApiChargeProprioController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        path: "/api/charge-proprio",
        summary: "Liste des charges propriétaires",
        description: "Retourne la liste de toutes les charges propriétaires.",
        tags: ['ChargeProprio']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "proprio_id", in: "query", description: "Filtrer par id du propriétaire", schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "date_start", in: "query", description: "Filtrer par date de début (YYYY-MM-DD)", schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "date_end", in: "query", description: "Filtrer par date de fin (YYYY-MM-DD)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, ChargeProprioRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $qb = $repository->createQueryBuilder('c');

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $qb->andWhere('c.entreprise = :entreprise')
                   ->setParameter('entreprise', $this->getUser()->getEntreprise());
            }

            $proprioId = $request->get('proprio_id');
            if ($proprioId) {
                $qb->andWhere('c.proprio = :proprio')
                   ->setParameter('proprio', $proprioId);
            }

            $dateStart = $request->get('date_start');
            if ($dateStart) {
                $qb->andWhere('c.dateCharge >= :dateStart')
                   ->setParameter('dateStart', $dateStart . ' 00:00:00');
            }

            $dateEnd = $request->get('date_end');
            if ($dateEnd) {
                $qb->andWhere('c.dateCharge <= :dateEnd')
                   ->setParameter('dateEnd', $dateEnd . ' 23:59:59');
            }

            $qb->orderBy('c.id', 'DESC');
            $charges = $qb->getQuery()->getResult();

            if ($withPagination == "true") {
                $charges = $this->paginationService->paginate($charges);
            }

            return $this->responseData($charges, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/charge-proprio/create",
        summary: "Créer une charge propriétaire",
        description: "Enregistre une nouvelle charge à déduire pour un propriétaire.",
        tags: ['ChargeProprio']
    )]
    public function create(Request $request, ChargeProprioRepository $repository, ProprioRepository $proprioRepository, MaisonRepository $maisonRepository, AppartementRepository $appartementRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) $data = $request->request->all();

            $charge = new ChargeProprio();
            
            if (isset($data['proprio_id']) && $data['proprio_id']) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
                $charge->setProprio($proprio);
            } else {
                return $this->errorResponse(null, "Le propriétaire est requis", 400);
            }

            if (isset($data['maison_id']) && $data['maison_id']) {
                $maison = $maisonRepository->find($data['maison_id']);
                if ($maison) $charge->setMaison($maison);
            }

            if (!isset($data['libelle']) || !$data['libelle']) return $this->errorResponse(null, "Libellé requis", 400);
            if (!isset($data['montant']) || !$data['montant']) return $this->errorResponse(null, "Montant requis", 400);

            $charge->setLibelle($data['libelle']);
            $charge->setMontant($data['montant']);
            $charge->setDetails($data['details'] ?? null);
            
            $date = isset($data['dateCharge']) ? new \DateTime($data['dateCharge']) : new \DateTime();
            $charge->setDateCharge($date);

            // Upload Scan
            $uploadedScan = $request->files->get('scan');
            if ($uploadedScan) {
                $filePrefix = $this->slugger->slug('charge_'.uniqid());
                $filePath = $this->getUploadDir('charges', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedScan, 'charges')) {
                    $charge->setScan($fichier);
                }
            }

            // Detailed Apartment Charges
            $appartsData = isset($data['chargeAppartements']) ? (is_string($data['chargeAppartements']) ? json_decode($data['chargeAppartements'], true) : $data['chargeAppartements']) : [];
            if (is_array($appartsData)) {
                foreach ($appartsData as $line) {
                    if (isset($line['appartement_id'])) {
                        $appart = $appartementRepository->find($line['appartement_id']);
                        if ($appart) {
                            $ca = new ChargeAppartement();
                            $ca->setAppartement($appart);
                            $ca->setLibelle($line['libelle'] ?? $charge->getLibelle());
                            $ca->setMontant($line['montant'] ?? 0);
                            $ca->setDetails($line['details'] ?? null);
                            $charge->addChargeAppartement($ca);
                            $this->updateAuditFields($ca, true);
                        }
                    }
                }
            }

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $charge->setEntreprise($this->getUser()->getEntreprise());
            }

            $this->updateAuditFields($charge, true);
            $repository->save($charge, true);

            return $this->responseData($charge, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/charge-proprio/{id}",
        summary: "Modifier une charge",
        description: "Met à jour une charge existante.",
        tags: ['ChargeProprio']
    )]
    public function update(Request $request, ChargeProprio $charge, ChargeProprioRepository $repository, ProprioRepository $proprioRepository, MaisonRepository $maisonRepository, AppartementRepository $appartementRepository): Response
    {
        try {
            if (!$charge) return $this->errorResponse(null, "Charge non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            if (!$data) $data = $request->request->all();

            if (isset($data['proprio_id'])) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if ($proprio) $charge->setProprio($proprio);
            }
            if (isset($data['maison_id'])) {
                $maison = $maisonRepository->find($data['maison_id']);
                if ($maison) $charge->setMaison($maison);
            }

            if (isset($data['libelle'])) $charge->setLibelle($data['libelle']);
            if (isset($data['montant'])) $charge->setMontant($data['montant']);
            if (isset($data['details'])) $charge->setDetails($data['details']);
            if (isset($data['dateCharge'])) $charge->setDateCharge(new \DateTime($data['dateCharge']));

            if (isset($data['isValidated'])) {
                $charge->setIsValidated(filter_var($data['isValidated'], FILTER_VALIDATE_BOOLEAN));
                if ($charge->isValidated() && !$charge->getDateValidation()) {
                    $charge->setDateValidation(new \DateTime());
                } elseif (!$charge->isValidated()) {
                    $charge->setDateValidation(null);
                }
            }

            // Upload Scan
            $uploadedScan = $request->files->get('scan');
            if ($uploadedScan) {
                $filePrefix = $this->slugger->slug('charge_'.uniqid());
                $filePath = $this->getUploadDir('charges', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedScan, 'charges')) {
                    $charge->setScan($fichier);
                }
            }

            // Detailed Apartment Charges (Sync)
            $appartsData = isset($data['chargeAppartements']) ? (is_string($data['chargeAppartements']) ? json_decode($data['chargeAppartements'], true) : $data['chargeAppartements']) : null;
            if (is_array($appartsData)) {
                // Clear existing
                foreach ($charge->getChargeAppartements() as $ca) {
                    $charge->removeChargeAppartement($ca);
                }
                
                foreach ($appartsData as $line) {
                    if (isset($line['appartement_id'])) {
                        $appart = $appartementRepository->find($line['appartement_id']);
                        if ($appart) {
                            $ca = new ChargeAppartement();
                            $ca->setAppartement($appart);
                            $ca->setLibelle($line['libelle'] ?? $charge->getLibelle());
                            $ca->setMontant($line['montant'] ?? 0);
                            $ca->setDetails($line['details'] ?? null);
                            $charge->addChargeAppartement($ca);
                            $this->updateAuditFields($ca, true);
                        }
                    }
                }
            }

            $this->updateAuditFields($charge);
            $repository->save($charge, true);

            return $this->responseData($charge, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/charge-proprio/{id}",
        summary: "Supprimer une charge",
        description: "Supprime une charge propriétaire.",
        tags: ['ChargeProprio']
    )]
    public function delete(ChargeProprio $charge, ChargeProprioRepository $repository): Response
    {
        try {
             if (!$charge) return $this->errorResponse(null, "Charge non trouvée", 404);
            $repository->remove($charge, true);
            return $this->response(['message' => 'Charge supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
