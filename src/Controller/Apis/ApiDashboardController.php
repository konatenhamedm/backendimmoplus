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
        description: "Fournit une vue d'ensemble des employés, locataires, propriétés et finances pour l'entreprise connectée. Supporte le filtrage par date.",
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
                description: "Statistiques récupérées avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "overview", type: "object", properties: [
                            new OA\Property(property: "totalEmployees", type: "integer"),
                            new OA\Property(property: "totalTenants", type: "integer"),
                            new OA\Property(property: "totalProprios", type: "integer"),
                            new OA\Property(property: "totalProperties", type: "integer"),
                            new OA\Property(property: "totalContracts", type: "integer"),
                        ]),
                        new OA\Property(property: "financials", type: "object", properties: [
                            new OA\Property(property: "totalRevenue", type: "number"),
                            new OA\Property(property: "totalOutstanding", type: "number"),
                            new OA\Property(property: "unpaidInvoicesCount", type: "integer"),
                            new OA\Property(property: "paidInvoicesCount", type: "integer"),
                        ]),
                        new OA\Property(property: "charts", type: "object", properties: [
                            new OA\Property(property: "monthlyRevenue", type: "array", items: new OA\Items(type: "object"))
                        ])
                    ]
                )
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
            
            // Filters
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
            $month = $request->query->get('month'); // Expecting format like '2024-05'
            $semester = $request->query->get('semester'); // '1' or '2'
            $year = $request->query->get('year') ?: date('Y');

            $criteria = [];
            if ($entreprise) {
                $criteria['entreprise'] = $entreprise;
            }

            // Count basic entities
            $totalEmployees = $this->em->getRepository(Employe::class)->count($criteria);
            $totalTenants = $this->em->getRepository(Locataire::class)->count($criteria);
            $totalProprios = $this->em->getRepository(Proprio::class)->count($criteria);
            $totalContracts = $this->em->getRepository(ContratLocation::class)->count($criteria);

            if ($entreprise) {
                $totalMaisons = (int) $this->em->getRepository(Maison::class)->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->join('m.proprio', 'p')
                    ->where('p.entreprise = :ent')
                    ->setParameter('ent', $entreprise)
                    ->getQuery()
                    ->getSingleScalarResult();

                $totalAppartements = (int) $this->em->getRepository(Appartement::class)->createQueryBuilder('a')
                    ->select('COUNT(a.id)')
                    ->join('a.maisson', 'm')
                    ->join('m.proprio', 'p')
                    ->where('p.entreprise = :ent')
                    ->setParameter('ent', $entreprise)
                    ->getQuery()
                    ->getSingleScalarResult();
            } else {
                $totalAppartements = $this->em->getRepository(Appartement::class)->count([]);
                $totalMaisons = $this->em->getRepository(Maison::class)->count([]);
            }

            // Revenue query
            $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
            if ($entreprise) {
                $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
            }

            // Apply time filters to invoices
            if ($startDate && $endDate) {
                $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
                   ->setParameter('start', new DateTime($startDate))
                   ->setParameter('end', new DateTime($endDate . ' 23:59:59'));
            } elseif ($month) {
                $startOfMonth = new DateTime($month . '-01');
                $endOfMonth = clone $startOfMonth;
                $endOfMonth->modify('last day of this month');
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

            $factures = $qb->getQuery()->getResult();

            $totalRevenue = 0;
            $totalOutstanding = 0;
            $paidCount = 0;
            $unpaidCount = 0;

            foreach ($factures as $facture) {
                /** @var FactureLocation $facture */
                $mntFact = $facture->getMntFact() ?? 0;
                $soldeFact = $facture->getSoldeFactLoc() ?? 0;

                $totalRevenue += ($mntFact - $soldeFact);
                $totalOutstanding += $soldeFact;
                
                if ($soldeFact <= 0) {
                    $paidCount++;
                } else {
                    $unpaidCount++;
                }
            }

            // Monthly distribution for charts (last 6 months or filtered year)
            $chartData = $this->getMonthlyDistribution($entreprise, $year);

            return $this->json([
                'overview' => [
                    'totalEmployees' => $totalEmployees,
                    'totalTenants' => $totalTenants,
                    'totalProprios' => $totalProprios,
                    'totalProperties' => $totalAppartements + $totalMaisons,
                    'totalContracts' => $totalContracts,
                ],
                'financials' => [
                    'totalRevenue' => $totalRevenue,
                    'totalOutstanding' => $totalOutstanding,
                    'unpaidInvoicesCount' => $unpaidCount,
                    'paidInvoicesCount' => $paidCount,
                ],
                'charts' => [
                    'monthlyRevenue' => $chartData,
                ]
            ], 200, [], ['groups' => ['group1']]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Une erreur est survenue lors du chargement des statistiques',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    private function getMonthlyDistribution($entreprise, $year)
    {
        $start = new DateTime($year . '-01-01 00:00:00');
        $end = new DateTime($year . '-12-31 23:59:59');

        $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
        $qb->select('f.dateEmission', 'f.mntFact', 'f.soldeFactLoc')
           ->where('f.dateEmission >= :start')
           ->andWhere('f.dateEmission <= :end')
           ->setParameter('start', $start)
           ->setParameter('end', $end);

        if ($entreprise) {
            $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
        }

        $results = $qb->getQuery()->getResult();
        
        $indexedResults = [];
        foreach ($results as $res) {
             $date = $res['dateEmission'];
             if ($date instanceof \DateTimeInterface) {
                 $month = $date->format('Y-m');
                 $amount = $res['mntFact'] - $res['soldeFactLoc'];
                 
                 if (!isset($indexedResults[$month])) {
                     $indexedResults[$month] = 0;
                 }
                 $indexedResults[$month] += $amount;
             }
        }

        $fullYear = [];
        for ($m = 1; $m <= 12; $m++) {
            $mStr = str_pad($m, 2, '0', STR_PAD_LEFT);
            $key = $year . '-' . $mStr;
            $fullYear[] = [
                'name' => $this->getMonthName($m),
                'revenue' => $indexedResults[$key] ?? 0
            ];
        }

        return $fullYear;
    }

    private function getMonthName($m)
    {
        $months = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Aoû', 
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
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
                description: "Données locataire récupérées avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "financials", type: "object", properties: [
                            new OA\Property(property: "totalUnpaid", type: "number"),
                            new OA\Property(property: "totalPaid", type: "number"),
                            new OA\Property(property: "unpaidCount", type: "integer"),
                            new OA\Property(property: "paidCount", type: "integer"),
                            new OA\Property(property: "nextPaymentDate", type: "string", format: "date", nullable: true),
                            new OA\Property(property: "totalInvested", type: "number"),
                            new OA\Property(property: "lastPaymentDate", type: "string", format: "date-time", nullable: true),
                        ]),
                        new OA\Property(property: "contract", type: "object", nullable: true),
                        new OA\Property(property: "presenceDays", type: "integer"),
                        new OA\Property(property: "recentTransactions", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
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
            if (!$user || !$user->getLocataire()) {
                // Retourner une structure vide plutôt qu'un 404 pour éviter l'erreur côté client
                if (!$user) {
                    return $this->json(['message' => 'Non authentifié'], 401);
                }
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
            $factures = $this->em->getRepository(FactureLocation::class)->findBy(['locataire' => $locataire]);
            $totalUnpaid = 0;
            $totalPaid = 0;
            $unpaidCount = 0;
            $paidCount = 0;
            $oldestUnpaidDate = null;

            foreach ($factures as $facture) {
                $solde = (float) $facture->getSoldeFactLoc();
                $totalUnpaid += $solde;
                $totalPaid += ((float)$facture->getMntFact() - $solde);
                
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
            $totalInvested = 0;
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
                $now = new \DateTime();
                $diff = $now->diff($contrat->getDateDebut());
                $presenceDays = $diff->days;
            }

            // ── Sérialisation manuelle pour éviter les références circulaires ──
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
}
