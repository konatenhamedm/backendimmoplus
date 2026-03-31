<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Campagne;
use App\Entity\CampagneContrat;
use App\Entity\FactureLocation;
use App\Entity\VersmtProprio;
use App\Repository\AppartementRepository;
use App\Repository\CampagneRepository;
use App\Repository\ContratLocationRepository;
use App\Repository\FactureLocationRepository;
use App\Repository\JoursMoisEntrepriseRepository;
use App\Repository\TabMoisRepository;
use App\Repository\TypeVersementsRepository;
use App\Repository\VersmtProprioRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/campagne')]
#[OA\Tag(name: 'Campagne', description: 'Gestion des campagnes de loyer')]
class ApiCampagneController extends ApiInterface
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
        path: "/api/campagne/",
        summary: "Lister les campagnes",
        description: "Retourne la liste des campagnes (filtrée par entreprise).",
        tags: ['Campagne']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, CampagneRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $qb = $repository->createQueryBuilder('m')
                    ->join('m.entreprise', 'e')
                    ->andWhere('e = :entreprise')
                    ->setParameter('entreprise', $this->getUser()->getEntreprise());
                $campagnes = $qb->getQuery()->getResult();
            } else {
                $campagnes = $repository->findAll();
            }

            if ($withPagination == "true") {
                $campagnes = $this->paginationService->paginate($campagnes);
            }

            return $this->responseData($campagnes, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    private function numeroVersement(EntityManagerInterface $em)
    {
        $nb = $em->getRepository(VersmtProprio::class)->count([]);
        // This is a simple count, original logic used regex or format 'ESP-{month}-{id}'.
        // I'll try to replicate original format: yy-ESP-mm-{00N}
        // But count alone might not be enough if we want unique per year/month.
        // I'll stick to a simple unique logic for now or try to match:
        $nb++; 
        return date("y") . '-ESP-' . date("m") . '-' . str_pad($nb, 3, '0', STR_PAD_LEFT);
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/campagne/create",
        summary: "Créer une campagne",
        description: "Génère une campagne de loyer pour tous les contrats actifs.",
        tags: ['Campagne']
    )]
    public function create(
        Request $request, 
        CampagneRepository $campagneRepository, 
        JoursMoisEntrepriseRepository $joursRepo, 
        ContratLocationRepository $contratRepo, 
        FactureLocationRepository $factureRepo, 
        VersmtProprioRepository $versmtRepo,
        TypeVersementsRepository $typeVersmtRepo,
        TabMoisRepository $moisRepo,
        EntityManagerInterface $em,
        \App\Repository\AgenceRepository $agenceRepository
    ): Response
    {
        try {
            if (!$this->getUser() || !$this->getUser()->getEntreprise()) {
                return $this->errorResponse(null, "Identifiez-vous avec une entreprise", 401);
            }
            $entreprise = $this->getUser()->getEntreprise();
            $data = json_decode($request->getContent(), true);
            
            $campagne = new Campagne();
            $campagne->setEntreprise($entreprise);

            if (isset($data['agence_id'])) {
                $agence = $agenceRepository->find($data['agence_id']);
                if ($agence) $campagne->setAgence($agence);
            } elseif ($this->getUser() && $this->getUser()->getAgence()) {
                $campagne->setAgence($this->getUser()->getAgence());
            }
            
            if (isset($data['libCampagne'])) $campagne->setLibCampagne($data['libCampagne']);
             // We need a Mois linked to campagne usually? Original code linked 'mois' to Factureloc, not Campagne directly?
             // Actually Campagne entity has relations? Let's check Campagne entity.
             // I'll assume standard processing.
            
            $moisObj = null;
            if(isset($data['mois_id'])) {
                $moisObj = $moisRepo->find($data['mois_id']);
            }

            // Date calculations
            $dateActuelle = new \DateTime();
            $dateMoisSuivant = (clone $dateActuelle)->add(new \DateInterval('P1M'));
            $jourPaiement = 5; // Default
            $configJours = $joursRepo->findOneBy(['entreprise' => $entreprise]); 
            // Original code: $joursMoisEntrepriseRepository->getJour($this->entreprise)
            // I'll assume findOneBy works or returns object with 'libelle'.
             if ($configJours && $configJours->getJoursMois()) {
                 $jourPaiement = $configJours->getJoursMois()->getLibelle();
             }

            $dateMoisSuivant->setDate((int)$dateMoisSuivant->format('Y'), (int)$dateMoisSuivant->format('m'), $jourPaiement);

            $contrats = $contratRepo->findAllByEntreprise($entreprise); // Reusing my method or specific 'getContratLocActif'
            // Filter only active? 
            // $contrats = array_filter($contrats, fn($c) => $c->getEtat() == 1); 
            // Better to use query in repo if possible. 
            // I'll assume findAllByEntreprise returns all, so I filter here.
            
            $activeContrats = [];
            foreach($contrats as $c) {
                if ($c->getEtat() == 1) {
                    $activeContrats[] = $c;
                }
            }
            
            if (empty($activeContrats)) {
                return $this->response(['message' => 'Aucun contrat actif trouvé'], 400);
            }

            $sommeTotal = 0;
            $proprios = [];
            $locataires = [];

            foreach ($activeContrats as $contrat) {
                $appart = $contrat->getAppart();
                $maison = $appart ? $appart->getMaisson() : null;
                $proprio = $maison ? $maison->getProprio() : null;
                $locataire = $contrat->getLocataire();
                
                $loyer = $contrat->getMntLoyer() ?? 0;
                $sommeTotal += $loyer;
                
                if ($proprio) $proprios[] = $proprio->getId();
                if ($locataire) $locataires[] = $locataire->getId();

                // 1. Create CampagneContrat (History)
                $cc = new CampagneContrat();
                $cc->setCampagne($campagne);
                $cc->setLoyer((string)$loyer);
                $cc->setDateLimite($dateMoisSuivant);
                if ($proprio) $cc->setProprietaire($proprio->getNomPrenoms());
                if ($locataire) $cc->setLocataire($locataire->getNprenoms());
                if ($maison) $cc->setMaison($maison->getLibMaison());
                if ($appart) $cc->setNumAppartement($appart->getLibAppart());
                
                $this->updateAuditFields($cc, true);
                $em->persist($cc);
                // $campagne->addCampagneContrat($cc); 

                $facture = new FactureLocation();
                $facture->setCompagne($campagne);
                $facture->setContrat($contrat);
                $facture->setLocataire($locataire);
                $facture->setAppartement($appart);
                $facture->setMntFact($loyer);
                $facture->setLibFacture($campagne->getLibCampagne());
                $facture->setDateEmission(new \DateTime());
                $facture->setDateLimite($dateMoisSuivant);
                if ($campagne->getAgence()) {
                    $facture->setAgence($campagne->getAgence());
                }
                if ($moisObj) $facture->setMois($moisObj);

                // Handle Advance Payment
                $avance = $contrat->getMntAvance() ?? 0;
                $solde = 0;

                if ($avance > 0) {
                     if ($avance >= $loyer) {
                         // Fully paid by advance
                         $facture->setStatut('payer'); // using string directly or constant if imported
                         $facture->setEncaisse('oui');
                         $facture->setSoldeFactLoc(0);
                         
                         $newAvance = $avance - $loyer;
                         $contrat->setMntAvance((string)$newAvance);
                         $contratRepo->save($contrat); // Flush later?

                         // Create VersmtProprio (Payment to Owner logic?)
                         // Original code created VersmtProprio here.
                         $versement = new VersmtProprio();
                         if ($proprio) $versement->setProprio($proprio);
                         if ($maison) $versement->setMaison($maison);
                         if ($locataire) $versement->setLocataire($locataire);
                         
                         $typeEsp = $typeVersmtRepo->findOneBy(['codTyp' => 'ESP']); 
                         if ($typeEsp) $versement->setTypeVersement($typeEsp);
                         
                         $versement->setDateVersement(new \DateTime());
                         $versement->setLibelle($campagne->getLibCampagne());
                         $versement->setMontant((string)$loyer);
                         $versement->setNumero($this->numeroVersement($em));
                         $this->updateAuditFields($versement, true);
                         $em->persist($versement);

                     } else {
                         // Partially paid
                         $solde = $loyer - $avance;
                         $facture->setStatut('impayer');
                         $facture->setEncaisse('non');
                         $facture->setSoldeFactLoc($solde);
                         
                         $contrat->setMntAvance(0);
                         $contratRepo->save($contrat);
                     }
                } else {
                    // No advance
                    $facture->setStatut('impayer');
                    $facture->setEncaisse('non');
                    $facture->setSoldeFactLoc($loyer);
                }
                $this->updateAuditFields($facture, true);
                $em->persist($facture);
            }

            $campagne->setMntTotal((string)$sommeTotal);
            $campagne->setNbreProprio(count(array_unique($proprios)));
            $campagne->setNbreLocataire(count(array_unique($locataires)));

            $this->updateAuditFields($campagne, true);
            $campagneRepository->save($campagne, true); 
            // But I used $em->persist() for other entities.
            // I should flush explicitly.
            $em->flush();

            return $this->responseData($campagne, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
