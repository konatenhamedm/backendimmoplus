<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Locataire;
use App\Repository\ContratLocationRepository;
use App\Repository\FactureLocationRepository;
use App\Repository\LocataireRepository;
use App\Repository\VersementProprioRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/locataire')]
#[OA\Tag(name: 'Locataire', description: 'Gestion des locataires')]
class ApiLocataireController extends ApiInterface
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
        path: "/api/locataire/",
        summary: "Lister les locataires",
        description: "Retourne la liste des locataires (filtrée par entreprise).",
        tags: ['Locataire']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, LocataireRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $locataires = $repository->findAllByEntreprise($this->getUser()->getEntreprise());
            } else {
                $locataires = $repository->findAll();
            }

            if ($withPagination == "true") {
                $locataires = $this->paginationService->paginate($locataires);
            }

            return $this->responseData($locataires, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/locataire/create",
        summary: "Créer un locataire",
        description: "Ajoute un nouveau locataire.",
        tags: ['Locataire']
    )]
    public function create(Request $request, LocataireRepository $repository, \App\Repository\SituationMatrimonialeRepository $situationRepo): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $locataire = new Locataire();
            
            if (isset($data['nom'])) $locataire->setNom($data['nom']);
            if (isset($data['prenoms'])) $locataire->setPrenoms($data['prenoms']);
            if (isset($data['DateNaiss'])) $locataire->setDateNaiss(new \DateTime($data['DateNaiss']));
            if (isset($data['LieuNaiss'])) $locataire->setLieuNaiss($data['LieuNaiss']);
            if (isset($data['Profession'])) $locataire->setProfession($data['Profession']);
            if (isset($data['Contacts'])) $locataire->setContacts($data['Contacts']);
            if (isset($data['Email'])) $locataire->setEmail($data['Email']);
            if (isset($data['numpiece'])) $locataire->setNumpiece($data['numpiece']);
            if (isset($data['Genre'])) $locataire->setGenre($data['Genre']);

            if (isset($data['situation_matri_id'])) {
                $sm = $situationRepo->find($data['situation_matri_id']);
                if (!$sm) return $this->errorResponse(null, "Situation matrimoniale non trouvée", 404);
                $locataire->setSituationMatri($sm);
            }
            
            // Optional fields
            if (isset($data['Ethnie'])) $locataire->setEthnie($data['Ethnie']);
            if (isset($data['NbEnfts'])) $locataire->setNbEnfts($data['NbEnfts']);
            if (isset($data['NbPersChge'])) $locataire->setNbPersChge($data['NbPersChge']);
            if (isset($data['Pere'])) $locataire->setPere($data['Pere']);
            if (isset($data['Mere'])) $locataire->setMere($data['Mere']);
            if (isset($data['NPConjointe'])) $locataire->setNPConjointe($data['NPConjointe']);
            if (isset($data['ProfConj'])) $locataire->setProfConj($data['ProfConj']);
            if (isset($data['EthnieConj'])) $locataire->setEthnieConj($data['EthnieConj']);
            if (isset($data['ContactConj'])) $locataire->setContactConj($data['ContactConj']);
            if (isset($data['VivezAvec'])) $locataire->setVivezAvec($data['VivezAvec']);

            // Upload InfoPiece
            $uploadedFile = $request->files->get('info_piece');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('info_piece_'.uniqid());
                $filePath = $this->getUploadDir('locataires', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'locataires')) {
                    $locataire->setInfoPiece($fichier);
                }
            }

            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $locataire->setEntreprise($this->getUser()->getEntreprise());
            }

            $this->updateAuditFields($locataire, true);

            $repository->save($locataire, true);

            return $this->responseData($locataire, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    #[OA\Put(
        path: "/api/locataire/{id}",
        summary: "Modifier un locataire",
        description: "Met à jour un locataire existant.",
        tags: ['Locataire']
    )]
    public function update(Request $request, Locataire $locataire, LocataireRepository $repository, \App\Repository\SituationMatrimonialeRepository $situationRepo): Response
    {
        try {
            if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }
            
           if (isset($data['nom'])) $locataire->setNom($data['nom']);
            if (isset($data['prenoms'])) $locataire->setPrenoms($data['prenoms']);
            if (isset($data['DateNaiss'])) $locataire->setDateNaiss(new \DateTime($data['DateNaiss']));
            if (isset($data['LieuNaiss'])) $locataire->setLieuNaiss($data['LieuNaiss']);
            if (isset($data['Profession'])) $locataire->setProfession($data['Profession']);
            if (isset($data['Contacts'])) $locataire->setContacts($data['Contacts']);
            if (isset($data['Email'])) $locataire->setEmail($data['Email']);
            if (isset($data['numpiece'])) $locataire->setNumpiece($data['numpiece']);
            if (isset($data['Genre'])) $locataire->setGenre($data['Genre']);

            if (isset($data['situation_matri_id'])) {
                $sm = $situationRepo->find($data['situation_matri_id']);
                if (!$sm) return $this->errorResponse(null, "Situation matrimoniale non trouvée", 404);
                $locataire->setSituationMatri($sm);
            }
            
            if (isset($data['Ethnie'])) $locataire->setEthnie($data['Ethnie']);
            if (isset($data['NbEnfts'])) $locataire->setNbEnfts($data['NbEnfts']);
            if (isset($data['NbPersChge'])) $locataire->setNbPersChge($data['NbPersChge']);
            if (isset($data['Pere'])) $locataire->setPere($data['Pere']);
            if (isset($data['Mere'])) $locataire->setMere($data['Mere']);
            if (isset($data['NPConjointe'])) $locataire->setNPConjointe($data['NPConjointe']);
            if (isset($data['ProfConj'])) $locataire->setProfConj($data['ProfConj']);
            if (isset($data['EthnieConj'])) $locataire->setEthnieConj($data['EthnieConj']);
            if (isset($data['ContactConj'])) $locataire->setContactConj($data['ContactConj']);
            if (isset($data['VivezAvec'])) $locataire->setVivezAvec($data['VivezAvec']);

            // Upload InfoPiece
            $uploadedFile = $request->files->get('info_piece');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('info_piece_'.uniqid());
                $filePath = $this->getUploadDir('locataires', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'locataires')) {
                    $locataire->setInfoPiece($fichier);
                }
            }

            $this->updateAuditFields($locataire);

            $repository->save($locataire, true);

            return $this->responseData($locataire, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/locataire/{id}",
        summary: "Supprimer un locataire",
        description: "Supprime un locataire.",
        tags: ['Locataire']
    )]
    public function delete(Locataire $locataire, LocataireRepository $repository): Response
    {
        try {
            if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
            $repository->remove($locataire, true);
            return $this->response(['message' => 'Locataire supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/sans-compte', methods: ['GET'])]
    #[OA\Get(
        path: "/api/locataire/sans-compte",
        summary: "Lister les locataires sans compte utilisateur",
        description: "Retourne les locataires de l'entreprise qui n'ont pas encore de compte utilisateur.",
        tags: ['Locataire']
    )]
    public function sansCompte(LocataireRepository $repository): Response
    {
        try {
            $locataires = $repository->withoutAccount()->getQuery()->getResult();
            return $this->responseData($locataires, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/details', methods: ['GET'])]
    #[OA\Get(
        path: "/api/locataire/{id}/details",
        summary: "Obtenir les détails d'un locataire (contrats, versements, factures)",
        description: "Retourne les contrats, les versements et les factures (payées/impayées) d'un locataire.",
        tags: ['Locataire']
    )]
    public function getLocataireDetails(
        Locataire $locataire,
        ContratLocationRepository $contratRepository,
        VersementProprioRepository $versementRepository, // Assuming VersementProprio or a generic Versement entity, checking previous context... Wait, user said "tout ses versements". 
        // In previous edits, we saw VersementProprio. But locataire pays rent via 'Reglements' usually linked to FactureLocation? 
        // Let's check relation. Locataire has OneToMany FactureLocation. FactureLocation has OneToMany Reglements.
        // Also Locataire might be PAYING directly?
        // Let's use what is available. The user asked for "ses versements".
        // FactureLocation has inversedBy 'locataire'. 
        // Reglements usually is payment of invoice.
       FactureLocationRepository $factureRepository
    ): Response
    {
        try {
            if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);

            // Contracts
            $contrats = $contratRepository->findBy(['locataire' => $locataire]);

            // Factures
            // Assuming 'statut' in FactureLocation discriminates paid/unpaid. 
            // In FactureLocation entity: const ETATS_STATUT = ['payer' => 'payer', 'impayer' => 'impayer'];
            $facturesPayees = $factureRepository->findBy(['locataire' => $locataire, 'statut' => 'payer']);
            $facturesImpayees = $factureRepository->findBy(['locataire' => $locataire, 'statut' => 'impayer']);
            
            // Versements
            // If versements are Reglements linked to Factures, we can iterate factures or query Reglements repository if it exists and has locataire relation.
            // But usually API response flattens this.
            // Let's assume we want to return the lists.
            // If there is a Versement entity linked to Locataire directly? 
            // I saw VersementProprio has `locataire` field in previous `VersementProprio.php` view (lines 35-36: private ?Locataire $locataire = null;).
            // But that's VersementProprio (payment TO owner?). 
            // User asked "tout ses versements", likely meaning payments MADE solely by the tenant (Reglements).
            // Let's look for ReglementsRepository inside the method or just map from factures if no direct repo access easily.
            // Better: use repositories.
            
            // Re-checking FactureLocation entity: it has `private Collection $reglements;`.
            // So we can collect all reglments from all his factures, OR query ReglementsRepository if it has `locataire` or via `facture.locataire`.
            
            // Let's stick to what we know:
            // Contrats: straightforward.
            // Factures: straightforward (payées/impayées).
            // Versements: likely Reglements. 
            
            // I will use a simple approach: fetch all Reglements associated with this locataire's invoices.
            // Or if VersementProprio is what matches "versements" (maybe generic name?), but VersementProprio name suggests payments to owners.
            // "tout ses versements" for a locataire usually means Rent Payments.
            // Let's check if there is a Reglement entity/repository.
            
            // I'll proceed with Contrats and Factures first, and for Versements, I'll allow fetching Reglements via Factures loop or separate query if I can confirm Reglements repo.
            // For now, I'll assume fetching all Factures and their Reglements is best.
            
            // Actually, querying all factures and separating them is efficient enough.
            $allFactures = $factureRepository->findBy(['locataire' => $locataire]);
            $facturesPayees = [];
            $facturesImpayees = [];
            $versements = [];

            foreach ($allFactures as $facture) {
                if ($facture->getStatut() === 'payer') {
                    $facturesPayees[] = $facture;
                } else {
                    $facturesImpayees[] = $facture;
                }
                
                // Collect Reglements (Versements)
                foreach ($facture->getReglements() as $reglement) {
                    $versements[] = $reglement;
                }
            }

            return $this->response([
                'contrats' => $contrats,
                'factures_payees' => $facturesPayees,
                'factures_impayees' => $facturesImpayees,
                'versements' => $versements 
            ], 'group1'); // Assuming group1 exposes necessary fields
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
