<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
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
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    /**
     * Liste les dépenses.
     * - Non-admin : retourne uniquement les dépenses de l'agence de l'utilisateur connecté.
     * - Admin : peut filtrer via ?agence_id=X, sinon toutes les dépenses de l'entreprise.
     */
    #[Route('', methods: ['GET'])]
    public function index(Request $request): Response
    {
        try {
            $user       = $this->getUser();
            $entreprise = $user->getEntreprise();
            $typeId     = $request->query->get('type_id');
            $dateStart  = $request->query->get('date_start');
            $dateEnd    = $request->query->get('date_end');

            // Déterminer l'agence à utiliser
            $isSuperAdmin = $user->getGroupe() && in_array($user->getGroupe()->getCode(), ['ADMIN', 'SUPER_ADMIN']);
            if ($isSuperAdmin) {
                // Admin peut cibler une agence spécifique ou voir tout
                $agenceId = $request->query->get('agence_id');
            } else {
                // Non-admin : forcé sur son agence
                $agenceId = $user->getAgence() ? $user->getAgence()->getId() : null;
            }

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

            return $this->responseData($qb->getQuery()->getResult(), 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /**
     * Créer une nouvelle dépense (multipart/form-data pour le scan).
     */
    #[Route('/create', methods: ['POST'])]
    public function create(
        Request $request,
        DepensesRepository $repo,
        TypeDepenseRepository $typeRepo,
        AgenceRepository $agenceRepo
    ): Response {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (empty($data['type_depense_id'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le type de dépense est requis']);
            }
            if (empty($data['montantTTC'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le montant est requis']);
            }
            if (empty($data['agence_id'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => "L'agence est requise"]);
            }

            $type = $typeRepo->find($data['type_depense_id']);
            if (!$type) {
                $this->setStatusCode(404);
                return $this->response(['message' => 'Type de dépense introuvable']);
            }

            $agence = $agenceRepo->find($data['agence_id']);
            if (!$agence) {
                $this->setStatusCode(404);
                return $this->response(['message' => 'Agence introuvable']);
            }

            $depense = new Depenses();
            $depense->setLibDepense($type->getLibelle()); // auto-rempli depuis le type
            $depense->setMontantTTC((int) $data['montantTTC']);
            $depense->setDate($data['date'] ?? date('Y-m-d'));
            $depense->setDetails($data['details'] ?? null);
            $depense->setTypeDepense($type);
            $depense->setAgence($agence);
            $depense->setEntreprise($this->getUser()->getEntreprise());

            // Upload justificatif (Fichier entity)
            $uploadedScan = $request->files->get('scan');
            if ($uploadedScan) {
                $filePrefix = $this->slugger->slug('depense_' . uniqid());
                $filePath   = $this->getUploadDir('depenses', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedScan, 'depenses')) {
                    $depense->setScan($fichier);
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

    /**
     * Modifier une dépense existante.
     */
    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(
        Request $request,
        Depenses $depense,
        DepensesRepository $repo,
        TypeDepenseRepository $typeRepo,
        AgenceRepository $agenceRepo
    ): Response {
        try {
            $data = $request->request->all() ?: (json_decode($request->getContent(), true) ?? []);

            if (isset($data['montantTTC'])) $depense->setMontantTTC((int) $data['montantTTC']);
            if (isset($data['date']))       $depense->setDate($data['date']);
            if (isset($data['details']))    $depense->setDetails($data['details']);

            if (!empty($data['type_depense_id'])) {
                $type = $typeRepo->find($data['type_depense_id']);
                if ($type) {
                    $depense->setTypeDepense($type);
                    $depense->setLibDepense($type->getLibelle());
                }
            }
            if (!empty($data['agence_id'])) {
                $agence = $agenceRepo->find($data['agence_id']);
                if ($agence) $depense->setAgence($agence);
            }

            // Remplacement du justificatif
            $uploadedScan = $request->files->get('scan');
            if ($uploadedScan) {
                $filePrefix = $this->slugger->slug('depense_' . uniqid());
                $filePath   = $this->getUploadDir('depenses', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedScan, 'depenses')) {
                    $depense->setScan($fichier);
                }
            }

            $this->updateAuditFields($depense);
            $repo->save($depense, true);

            return $this->responseData($depense, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /**
     * Supprimer une dépense.
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Depenses $depense, DepensesRepository $repo): Response
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
