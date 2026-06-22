<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\DemarcheAdministrative;
use App\Entity\EchancierTerrain;
use App\Entity\EtapeDemarche;
use App\Entity\Terrain;
use App\Entity\TypeEtapeDemarche;
use App\Entity\VenteTerrain;
use App\Entity\VersementTerrain;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vente-terrain')]
#[OA\Tag(name: 'VenteTerrain', description: 'Gestion des ventes de terrains et démarches')]
class ApiVenteTerrainController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Entreprise non trouvée", 400);
            }

            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            $agence = $isSuperAdmin ? null : $user->getAgence();
            $qb = $em->getRepository(VenteTerrain::class)->createQueryBuilder('v')
                ->innerJoin('v.client', 'c')
                ->addSelect('c')
                ->innerJoin('v.terrain', 't')
                ->addSelect('t')
                ->where('v.entreprise = :entreprise')
                ->setParameter('entreprise', $user->getEntreprise());

            if ($agence) {
                $qb->andWhere('v.agence = :agence')
                   ->setParameter('agence', $agence);
            }

            $ventes = $qb->getQuery()->getResult();

            return $this->responseData($ventes, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $terrain = $em->getRepository(Terrain::class)->find($data['terrain_id']);
            $client = $em->getRepository(\App\Entity\ClientTerrain::class)->find($data['client_id']);

            if (!$terrain || !$client) {
                return $this->errorResponse(null, "Terrain ou Client introuvable", 404);
            }

            if ($terrain->getEtat() === 'vendu') {
                return $this->errorResponse(null, "Ce terrain est déjà vendu", 400);
            }

            $vente = new VenteTerrain();
            $vente->setTerrain($terrain);
            $vente->setClient($client);
            $vente->setPrixVente($data['prixVente']);
            $vente->setApportInitial($data['apportInitial']);
            
            $reste = (float)$data['prixVente'] - (float)$data['apportInitial'];
            $vente->setResteAPayer((string)$reste);
            
            if (isset($data['typeVente'])) {
                $vente->setTypeVente($data['typeVente']);
            }

            $vente->setEntreprise($user->getEntreprise());
            $agence = $user->getAgence() ?? $em->getRepository(\App\Entity\Agence::class)->find($data['agence_id'] ?? 0);
            if ($agence) $vente->setAgence($agence);

            $this->updateAuditFields($vente, true);
            $em->persist($vente);

            // Génération de l'échéancier si reste à payer
            if ($reste > 0 && isset($data['nbMois']) && (int)$data['nbMois'] > 0) {
                $nbMois = (int)$data['nbMois'];
                $montantMensuel = $reste / $nbMois;
                
                for ($i = 1; $i <= $nbMois; $i++) {
                    $echancier = new EchancierTerrain();
                    $datePrevue = new \DateTime();
                    $datePrevue->modify("+$i month");
                    $echancier->setDatePrevue($datePrevue);
                    $echancier->setMontant((string)$montantMensuel);
                    $echancier->setVenteTerrain($vente);
                    $em->persist($echancier);
                }
            }

            // Gestion de l'apport comme premier versement
            if ((float)$data['apportInitial'] > 0) {
                $versement = new VersementTerrain();
                $versement->setMontant($data['apportInitial']);
                $versement->setVenteTerrain($vente);
                $versement->setModePaiement($data['modePaiement'] ?? 'Espece');
                $versement->setReference('Apport Initial');
                $em->persist($versement);
            }

            // Changer le statut du terrain
            $terrain->setEtat('vendu');
            if ($terrain->getSite()) {
                $terrain->getSite()->updateEtatAutomatique();
                $em->persist($terrain->getSite());
            }
            $em->persist($terrain);

            // Si c'est une gestion agence, on crée la démarche administrative
            if ($vente->getTypeVente() === 'sans_papier_gestion_agence') {
                $demarche = new DemarcheAdministrative();
                $demarche->setTitre("Démarches pour lot " . $terrain->getNum());
                $demarche->setVenteTerrain($vente);
                $demarche->setFraisEstimes($data['fraisEstimes'] ?? '0');
                $em->persist($demarche);

                // ─── Récupération des étapes paramétrées par l'entreprise ───
                $typesEtapes = $em->getRepository(TypeEtapeDemarche::class)->findBy(
                    ['entreprise' => $user->getEntreprise(), 'isActif' => true],
                    ['ordre' => 'ASC']
                );

                // Si aucune étape n'est configurée → injecter les 7 étapes par défaut
                if (count($typesEtapes) === 0) {
                    foreach (TypeEtapeDemarche::DEFAULTS as $def) {
                        $typeEtape = new TypeEtapeDemarche();
                        $typeEtape->setNom($def['nom']);
                        $typeEtape->setDescription($def['description']);
                        $typeEtape->setOrdre($def['ordre']);
                        $typeEtape->setIsActif(true);
                        $typeEtape->setEntreprise($user->getEntreprise());
                        $em->persist($typeEtape);
                        $typesEtapes[] = $typeEtape;
                    }
                    $em->flush(); // flush pour avoir les IDs des types
                }

                // Créer une EtapeDemarche par type actif (liée au TypeEtapeDemarche)
                $selectedIds = $data['etapes_selectionnees'] ?? null;
                
                foreach ($typesEtapes as $typeEtape) {
                    if ($selectedIds !== null && !in_array($typeEtape->getId(), $selectedIds)) {
                        continue;
                    }
                    $etape = new EtapeDemarche();
                    $etape->setTypeEtape($typeEtape);
                    $etape->setNomEtape($typeEtape->getNom()); // fallback
                    $etape->setDemarche($demarche);
                    $em->persist($etape);

                    // Générer les FraisVenteTerrain associés à ce TypeEtape
                    $fraisTypes = $em->getRepository(\App\Entity\TypeFraisTerrain::class)->findBy([
                        'typeEtapeDemarche' => $typeEtape,
                        'isActif' => true
                    ]);
                    $fraisPersonnalises = $data['frais_personnalises'] ?? [];

                    foreach ($fraisTypes as $fraisType) {
                        $fraisVente = new \App\Entity\FraisVenteTerrain();
                        $fraisVente->setVenteTerrain($vente);
                        $fraisVente->setTypeFrais($fraisType);
                        $fraisVente->setEtapeDemarche($etape);
                        
                        $montantDefaut = $fraisType->getMontantDefaut() ?? '0';
                        $montantFinal = isset($fraisPersonnalises[$fraisType->getId()]) 
                            ? $fraisPersonnalises[$fraisType->getId()] 
                            : $montantDefaut;
                            
                        $fraisVente->setMontant((string)$montantFinal);
                        $fraisVente->setStatutPaiement('non_paye');
                        $fraisVente->setEntreprise($vente->getEntreprise());
                        $em->persist($fraisVente);
                    }
                }
            }

            $em->flush();

            return $this->responseData($vente, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/versement', methods: ['POST'])]
    public function addVersement(Request $request, VenteTerrain $vente, EntityManagerInterface $em): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $montant = (float)$data['montant'];
            if ($montant <= 0) {
                return $this->errorResponse(null, "Montant invalide", 400);
            }

            $versement = new VersementTerrain();
            $versement->setMontant((string)$montant);
            $versement->setVenteTerrain($vente);
            $versement->setModePaiement($data['modePaiement'] ?? 'Espece');
            $versement->setReference($data['reference'] ?? null);
            
            $em->persist($versement);

            // Mise à jour du reste à payer
            $nouveauReste = (float)$vente->getResteAPayer() - $montant;
            $vente->setResteAPayer((string)$nouveauReste);
            
            if ($nouveauReste <= 0) {
                $vente->setEtat('solde');
            }

            // Valider les échéances concernées (logique simplifiée)
            foreach ($vente->getEchanciers() as $ech) {
                if ($ech->getEtat() === 'en_attente' && $montant > 0) {
                    // Si le versement couvre l'échéance (simplifié, on marque l'échéance payée si on a de l'argent)
                    $ech->setEtat('paye');
                    $montant -= (float)$ech->getMontant();
                }
            }

            $em->flush();

            return $this->responseData($versement, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/demarche/{etapeId}/commencer', methods: ['POST'])]
    public function commencerEtape(int $etapeId, EntityManagerInterface $em): Response
    {
        try {
            $etape = $em->getRepository(EtapeDemarche::class)->find($etapeId);
            if (!$etape) return $this->errorResponse(null, "Étape non trouvée", 404);

            $etape->setStatut('en_cours');
            $em->flush();

            return $this->response(['message' => 'Étape passée en cours.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/demarche/{etapeId}/valider', methods: ['POST'])]
    public function validerEtape(int $etapeId, EntityManagerInterface $em): Response
    {
        try {
            $etape = $em->getRepository(EtapeDemarche::class)->find($etapeId);
            if (!$etape) return $this->errorResponse(null, "Étape non trouvée", 404);

            $etape->setStatut('termine');
            $etape->setDateValidation(new \DateTime());
            
            // ICI: Déclencher l'Event / Mailer pour notifier le client
            // $mailerService->sendDemarcheUpdateEmail($etape->getDemarche()->getVenteTerrain()->getClient(), $etape);

            $em->flush();

            return $this->response(['message' => 'Étape validée avec succès. Le client a été notifié.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
    #[Route('/stats', methods: ['GET'])]
    public function stats(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Entreprise non trouvée", 400);
            }

            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            $agence = $isSuperAdmin ? null : $user->getAgence();

            $year = $request->query->get('year', date('Y'));
            $month = $request->query->get('month');
            $semester = $request->query->get('semester');

            // 1. Lots
            $qbTerrain = $em->getRepository(Terrain::class)->createQueryBuilder('t')
                ->where('t.entreprise = :entreprise')
                ->setParameter('entreprise', $user->getEntreprise());
            if ($agence) {
                $qbTerrain->andWhere('t.agence = :agence')->setParameter('agence', $agence);
            }
            $terrains = $qbTerrain->getQuery()->getResult();
            $totalTerrains = count($terrains);
            $terrainsDispo = 0;
            $terrainsVendus = 0;
            foreach ($terrains as $t) {
                if ($t->getEtat() === 'disponible') $terrainsDispo++;
                elseif ($t->getEtat() === 'vendu') $terrainsVendus++;
            }

            // 2. Ventes
            $qbVente = $em->getRepository(VenteTerrain::class)->createQueryBuilder('v')
                ->where('v.entreprise = :entreprise')
                ->setParameter('entreprise', $user->getEntreprise());
            if ($agence) {
                $qbVente->andWhere('v.agence = :agence')->setParameter('agence', $agence);
            }
            $ventes = $qbVente->getQuery()->getResult();
            $totalVentes = count($ventes);
            
            $clientIds = [];
            foreach ($ventes as $v) {
                if ($v->getClient()) $clientIds[$v->getClient()->getId()] = true;
            }
            $totalClients = count($clientIds);

            // Filtrage Ventes pour les KPIs financiers
            $caGlobal = 0;
            $resteARecouvrer = 0;
            $ventesParMois = [];
            
            foreach ($ventes as $v) {
                $dateVente = $v->getCreatedAt() ?: new \DateTime();
                $vYear = $dateVente->format('Y');
                $vMonth = $dateVente->format('m');
                
                // Mettre à jour les ventes du mois (seulement pour l'année sélectionnée)
                if ($vYear === $year) {
                    $mInt = (int)$vMonth;
                    if (!isset($ventesParMois[$mInt])) {
                        $ventesParMois[$mInt] = ['revenue' => 0, 'count' => 0];
                    }
                    $ventesParMois[$mInt]['count']++;
                    $ventesParMois[$mInt]['revenue'] += (float)$v->getPrixVente();
                }

                // Filtrage Période pour les KPIs financiers
                $keep = true;
                if ($year && $vYear !== $year) $keep = false;
                if ($keep && $month && $vMonth !== str_pad($month, 2, '0', STR_PAD_LEFT)) $keep = false;
                if ($keep && $semester) {
                    if ($semester == '1' && (int)$vMonth > 6) $keep = false;
                    if ($semester == '2' && (int)$vMonth <= 6) $keep = false;
                }

                if ($keep) {
                    $caGlobal += (float)$v->getPrixVente();
                    $resteARecouvrer += (float)$v->getResteAPayer();
                }
            }
            
            $encaisse = $caGlobal - $resteARecouvrer;

            // Formater charts
            $monthlyRevenue = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthlyRevenue[] = [
                    'month' => $i,
                    'count' => $ventesParMois[$i]['count'] ?? 0,
                    'revenue' => $ventesParMois[$i]['revenue'] ?? 0
                ];
            }

            return $this->response([
                'overview' => [
                    'totalTerrains' => $totalTerrains,
                    'terrainsDispo' => $terrainsDispo,
                    'terrainsVendus' => $terrainsVendus,
                    'totalVentes' => $totalVentes,
                    'totalClients' => $totalClients
                ],
                'financials' => [
                    'caGlobal' => $caGlobal,
                    'encaisse' => $encaisse,
                    'resteARecouvrer' => $resteARecouvrer
                ],
                'charts' => [
                    'monthlyRevenue' => $monthlyRevenue,
                    'lotsStatus' => [
                        ['name' => 'Disponibles', 'value' => $terrainsDispo, 'color' => '#10B981'],
                        ['name' => 'Vendus', 'value' => $terrainsVendus, 'color' => '#8B5CF6'],
                        ['name' => 'Autres', 'value' => $totalTerrains - $terrainsDispo - $terrainsVendus, 'color' => '#F59E0B']
                    ]
                ]
            ]);

        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
