<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\FactureLocation;
use App\Entity\Transaction;
use App\Repository\AppartementRepository;
use App\Repository\CampagneRepository;
use App\Repository\ContratLocationRepository;
use App\Repository\FactureLocationRepository;
use App\Repository\LocataireRepository;
use App\Repository\TabMoisRepository;
use App\Repository\TransactionRepository;
use App\Service\FneGeneratorService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/facture-location')]
#[OA\Tag(name: 'FactureLocation', description: 'Gestion des factures de location')]
class ApiFactureLocationController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture-location/",
        summary: "Lister les factures",
        description: "Retourne la liste des factures (filtrée par entreprise).",
        tags: ['FactureLocation']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, FactureLocationRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $user = $this->getUser();
            
            if ($user && $user->getEntreprise()) {
                $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
                $agenceId = $request->get('agence_id');
                $agence = $isSuperAdmin ? $agenceId : $user->getAgence();
                $search = $request->get('search');
                $proprioId = $request->get('proprio_id');
                $statut = $request->get('statut');
                
                $factures = $repository->findWithFilters(
                    $user->getEntreprise(),
                    $agence,
                    $proprioId,
                    $search,
                    $statut
                );
            } else {
                $factures = $repository->findAll();
            }

            if ($withPagination == "true") {
                $factures = $this->paginationService->paginate($factures);
            }

            return $this->responseData($factures, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/relances/agent', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture-location/relances/agent",
        summary: "Lister les factures à relancer (Assignées à l'agent)",
        description: "Retourne la liste des factures impayées des maisons assignées à l'agent connecté.",
        tags: ['FactureLocation']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function getRelancesAgent(Request $request, FactureLocationRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $user = $this->getUser();
            
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Utilisateur ou entreprise non trouvé", 404);
            }
            
            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            $agenceId = $request->get('agence_id');
            $agence = $isSuperAdmin ? $agenceId : $user->getAgence();
            $search = $request->get('search');
            $statut = $request->get('statut') ?: 'impayer';
            
            $factures = $repository->findRelancesByAgentWithFilters(
                $user,
                $user->getEntreprise(),
                $agence,
                $search,
                $statut
            );

            if ($withPagination == "true") {
                $factures = $this->paginationService->paginate($factures);
            }

            return $this->responseData($factures, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/facture-location/create",
        summary: "Créer une facture",
        description: "Crée une nouvelle facture de location.",
        tags: ['FactureLocation']
    )]
    public function create(Request $request, FactureLocationRepository $repository, LocataireRepository $locataireRepository, ContratLocationRepository $contratRepository, AppartementRepository $appartementRepository, CampagneRepository $campagneRepository, TabMoisRepository $moisRepository, \App\Repository\AgenceRepository $agenceRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $facture = new FactureLocation();
            
            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $facture->setLocataire($locataire);
            }
            
            if (isset($data['contrat_id'])) {
                $contrat = $contratRepository->find($data['contrat_id']);
                if (!$contrat) return $this->errorResponse(null, "Contrat non trouvé", 404);
                $facture->setContrat($contrat);
            }

            if (isset($data['appartement_id'])) {
                $appart = $appartementRepository->find($data['appartement_id']);
                if (!$appart) return $this->errorResponse(null, "Appartement non trouvé", 404);
                $facture->setAppartement($appart);
            }

            if (isset($data['campagne_id'])) {
                $campagne = $campagneRepository->find($data['campagne_id']);
                if (!$campagne) return $this->errorResponse(null, "Campagne non trouvée", 404);
                $facture->setCompagne($campagne);
            }

            if (isset($data['mois_id'])) {
                $mois = $moisRepository->find($data['mois_id']);
                if (!$mois) return $this->errorResponse(null, "Mois non trouvé", 404);
                $facture->setMois($mois);
            }

            if (isset($data['libFacture'])) $facture->setLibFacture($data['libFacture']);
            if (isset($data['mntFact'])) $facture->setMntFact($data['mntFact']);
            if (isset($data['soldeFactLoc'])) $facture->setSoldeFactLoc($data['soldeFactLoc']);
            if (isset($data['statut'])) $facture->setStatut($data['statut']);
            if (isset($data['encaisse'])) $facture->setEncaisse($data['encaisse']);
            
            if (isset($data['dateEmission'])) $facture->setDateEmission(new \DateTime($data['dateEmission']));
            if (isset($data['dateLimite'])) $facture->setDateLimite(new \DateTime($data['dateLimite']));

            if (isset($data['agence_id'])) {
                $agence = $agenceRepository->find($data['agence_id']);
                if ($agence) $facture->setAgence($agence);
            } elseif ($this->getUser() && $this->getUser()->getAgence()) {
                $facture->setAgence($this->getUser()->getAgence());
            }

            $this->updateAuditFields($facture, true);

            $repository->save($facture, true);

            return $this->responseData($facture, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/facture-location/{id}",
        summary: "Modifier une facture",
        description: "Met à jour une facture existante.",
        tags: ['FactureLocation']
    )]
    public function update(Request $request, FactureLocation $facture, FactureLocationRepository $repository, LocataireRepository $locataireRepository, ContratLocationRepository $contratRepository, AppartementRepository $appartementRepository, CampagneRepository $campagneRepository, TabMoisRepository $moisRepository, \App\Repository\AgenceRepository $agenceRepository): Response
    {
        try {
            if (!$facture) return $this->errorResponse(null, "Facture non trouvée", 404);

            $data = json_decode($request->getContent(), true);

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $facture->setLocataire($locataire);
            }
            
            if (isset($data['contrat_id'])) {
                $contrat = $contratRepository->find($data['contrat_id']);
                if (!$contrat) return $this->errorResponse(null, "Contrat non trouvé", 404);
                $facture->setContrat($contrat);
            }

            if (isset($data['appartement_id'])) {
                $appart = $appartementRepository->find($data['appartement_id']);
                if (!$appart) return $this->errorResponse(null, "Appartement non trouvé", 404);
                $facture->setAppartement($appart);
            }

            if (isset($data['campagne_id'])) {
                $campagne = $campagneRepository->find($data['campagne_id']);
                if (!$campagne) return $this->errorResponse(null, "Campagne non trouvée", 404);
                $facture->setCompagne($campagne);
            }

            if (isset($data['mois_id'])) {
                $mois = $moisRepository->find($data['mois_id']);
                if (!$mois) return $this->errorResponse(null, "Mois non trouvé", 404);
                $facture->setMois($mois);
            }

            if (isset($data['libFacture'])) $facture->setLibFacture($data['libFacture']);
            if (isset($data['mntFact'])) $facture->setMntFact($data['mntFact']);
            if (isset($data['soldeFactLoc'])) $facture->setSoldeFactLoc($data['soldeFactLoc']);
            
            if (isset($data['statut'])) $facture->setStatut($data['statut']);
            if (isset($data['encaisse'])) $facture->setEncaisse($data['encaisse']);
            
            if (isset($data['dateEmission'])) $facture->setDateEmission(new \DateTime($data['dateEmission']));
            if (isset($data['dateLimite'])) $facture->setDateLimite(new \DateTime($data['dateLimite']));

            if (isset($data['agence_id'])) {
                $agence = $agenceRepository->find($data['agence_id']);
                if ($agence) $facture->setAgence($agence);
            } elseif (!$facture->getAgence() && $this->getUser() && $this->getUser()->getAgence()) {
                $facture->setAgence($this->getUser()->getAgence());
            }

            $this->updateAuditFields($facture);

            $repository->save($facture, true);

            return $this->responseData($facture, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/facture-location/{id}",
        summary: "Supprimer une facture",
        description: "Supprime une facture.",
        tags: ['FactureLocation']
    )]
    public function delete(FactureLocation $facture, FactureLocationRepository $repository): Response
    {
        try {
            if (!$facture) return $this->errorResponse(null, "Facture non trouvée", 404);
            $repository->remove($facture, true);
            return $this->response(['message' => 'Facture supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/locataire/{id}/impayers', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture-location/locataire/{id}/impayers",
        summary: "Factures impayées d'un locataire",
        description: "Retourne les factures impayées pour un locataire donné.",
        tags: ['FactureLocation']
    )]
    public function getImpayer(FactureLocationRepository $repository, int $id): Response
    {
        try {
             $factures = $repository->findAllFactureLocataireImpayer($id);
             return $this->responseData($factures, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/locataire/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture-location/locataire/{id}",
        summary: "Factures d'un locataire",
        description: "Retourne les factures pour un locataire donné.",
        tags: ['FactureLocation']
    )]
    public function getByLocataire(FactureLocationRepository $repository, int $id): Response
    {
        try {
            // Reusing findAllFactureLocataire which defaults to impayer in repo?
            // checking repo again. findAllFactureLocataire uses 'impayer'.
            // I should just use findBy if I want all.
            $factures = $repository->findBy(['locataire' => $id]);
            return $this->responseData($factures, 'group1');
        } catch (\Exception $exception) {
             $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/mes-factures', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture-location/mes-factures",
        summary: "Mes factures (Espace Locataire)",
        description: "Retourne les factures du locataire connecté.",
        tags: ['FactureLocation']
    )]
    public function mesFactures(FactureLocationRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getLocataire()) {
                return $this->errorResponse(null, "Profil locataire non trouvé", 404);
            }
            
            $factures = $repository->findBy(['locataire' => $user->getLocataire()->getId()], ['dateEmission' => 'DESC']);
            return $this->responseData($factures, 'group1');
        } catch (\Exception $exception) {
             $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/collect-payment', methods: ['POST'])]
    #[OA\Post(
        path: "/api/facture-location/{id}/collect-payment",
        summary: "Encaisser un paiement par un agent",
        description: "Enregistre un paiement effectué par un agent pour une facture spécifique.",
        tags: ['FactureLocation']
    )]
    public function collectPayment(
        Request $request, 
        FactureLocation $facture, 
        FactureLocationRepository $repository,
        TransactionRepository $transactionRepository
    ): Response {
        try {
            if (!$facture) return $this->errorResponse(null, "Facture non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            $amount = $data['amount'] ?? null;
            $mode = $data['mode'] ?? 'ESPÈCE';
            
            if (!$amount || $amount <= 0) {
                return $this->errorResponse(null, "Montant invalide", 400);
            }

            if ($amount > $facture->getSoldeFactLoc()) {
                return $this->errorResponse(null, "Le montant dépasse le solde restant", 400);
            }

            // 1. Créer la Transaction
            $transaction = new Transaction();
            $transaction->setAmount($amount);
            $transaction->setFactureLocation($facture);
            $transaction->setLocataire($facture->getLocataire());
            $transaction->setMode($mode);
            $transaction->setType('RENTRÉE');
            $transaction->setStatus('SUCCESS');
            $transaction->setReference('TRX-COLLECT-' . time());
            $transaction->setAgent($this->getUser());
            $transaction->setDate(new \DateTime());
            $transaction->setDescription($data['description'] ?? "Paiement encaissé par " . $this->getUser()->getNomPrenoms());
            
            $this->updateAuditFields($transaction, true);
            $transactionRepository->save($transaction, true);

            // 2. Mettre à jour la Facture
            $nouveauSolde = $facture->getSoldeFactLoc() - $amount;
            $nouveauEncaisse = (int)$facture->getEncaisse() + $amount;
            
            $facture->setSoldeFactLoc($nouveauSolde);
            $facture->setEncaisse((string)$nouveauEncaisse);
            
            if ($nouveauSolde <= 0) {
                $facture->setStatut('payer');
            }

            $this->updateAuditFields($facture);
            $repository->save($facture, true);

            // 3. Notifier les Admins
            if ($this->notificationService) {
                $title = "💰 Nouveau Paiement Encaissé";
                $message = sprintf(
                    "L'agent %s a encaissé %s FCFA pour la facture %s (Locataire: %s).",
                    $this->getUser()->getNomPrenoms(),
                    number_format($amount, 0, ',', ' '),
                    $facture->getLibFacture(),
                    $facture->getLocataire()->getNom() . ' ' . $facture->getLocataire()->getPrenoms()
                );
                $this->notificationService->notifyAdmins($facture->getEntreprise(), $title, $message, [
                    'facture_id' => $facture->getId(),
                    'transaction_id' => $transaction->getId(),
                    'send_email' => true,
                    'amount' => $amount,
                    'agent_name' => $this->getUser()->getNomPrenoms(),
                    'locataire_name' => $facture->getLocataire()->getNom() . ' ' . $facture->getLocataire()->getPrenoms(),
                    'facture_libelle' => $facture->getLibFacture(),
                    'mode' => $mode,
                    'date' => new \DateTime(),
                    'email_template' => 'payment_collected_agent'
                ]);
            }

            return $this->responseData($facture, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/generate-fne', methods: ['POST'])]
    #[OA\Post(
        path: "/api/facture-location/{id}/generate-fne",
        summary: "Générer la Facture Normalisée (FNE)",
        description: "Communique avec l'API e-impots pour certifier la facture.",
        tags: ['FactureLocation']
    )]
    public function generateFNE(
        FactureLocation $facture,
        FneGeneratorService $fneService,
        FactureLocationRepository $repository
    ): Response {
        try {
            if (!$facture) return $this->errorResponse(null, "Facture non trouvée", 404);
            if ($facture->getFneUid()) {
                return $this->errorResponse(null, "Cette facture a déjà été normalisée.", 400);
            }

            // Génération
            $result = $fneService->generateFneForFacture($facture);
            
            // Mise à jour de la facture
            $facture->setFneUid($result['uid'] ?? null);
            $facture->setFneQrCode($result['qr_code'] ?? null);
            $facture->setFneStatus($result['status'] ?? null);

            $repository->save($facture, true);

            return $this->responseData([
                'facture' => $facture,
                'fne_details' => $result
            ], 'group1', ['message' => 'Facture normalisée avec succès.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => "Erreur de normalisation : " . $exception->getMessage()]);
        }
    }
}
