<?php

namespace App\Controller\Apis;

use App\Entity\Appartement;
use App\Entity\ContratLocation;
use App\Entity\Employe;
use App\Entity\FactureLocation;
use App\Entity\Locataire;
use App\Entity\Maison;
use App\Entity\Proprio;
use App\Entity\Reglements;
use App\Entity\User;
use App\Repository\AppartementRepository;
use App\Repository\ContratLocationRepository;
use App\Repository\EmployeRepository;
use App\Repository\FactureLocationRepository;
use App\Repository\LocataireRepository;
use App\Repository\MaisonRepository;
use App\Repository\ProprioRepository;
use App\Repository\ReglementsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use DateTime;
use OpenApi\Attributes as OA;

#[Route('/api/dashboard')]
class ApiDashboardController extends AbstractController
{
    private $em;
    private $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupère les statistiques globales du tableau de bord",
        description: "Fournit une vue d'ensemble enrichie : employees, locataires, propriétés, finances, taux d'occupation, activité récente et évolution mensuelle.",
        parameters: [
            new OA\Parameter(name: "startDate", in: "query", description: "Date de début (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "endDate", in: "query", description: "Date de fin (YYYY-MM-DD)", schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "month", in: "query", description: "Filtre par mois (YYYY-MM)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "semester", in: "query", description: "Filtre par semestre (1 ou 2)", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "year", in: "query", description: "Filtre par année (YYYY)", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Statistiques récupérées avec succès"
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    #[OA\Tag(name: 'Dashboard')]
    public function getStats(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->security->getUser();
            if (!$user) {
                return $this->json(['message' => 'Non authentifié'], 401);
            }

            $entreprise = $user->getEntreprise();

            // === Filters ===
            $startDate = $request->query->get('startDate');
            $endDate   = $request->query->get('endDate');
            $month     = $request->query->get('month');
            $semester  = $request->query->get('semester');
            $year      = $request->query->get('year') ?: date('Y');
            
            // X-Agence-Id context
            $headerAgenceId = $request->query->get('agence_id');
            if ($headerAgenceId === 'null' || $headerAgenceId === 'undefined') {
                $headerAgenceId = null;
            }
            $agenceId = $headerAgenceId ? (int) $headerAgenceId : ($user->getAgence() ? $user->getAgence()->getId() : null);

            $criteria = [];
            if ($entreprise) {
                $criteria['entreprise'] = $entreprise;
            }
            if ($agenceId) {
                $criteria['agence'] = $agenceId;
            }

            // ── Period boundaries (used for "new" counts) ──────────────────────
            [$periodStart, $periodEnd] = $this->resolvePeriodBounds($startDate, $endDate, $month, $semester, $year);

            // === 1. Overview counts ============================================
            $totalEmployees = $this->em->getRepository(Employe::class)->count($criteria);
            $totalTenants   = $this->em->getRepository(Locataire::class)->count($criteria);
            $totalProprios  = $this->em->getRepository(Proprio::class)->count($criteria);
            $totalContracts = $this->em->getRepository(ContratLocation::class)->count($criteria);

            // Active / resiliated contracts
            $qbActiveCt = $this->em->getRepository(ContratLocation::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.etat = 1');
            if ($entreprise) {
                $qbActiveCt->andWhere('c.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $qbActiveCt->andWhere('c.agence = :ag')->setParameter('ag', $agenceId);
            }
            $activeContracts = (int) $qbActiveCt->getQuery()->getSingleScalarResult();

            $resiliatedContracts = $totalContracts - $activeContracts;

            // New contracts in period
            $qbNewCt = $this->em->getRepository(ContratLocation::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.createdAt >= :start AND c.createdAt <= :end')
                ->setParameter('start', $periodStart)
                ->setParameter('end', $periodEnd);
            if ($entreprise) {
                $qbNewCt->andWhere('c.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $qbNewCt->andWhere('c.agence = :ag')->setParameter('ag', $agenceId);
            }
            $newContractsPeriod = (int) $qbNewCt->getQuery()->getSingleScalarResult();

            // New tenants in period
            $qbNewT = $this->em->getRepository(Locataire::class)->createQueryBuilder('l')
                ->select('COUNT(l.id)')
                ->where('l.createdAt >= :start AND l.createdAt <= :end')
                ->setParameter('start', $periodStart)
                ->setParameter('end', $periodEnd);
            if ($entreprise) {
                $qbNewT->andWhere('l.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $qbNewT->andWhere('l.agence = :ag')->setParameter('ag', $agenceId);
            }
            $newTenantsPeriod = (int) $qbNewT->getQuery()->getSingleScalarResult();

            // === 2. Properties & Occupation ====================================
            $qbMaisons = $this->em->getRepository(Maison::class)->createQueryBuilder('m')
                ->select('COUNT(m.id)')
                ->join('m.proprio', 'p');
            if ($entreprise) {
                $qbMaisons->andWhere('p.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $qbMaisons->andWhere('m.agence = :ag')->setParameter('ag', $agenceId);
            }
            $totalMaisons = (int) $qbMaisons->getQuery()->getSingleScalarResult();

            $appartQb = $this->em->getRepository(Appartement::class)->createQueryBuilder('a')
                ->join('a.maisson', 'm')
                ->join('m.proprio', 'p');
            if ($entreprise) {
                $appartQb->andWhere('p.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $appartQb->andWhere('m.agence = :ag')->setParameter('ag', $agenceId);
            }

            $totalAppartements = (int) (clone $appartQb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
            $occupiedAppartements = (int) (clone $appartQb)->select('COUNT(a.id)')->andWhere('a.oqp = 1')->getQuery()->getSingleScalarResult();

            $freeAppartements  = $totalAppartements - $occupiedAppartements;
            $occupancyRate = $totalAppartements > 0
                ? round(($occupiedAppartements / $totalAppartements) * 100, 1)
                : 0;

            // === 3. Financials =================================================
            $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
            if ($entreprise) {
                $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $qb->andWhere('f.agence = :ag')->setParameter('ag', $agenceId);
            }

            // Apply time filter
            if ($startDate && $endDate) {
                $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
                    ->setParameter('start', new DateTime($startDate))
                    ->setParameter('end', new DateTime($endDate . ' 23:59:59'));
            } elseif ($month) {
                $startOfMonth = new DateTime($month . '-01');
                $endOfMonth   = (clone $startOfMonth)->modify('last day of this month');
                $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
                    ->setParameter('start', $startOfMonth)
                    ->setParameter('end', $endOfMonth);
            } elseif ($semester) {
                if ($semester == '1') {
                    $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
                        ->setParameter('start', new DateTime($year . '-01-01'))
                        ->setParameter('end', new DateTime($year . '-06-30 23:59:59'));
                } else {
                    $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
                        ->setParameter('start', new DateTime($year . '-07-01'))
                        ->setParameter('end', new DateTime($year . '-12-31 23:59:59'));
                }
            }

            $factures       = $qb->getQuery()->getResult();
            $totalRevenue   = 0;
            $totalOutstanding = 0;
            $paidCount      = 0;
            $unpaidCount    = 0;

            foreach ($factures as $facture) {
                /** @var FactureLocation $facture */
                $mntFact  = $facture->getMntFact() ?? 0;
                $soldeFact = $facture->getSoldeFactLoc() ?? 0;

                $totalRevenue     += ($mntFact - $soldeFact);
                $totalOutstanding += $soldeFact;

                if ($soldeFact <= 0) {
                    $paidCount++;
                } else {
                    $unpaidCount++;
                }
            }

            // === 4. Recent Activity ============================================
            $recentActivity = $this->getRecentActivity($entreprise, $agenceId, 8);

            // === 5. Monthly chart =============================================
            $chartData = $this->getMonthlyDistribution($entreprise, $agenceId, $year);

            // === 6. Payment collection rate (for current period) ===============
            $totalBilled  = $totalRevenue + $totalOutstanding;
            $collectionRate = $totalBilled > 0 ? round(($totalRevenue / $totalBilled) * 100, 1) : 0;

            return $this->json([
                'overview' => [
                    'totalEmployees'        => $totalEmployees,
                    'totalTenants'          => $totalTenants,
                    'newTenantsPeriod'      => $newTenantsPeriod,
                    'totalProprios'         => $totalProprios,
                    'totalProperties'       => $totalAppartements + $totalMaisons,
                    'totalMaisons'          => $totalMaisons,
                    'totalAppartements'     => $totalAppartements,
                    'occupiedAppartements'  => $occupiedAppartements,
                    'freeAppartements'      => $freeAppartements,
                    'occupancyRate'         => $occupancyRate,
                    'totalContracts'        => $totalContracts,
                    'activeContracts'       => $activeContracts,
                    'resiliatedContracts'   => $resiliatedContracts,
                    'newContractsPeriod'    => $newContractsPeriod,
                ],
                'financials' => [
                    'totalRevenue'          => $totalRevenue,
                    'totalOutstanding'      => $totalOutstanding,
                    'totalBilled'           => $totalBilled,
                    'unpaidInvoicesCount'   => $unpaidCount,
                    'paidInvoicesCount'     => $paidCount,
                    'collectionRate'        => $collectionRate,
                ],
                'charts'         => ['monthlyRevenue' => $chartData],
                'recentActivity' => $recentActivity,
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Une erreur est survenue lors du chargement des statistiques',
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine()
            ], 500);
        }
    }

    // ── Helper: resolve time-period boundaries ─────────────────────────────────
    private function resolvePeriodBounds(?string $startDate, ?string $endDate, ?string $month, ?string $semester, string $year): array
    {
        if ($startDate && $endDate) {
            return [new DateTime($startDate), new DateTime($endDate . ' 23:59:59')];
        }
        if ($month) {
            $s = new DateTime($month . '-01 00:00:00');
            $e = (clone $s)->modify('last day of this month 23:59:59');
            return [$s, $e];
        }
        if ($semester) {
            return $semester == '1'
                ? [new DateTime($year . '-01-01'), new DateTime($year . '-06-30 23:59:59')]
                : [new DateTime($year . '-07-01'), new DateTime($year . '-12-31 23:59:59')];
        }
        // Default: current month
        $now = new DateTime();
        $s   = new DateTime($now->format('Y-m') . '-01 00:00:00');
        $e   = (clone $s)->modify('last day of this month 23:59:59');
        return [$s, $e];
    }

    // ── Helper: recent activity feed ───────────────────────────────────────────
    private function getRecentActivity($entreprise, $agenceId, int $limit = 8): array
    {
        $activity = [];

        // Recent signed contracts
        $qbC = $this->em->getRepository(ContratLocation::class)->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit);
        if ($entreprise) {
            $qbC->andWhere('c.entreprise = :ent')->setParameter('ent', $entreprise);
        }
        if ($agenceId) {
            $qbC->andWhere('c.agence = :ag')->setParameter('ag', $agenceId);
        }
        foreach ($qbC->getQuery()->getResult() as $contrat) {
            /** @var ContratLocation $contrat */
            $appart = $contrat->getAppart();
            $activity[] = [
                'type'    => 'contract',
                'icon'    => 'file-signature',
                'color'   => '#1ABCB7',
                'label'   => 'Contrat ' . ($contrat->getEtat() == 1 ? 'signé' : 'résilié'),
                'detail'  => $appart ? $appart->getLibAppart() : 'Appartement',
                'date'    => $contrat->getCreatedAt() ? $contrat->getCreatedAt()->format('Y-m-d H:i') : null,
            ];
        }

        // Recent payments (reglements)
        $reglements = $this->em->getRepository(Reglements::class)->createQueryBuilder('r')
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();

        foreach ($reglements as $r) {
            /** @var Reglements $r */
            $activity[] = [
                'type'   => 'payment',
                'icon'   => 'cash',
                'color'  => '#32cd32',
                'label'  => 'Loyer reçu',
                'detail' => number_format((float)$r->getMontantVerse(), 0, ',', ' ') . ' FCFA',
                'date'   => $r->getDate() ? date('Y-m-d', $r->getDate()) : null,
            ];
        }

        // Sort all by date desc (approximate, based on contract createdAt)
        usort($activity, function ($a, $b) {
            return strcmp($b['date'] ?? '', $a['date'] ?? '');
        });

        return array_slice($activity, 0, $limit);
    }

    // ── Helper: monthly revenue distribution ──────────────────────────────────
    private function getMonthlyDistribution($entreprise, $agenceId, $year): array
    {
        $start = new DateTime($year . '-01-01 00:00:00');
        $end   = new DateTime($year . '-12-31 23:59:59');

        $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
        $qb->select('f.dateEmission', 'f.mntFact', 'f.soldeFactLoc')
            ->where('f.dateEmission >= :start')
            ->andWhere('f.dateEmission <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($entreprise) {
            $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
        }
        if ($agenceId) {
            $qb->andWhere('f.agence = :ag')->setParameter('ag', $agenceId);
        }

        $results = $qb->getQuery()->getResult();

        $indexedRevenue      = [];
        $indexedOutstanding  = [];
        foreach ($results as $res) {
            $date = $res['dateEmission'];
            if ($date instanceof \DateTimeInterface) {
                $mKey   = $date->format('Y-m');
                $revenue = ($res['mntFact'] ?? 0) - ($res['soldeFactLoc'] ?? 0);
                $outstanding = $res['soldeFactLoc'] ?? 0;

                $indexedRevenue[$mKey]     = ($indexedRevenue[$mKey] ?? 0) + $revenue;
                $indexedOutstanding[$mKey] = ($indexedOutstanding[$mKey] ?? 0) + $outstanding;
            }
        }

        $fullYear = [];
        for ($m = 1; $m <= 12; $m++) {
            $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
            $key  = $year . '-' . $mStr;
            $fullYear[] = [
                'name'        => $this->getMonthName($m),
                'revenue'     => $indexedRevenue[$key] ?? 0,
                'outstanding' => $indexedOutstanding[$key] ?? 0,
            ];
        }

        return $fullYear;
    }

    private function getMonthName($m): string
    {
        $months = [
            1  => 'Jan',
            2  => 'Fév',
            3  => 'Mar',
            4  => 'Avr',
            5  => 'Mai',
            6  => 'Juin',
            7  => 'Juil',
            8  => 'Aoû',
            9  => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc',
        ];
        return $months[$m];
    }

    #[Route('/locataire/stats', name: 'api_dashboard_locataire_stats', methods: ['GET'])]
    #[OA\Get(
        summary: "Récupère les statistiques personnelles du locataire connecté",
        description: "Fournit les données financières (loyers payés/impayés, investissement total), l'état du contrat et les transactions récentes pour le tableau de bord locataire.",
        responses: [
            new OA\Response(
                response: 200,
                description: "Données locataire récupérées avec succès"
            ),
            new OA\Response(response: 404, description: "Profil locataire non trouvé"),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    #[OA\Tag(name: 'Dashboard')]
    public function getLocataireStats(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->security->getUser();
            if (!$user) {
                return $this->json(['message' => 'Non authentifié'], 401);
            }
            if (!$user->getLocataire()) {
                return $this->json([
                    'financials' => [
                        'totalUnpaid'     => 0,
                        'totalPaid'       => 0,
                        'unpaidCount'     => 0,
                        'paidCount'       => 0,
                        'nextPaymentDate' => null,
                        'totalInvested'   => 0,
                        'lastPaymentDate' => null,
                    ],
                    'contract'           => null,
                    'presenceDays'       => 0,
                    'recentTransactions' => [],
                ], 200);
            }

            $locataire = $user->getLocataire();

            // 1. Financials
            $factures     = $this->em->getRepository(FactureLocation::class)->findBy(['locataire' => $locataire]);
            $totalUnpaid  = 0;
            $totalPaid    = 0;
            $unpaidCount  = 0;
            $paidCount    = 0;
            $oldestUnpaidDate = null;

            foreach ($factures as $facture) {
                $solde = (float) $facture->getSoldeFactLoc();
                $totalUnpaid += $solde;
                $totalPaid   += ((float)$facture->getMntFact() - $solde);

                if ($solde > 0) {
                    $unpaidCount++;
                    if (!$oldestUnpaidDate || $facture->getDateLimite() < $oldestUnpaidDate) {
                        $oldestUnpaidDate = $facture->getDateLimite();
                    }
                } else {
                    if ($facture->getMntFact() > 0) {
                        $paidCount++;
                    }
                }
            }

            // 2. Active Contract
            $contrat = $this->em->getRepository(ContratLocation::class)->findOneBy(['locataire' => $locataire, 'etat' => 1]);

            // 3. Transactions
            $transactions = $this->em->getRepository(\App\Entity\Transaction::class)->findBy(
                ['locataire' => $locataire],
                ['date' => 'DESC'],
                5
            );

            // 4. Extra stats
            $totalInvested   = 0;
            $lastPaymentDate = null;
            $allTransactions = $this->em->getRepository(\App\Entity\Transaction::class)->findBy(['locataire' => $locataire, 'status' => 'SUCCESS']);
            foreach ($allTransactions as $t) {
                $totalInvested += (float)$t->getAmount();
                if (!$lastPaymentDate || $t->getDate() > $lastPaymentDate) {
                    $lastPaymentDate = $t->getDate();
                }
            }

            $presenceDays = 0;
            if ($contrat && $contrat->getDateDebut()) {
                $now  = new \DateTime();
                $diff = $now->diff($contrat->getDateDebut());
                $presenceDays = $diff->days;
            }

            $contratData = null;
            if ($contrat) {
                $appart = $contrat->getAppart();
                $maison = $appart ? $appart->getMaisson() : null;
                $contratData = [
                    'id'         => $contrat->getId(),
                    'dateDebut'  => $contrat->getDateDebut()  ? $contrat->getDateDebut()->format('Y-m-d')  : null,
                    'dateFin'    => $contrat->getDateFin()    ? $contrat->getDateFin()->format('Y-m-d')    : null,
                    'mntLoyer'   => $contrat->getMntLoyer(),
                    'mntCaution' => $contrat->getMntCaution(),
                    'dateEntree' => $contrat->getDateEntree() ? $contrat->getDateEntree()->format('Y-m-d') : null,
                    'etat'       => $contrat->getEtat(),
                    'appart'     => $appart ? [
                        'id'        => $appart->getId(),
                        'libAppart' => $appart->getLibAppart(),
                        'maisson'   => $maison ? [
                            'id'        => $maison->getId(),
                            'libMaison' => $maison->getLibMaison(),
                        ] : null,
                    ] : null,
                ];
            }

            $transactionsData = array_map(function ($t) {
                return [
                    'id'     => $t->getId(),
                    'amount' => $t->getAmount(),
                    'date'   => $t->getDate() ? $t->getDate()->format('Y-m-d H:i:s') : null,
                    'mode'   => $t->getMode(),
                    'status' => $t->getStatus(),
                ];
            }, $transactions);

            return $this->json([
                'financials' => [
                    'totalUnpaid'     => $totalUnpaid,
                    'totalPaid'       => $totalPaid,
                    'unpaidCount'     => $unpaidCount,
                    'paidCount'       => $paidCount,
                    'nextPaymentDate' => $oldestUnpaidDate ? $oldestUnpaidDate->format('Y-m-d') : null,
                    'totalInvested'   => $totalInvested,
                    'lastPaymentDate' => $lastPaymentDate ? $lastPaymentDate->format('Y-m-d H:i:s') : null,
                ],
                'contract'           => $contratData,
                'presenceDays'       => $presenceDays,
                'recentTransactions' => $transactionsData,
            ], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/platform', name: 'api_dashboard_platform', methods: ['GET'])]
    #[OA\Get(summary: "Statistiques globales plateformes pour SADM", tags: ['Dashboard'])]
    public function getPlatformStats(Request $request): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            if (!$user) {
                return $this->json(['message' => 'Non authentifié'], 401);
            }

            // Total Entreprises
            $totalEntreprises = (int) $this->em->createQuery('SELECT COUNT(e.id) FROM App\Entity\Entreprise e')->getSingleScalarResult();

            // Total Agences
            $totalAgences = (int) $this->em->createQuery('SELECT COUNT(a.id) FROM App\Entity\Agence a')->getSingleScalarResult();

            // Total Utilisateurs
            $totalUsers = (int) $this->em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u WHERE u.isActive = true OR u.isActive = 1')->getSingleScalarResult();

            // Total Maisons (Sites)
            $totalMaisons = (int) $this->em->createQuery('SELECT COUNT(m.id) FROM App\Entity\Maison m')->getSingleScalarResult();

            // Total Appartements
            $totalAppartements = (int) $this->em->createQuery('SELECT COUNT(a.id) FROM App\Entity\Appartement a')->getSingleScalarResult();

            // Total Locataires
            $totalLocataires = (int) $this->em->createQuery('SELECT COUNT(l.id) FROM App\Entity\Locataire l')->getSingleScalarResult();

            // Total Propriétaires
            $totalProprietaires = (int) $this->em->createQuery('SELECT COUNT(p.id) FROM App\Entity\Proprio p')->getSingleScalarResult();

            // Contrats actifs
            $totalContratsActifs = (int) $this->em->createQuery('SELECT COUNT(c.id) FROM App\Entity\ContratLocation c WHERE c.etat = 1')->getSingleScalarResult();

            // Chiffre d'affaire global estimé (Loyer mensuel * Contrats Actifs) - Simplification
            $totalLoyerMensuel = (float) $this->em->createQuery('SELECT SUM(c.mntLoyer) FROM App\Entity\ContratLocation c WHERE c.etat = 1')->getSingleScalarResult();

            // --- Charts Data Preparation ---
            // 1. Evolution Inscriptions (Derniers 6 mois)
            $sixMonthsAgo = new \DateTime('-6 months');
            $enterprises = $this->em->createQuery('SELECT e.dateCreation as dateCreation FROM App\Entity\Entreprise e WHERE e.dateCreation >= :date ORDER BY e.dateCreation ASC')
                ->setParameter('date', $sixMonthsAgo)
                ->getResult();
            
            $users = $this->em->createQuery('SELECT u.createdAt as createdAt FROM App\Entity\User u WHERE u.createdAt >= :date ORDER BY u.createdAt ASC')
                ->setParameter('date', $sixMonthsAgo)
                ->getResult();

            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = (new \DateTime("-$i months"))->format('Y-m');
                $months[$m] = ['name' => $m, 'entreprises' => 0, 'utilisateurs' => 0];
            }

            foreach ($enterprises as $e) {
                if ($e['dateCreation']) {
                    $m = $e['dateCreation']->format('Y-m');
                    if (isset($months[$m])) $months[$m]['entreprises']++;
                }
            }

            foreach ($users as $u) {
                if ($u['createdAt']) {
                    $m = $u['createdAt']->format('Y-m');
                    if (isset($months[$m])) $months[$m]['utilisateurs']++;
                }
            }
            $evolutionChart = array_values($months);

            // 2. Statut des Contrats
            $contratsByEtat = $this->em->createQuery('SELECT c.etat, COUNT(c.id) as count FROM App\Entity\ContratLocation c GROUP BY c.etat')->getResult();
            $contratChart = [];
            foreach ($contratsByEtat as $c) {
                $label = 'Actif';
                if ($c['etat'] == 0) $label = 'En attente/Brouillon';
                if ($c['etat'] == 2) $label = 'Résilié';
                $contratChart[] = ['name' => $label, 'value' => (int)$c['count']];
            }

            return $this->json([
                'overview' => [
                    'totalEntreprises' => $totalEntreprises,
                    'totalAgences' => $totalAgences,
                    'totalUsers' => $totalUsers,
                    'totalMaisons' => $totalMaisons,
                    'totalAppartements' => $totalAppartements,
                    'totalLocataires' => $totalLocataires,
                    'totalProprietaires' => $totalProprietaires,
                    'totalContratsActifs' => $totalContratsActifs,
                    'chiffreAffaireMensuelGbl' => $totalLoyerMensuel,
                ],
                'charts' => [
                    'evolution' => $evolutionChart,
                    'contrats' => $contratChart,
                    'parc' => [
                        ['name' => 'Maisons', 'value' => $totalMaisons],
                        ['name' => 'Appartements', 'value' => $totalAppartements]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/arrears', name: 'api_dashboard_arrears', methods: ['GET'])]
    #[OA\Get(summary: "Liste des factures en retard (pour dashboard agent)", tags: ['Dashboard'])]
    public function getArrears(Request $request, FactureLocationRepository $repository, EntityManagerInterface $em, Security $security): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $security->getUser();
            if (!$user) return $this->json(['message' => 'Non authentifié'], 401);

            $entreprise = $user->getEntreprise();
            
            // X-Agence-Id context
            $headerAgenceId = $request->query->get('agence_id');
            $agenceId = ($headerAgenceId && $headerAgenceId !== 'all' && $headerAgenceId !== 'null' && $headerAgenceId !== 'undefined') ? (int) $headerAgenceId : ($user->getAgence() ? $user->getAgence()->getId() : null);

            $qb = $repository->createQueryBuilder('f')
                ->where('f.soldeFactLoc > 0')
                ->join('f.locataire', 'l')
                ->orderBy('f.dateLimite', 'ASC');

            if ($entreprise) {
                $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
            }
            if ($agenceId) {
                $qb->andWhere('f.agence = :ag')->setParameter('ag', $agenceId);
            }

            $factures = $qb->setMaxResults(10)->getQuery()->getResult();

            $data = [];
            foreach ($factures as $f) {
                $data[] = [
                    'locataire' => [
                        'nom_complet' => $f->getLocataire()->getNom() . ' ' . $f->getLocataire()->getPrenoms(),
                        'nom' => $f->getLocataire()->getNom(),
                    ],
                    'loyer' => [
                        'maison' => [
                            'libMaison' => $f->getAppartement() ? ($f->getAppartement()->getMaisson() ? $f->getAppartement()->getMaisson()->getLibMaison() : 'N/A' ) : 'N/A'
                        ]
                    ],
                    'lastRelance' => null, // À implémenter plus tard
                    'montantRestant' => (float)$f->getSoldeFactLoc(),
                    'echeanceCount' => 1,
                ];
            }

            return $this->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 500);
        }
    }
}
