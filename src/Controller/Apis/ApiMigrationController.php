<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ContratLocation;
use App\Entity\Locataire;
use App\Entity\FactureLocation;
use App\Entity\Nature;
use App\Entity\Regime;
use App\Repository\AppartementRepository;
use App\Repository\CampagneRepository;
use App\Repository\ContratLocationRepository;
use App\Repository\LocataireRepository;
use App\Repository\TabMoisRepository;
use App\Repository\AnneeRepository;
use App\Repository\SituationMatrimonialeRepository;
use App\Repository\AgenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/migration')]
#[OA\Tag(name: 'Migration', description: 'Flux de migration pour les anciens locataires')]
class ApiMigrationController extends ApiInterface
{
    #[Route('/setup', methods: ['POST'])]
    #[OA\Post(
        path: "/api/migration/setup",
        summary: "Configuration initiale groupée (Migration)",
        description: "Crée un locataire, son contrat et génère l'historique des factures payées.",
        tags: ['Migration']
    )]
    public function setup(
        Request $request,
        LocataireRepository $locataireRepository,
        ContratLocationRepository $contratRepository,
        AppartementRepository $appartementRepository,
        CampagneRepository $campagneRepository,
        TabMoisRepository $moisRepository,
        AnneeRepository $anneeRepository,
        SituationMatrimonialeRepository $situationRepository,
        AgenceRepository $agenceRepository
    ): Response {
        $this->em->beginTransaction();
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) return $this->errorResponse(null, "Données invalides", 400);

            $locData = $data['locataire'] ?? [];
            $ctrData = $data['contrat'] ?? [];
            $migData = $data['migration'] ?? [];
            $mode = $data['mode'] ?? 'new';

            $locataire = null;

            if ($mode === 'existing' && isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) {
                    return $this->errorResponse(null, "Locataire existant non trouvé", 404);
                }
            }

            // Détection de l'agence (priorité: data > appartement > user)
            $agence = null;
            if (isset($data['agence_id'])) {
                $agence = $agenceRepository->find($data['agence_id']);
            }

            $appartement = null;
            if (isset($ctrData['appartement_id'])) {
                $appartement = $appartementRepository->find($ctrData['appartement_id']);
                if ($appartement && !$agence) {
                    $agence = $appartement->getMaisson() ? $appartement->getMaisson()->getAgence() : null;
                }
            }

            if (!$agence && $this->getUser()) {
                $agence = $this->getUser()->getAgence();
            }

            if (!$locataire) {
                // 1. Création du Locataire
                $locataire = new Locataire();
                $locataire->setNom($locData['nom'] ?? '');
                $locataire->setPrenoms($locData['prenoms'] ?? '');
                if (isset($locData['dateNaiss'])) $locataire->setDateNaiss(new \DateTime($locData['dateNaiss']));
                $locataire->setLieuNaiss($locData['lieuNaiss'] ?? '');
                $locataire->setProfession($locData['profession'] ?? '');
                $locataire->setContacts($locData['contacts'] ?? '');
                $locataire->setEmail($locData['email'] ?? '');
                $locataire->setNumpiece($locData['numpiece'] ?? '');
                $locataire->setGenre($locData['genre'] ?? 'M');
                $locataire->setEthnie($locData['ethnie'] ?? '');
                $locataire->setNbEnfts((string)($locData['nbEnfts'] ?? 0));
                $locataire->setNbPersChge((string)($locData['nbPersChge'] ?? 0));
                $locataire->setPere($locData['pere'] ?? '');
                $locataire->setMere($locData['mere'] ?? '');
                $locataire->setNPConjointe($locData['nPConjointe'] ?? '');
                $locataire->setProfConj($locData['profConj'] ?? '');
                $locataire->setEthnieConj($locData['ethnieConj'] ?? '');
                $locataire->setContactConj($locData['contactConj'] ?? '');
                $locataire->setVivezAvec($locData['vivezAvec'] ?? '');
                $locataire->setTelWhatsapp($locData['telWhatsapp'] ?? '');
                $locataire->setEmployeur($locData['employeur'] ?? '');
                $locataire->setRessourcesExactes($locData['ressourcesExactes'] ?? '');
                $locataire->setAnimalCompagnie($locData['animalCompagnie'] ?? '');
                $locataire->setEmailConjoint($locData['emailConjoint'] ?? '');

                if (isset($locData['situation_matri_id'])) {
                    $sit = $situationRepository->find($locData['situation_matri_id']);
                    if ($sit) $locataire->setSituationMatri($sit);
                }

                if ($this->getUser() && $this->getUser()->getEntreprise()) {
                    $locataire->setEntreprise($this->getUser()->getEntreprise());
                }
                
                if ($agence) {
                    $locataire->setAgence($agence);
                }

                $this->updateAuditFields($locataire, true);
                $this->em->persist($locataire);
            }

            // 2. Création du Contrat
            $contrat = new ContratLocation();
            $contrat->setLocataire($locataire);

            if ($appartement) {
                $contrat->setAppart($appartement);
                $contrat->setMntLoyer($appartement->getLoyer());
            }

            if (isset($ctrData['dateDebut'])) $contrat->setDateDebut(new \DateTime($ctrData['dateDebut']));
            if (isset($ctrData['dateFin'])) $contrat->setDateFin(new \DateTime($ctrData['dateFin']));
            if (isset($ctrData['dateEntree'])) $contrat->setDateEntree(new \DateTime($ctrData['dateEntree']));
            
            $isAvanceConsommee = $migData['avanceConsommee'] ?? false;
            $contrat->setIsAvanceConsommee($isAvanceConsommee);
            
            if ($isAvanceConsommee) {
                $contrat->setNbMoisAvance('0');
                $contrat->setMntAvance('0');
            } else {
                $contrat->setNbMoisAvance($ctrData['nbMoisAvance'] ?? '0');
                $contrat->setMntAvance($ctrData['mntAvance'] ?? '0');
            }

            $contrat->setNbMoisCaution($ctrData['nbMoisCaution'] ?? '0');
            $contrat->setMntCaution($ctrData['mntCaution'] ?? '0');
            $contrat->setFraisanex($ctrData['fraisanex'] ?? '0');
            $contrat->setJourGenerationFacture($ctrData['jourGenerationFacture'] ?? 5);
            
            if (isset($ctrData['nature_id'])) {
                $nat = $this->em->getRepository(Nature::class)->find($ctrData['nature_id']);
                if ($nat) $contrat->setNature($nat);
            }
            if (isset($ctrData['regime_id'])) {
                $reg = $this->em->getRepository(Regime::class)->find($ctrData['regime_id']);
                if ($reg) $contrat->setRegime($reg);
            }
            $contrat->setReglement($ctrData['reglement'] ?? '');
            $contrat->setIsEcheance($ctrData['isEcheance'] ?? false);
            $contrat->setNbEcheance((int)($ctrData['nbEcheance'] ?? 0));
            
            // Calcul total versé (caution + avance restante + frais)
            $somme = (float)$contrat->getMntCaution() + (float)$contrat->getMntAvance() + (float)$contrat->getFraisanex();
            $contrat->setTotVerse((string)$somme);
            $contrat->setEtat(1);

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $contrat->setEntreprise($this->getUser()->getEntreprise());
            }

            if ($agence) {
                $contrat->setAgence($agence);
            }

            $this->updateAuditFields($contrat, true);
            $this->em->persist($contrat);

            if ($appartement) {
                $appartement->setOqp(1);
            }

            // 3. Génération de l'historique
            $nbFacturesPayees = (int)($migData['nbFacturesPayees'] ?? 0);
            if ($nbFacturesPayees > 0) {
                $dateDebut = $contrat->getDateDebut() ?: new \DateTime();
                
                for ($i = 0; $i < $nbFacturesPayees; $i++) {
                    $dateFacture = new \DateTime($dateDebut->format('Y-m-d'));
                    $dateFacture->modify("+$i month");
                    
                    $numMois = (int)$dateFacture->format('m');
                    $anneeLibelle = $dateFacture->format('Y');

                    $mois = $moisRepository->findOneBy(['numMois' => $numMois]);
                    $annee = $anneeRepository->findOneBy(['libelle' => $anneeLibelle]);

                    if ($mois && $annee) {
                        $campagne = $campagneRepository->findOneBy([
                            'mois' => $mois,
                            'annee' => $annee,
                            'entreprise' => $this->getUser()->getEntreprise()
                        ]);

                        // Si la campagne n'existe pas, on la crée automatiquement pour la migration
                        if (!$campagne) {
                            $campagne = new \App\Entity\Campagne();
                            $campagne->setMois($mois);
                            $campagne->setAnnee($annee);
                            $campagne->setLibCampagne("Campagne " . $mois->getLibMois() . " " . $annee->getLibelle());
                            $campagne->setEntreprise($this->getUser()->getEntreprise());
                            $campagne->setAgence($agence);
                            $campagne->setNbreProprio(0);
                            $campagne->setNbreLocataire(0);
                            $campagne->setMntTotal(0);
                            $campagne->setMntPaye('0');
                            $this->updateAuditFields($campagne, true);
                            $this->em->persist($campagne);
                        }

                        $facture = new FactureLocation();
                        $facture->setLocataire($locataire);
                        $facture->setContrat($contrat);
                        $facture->setAppartement($appartement);
                        $facture->setCompagne($campagne);
                        $facture->setMois($mois);
                        $facture->setLibFacture("Facture " . $mois->getLibMois() . " " . $annee->getLibelle() . " (Migration)");
                        
                        $mntLoyer = (int)($contrat->getMntLoyer() ?: 0);
                        $facture->setMntFact($mntLoyer);
                        $facture->setSoldeFactLoc(0);
                        $facture->setEncaisse((string)$mntLoyer);
                        $facture->setStatut('payer');
                        $facture->setIsValidated('oui');
                        
                        $facture->setDateEmission($dateFacture);
                        $facture->setDateLimite((clone $dateFacture)->modify('+5 days'));
                        
                        // Définir début et fin de mois pour la facture
                        $dDebut = new \DateTime($dateFacture->format('Y-m-01'));
                        $dFin = new \DateTime($dateFacture->format('Y-m-t'));
                        $facture->setDateDebut($dDebut);
                        $facture->setDateFin($dFin);

                        $facture->setAgence($contrat->getAgence());
                        $facture->setEntreprise($this->getUser()->getEntreprise());

                        $this->updateAuditFields($facture, true);
                        $this->em->persist($facture);
                    }
                }
            }

            $this->em->flush();
            $this->em->commit();

            return $this->responseData([
                'locataire' => $locataire,
                'contrat' => $contrat
            ], 'group1');

        } catch (\Throwable $e) {
            $this->em->rollback();
            return $this->errorResponse(null, $e->getMessage(), 500);
        }
    }
}
