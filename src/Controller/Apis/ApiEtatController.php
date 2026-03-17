<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\FactureLocation;
use App\Entity\Maison;
use App\Entity\Paiement;
use App\Entity\Reglements;
use App\Entity\Proprio;
use App\Entity\Transaction;
use App\Entity\VersmtProprio;
use App\Entity\Campagne;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/etat')]
#[OA\Tag(name: 'Etats', description: 'Génération d\'états et rapports')]
class ApiEtatController extends ApiInterface
{
    #[Route('/finance', methods: ['GET'])]
    #[OA\Get(
        path: "/api/etat/finance",
        summary: "Données pour l'état financier",
        description: "Retourne les agrégations financières pour une période donnée.",
        tags: ['Etats']
    )]
    #[OA\Parameter(name: "startDate", in: "query", schema: new OA\Schema(type: "string", format: "date"))]
    #[OA\Parameter(name: "endDate", in: "query", schema: new OA\Schema(type: "string", format: "date"))]
    public function getFinanceData(Request $request): Response
    {
        try {
            $entreprise = $this->getUser()->getEntreprise();
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');

            $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
            if ($entreprise) {
                $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
            }

            if ($startDate && $endDate) {
                $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
                   ->setParameter('start', new \DateTime($startDate))
                   ->setParameter('end', new \DateTime($endDate . ' 23:59:59'));
            }

            $factures = $qb->getQuery()->getResult();

            $totalRevenue = 0;
            $totalOutstanding = 0;
            $paidAmount = 0;

            foreach ($factures as $f) {
                $totalRevenue += (float)$f->getMntFact();
                $totalOutstanding += (float)$f->getSoldeFactLoc();
                $paidAmount += ((float)$f->getMntFact() - (float)$f->getSoldeFactLoc());
            }

            return $this->response([
                'summary' => [
                    'total_billed' => $totalRevenue,
                    'total_paid' => $paidAmount,
                    'total_outstanding' => $totalOutstanding,
                    'collection_rate' => $totalRevenue > 0 ? round(($paidAmount / $totalRevenue) * 100, 2) : 0
                ]
            ]);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/analysis', methods: ['GET'])]
    public function getAnalysisData(Request $request): Response
    {
        try {
            $entreprise = $this->getUser()->getEntreprise();
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');
            $siteId = $request->query->get('siteId');
            if ($siteId === 'all') $siteId = null;

            $invoiceData = $this->fetchInvoiceStatus($startDate, $endDate, $entreprise, $siteId);
            $payoutData = $this->fetchOwnerPayouts($startDate, $endDate, $entreprise, $siteId);
            $campaignData = $this->fetchCampaignOccupancy($entreprise);

            return $this->response([
                'invoices' => array_map(fn($f) => [
                    'date' => $f->getDateEmission() ? $f->getDateEmission()->format('Y-m-d') : null,
                    'locataire' => ($f->getLocataire() ? $f->getLocataire()->getNom() . ' ' . $f->getLocataire()->getPrenoms() : 'N/A'),
                    'libelle' => $f->getLibFacture(),
                    'facture' => (float)$f->getMntFact(),
                    'paye' => (float)$f->getMntFact() - (float)$f->getSoldeFactLoc(),
                    'solde' => (float)$f->getSoldeFactLoc(),
                ], $invoiceData['items']),
                'payouts' => array_map(fn($p) => [
                    'date' => $p->getDateVersement() ? $p->getDateVersement()->format('Y-m-d') : null,
                    'proprio' => ($p->getProprio() ? $p->getProprio()->getNom() . ' ' . $p->getProprio()->getPrenoms() : 'N/A'),
                    'maison' => $p->getMaison() ? $p->getMaison()->getLibMaison() : null,
                    'libelle' => $p->getLibelle(),
                    'montant' => (float)$p->getMontant(),
                ], $payoutData),
                'campaigns' => $campaignData,
                'summary' => $invoiceData['summary']
            ]);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/proprio-maisons', methods: ['GET'])]
    #[OA\Get(
        path: "/api/etat/proprio-maisons",
        summary: "Liste des maisons par propriétaire",
        description: "Retourne la liste des propriétés groupées par propriétaire.",
        tags: ['Etats']
    )]
    public function getProprioMaisons(): Response
    {
        try {
            $entreprise = $this->getUser()->getEntreprise();
            $proprios = $this->em->getRepository(Proprio::class)->findBy(['entreprise' => $entreprise]);
            
            $result = [];
            foreach ($proprios as $p) {
                $maisons = $this->em->getRepository(Maison::class)->findBy(['proprio' => $p]);
                $result[] = [
                    'proprio' => [
                        'id' => $p->getId(),
                        'nom' => $p->getNom(),
                        'prenoms' => $p->getPrenoms(),
                    ],
                    'maisons_count' => count($maisons),
                    'maisons' => array_map(fn($m) => [
                        'id' => $m->getId(),
                        'nom' => $m->getLibMaison(),
                        'adresse' => $m->getLocalisation(),
                        'lot' => $m->getLot(),
                    ], $maisons)
                ];
            }

            return $this->response($result);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    #[Route('/download/{reportId}', methods: ['GET'])]
    public function downloadReport(string $reportId, Request $request): Response
    {
        try {
            $format = $request->query->get('format', 'pdf');
            $startDate = $request->query->get('startDate', date('Y-m-01'));
            $endDate = $request->query->get('endDate', date('Y-m-d'));
            $siteId = $request->query->get('siteId');
            if ($siteId === 'all') $siteId = null;
            $entreprise = $this->getUser()->getEntreprise();

            if ($format === 'pdf') {
                return $this->generatePdf($reportId, $startDate, $endDate, $entreprise, $siteId);
            } else {
                return $this->generateExcel($reportId, $startDate, $endDate, $entreprise, $siteId);
            }
        } catch (\Exception $e) {
            return new Response("Erreur lors de la génération: " . $e->getMessage(), 500);
        }
    }

    private function generatePdf($reportId, $startDate, $endDate, $entreprise, $siteId = null): Response
    {
        $html = "";
        $filename = "rapport_" . $reportId . "_" . date('Ymd') . ".pdf";
        $logo = $this->getLogoBase64($entreprise);

        switch ($reportId) {
            case 'fin_global':
            case 'fin_revenue':
            case 'audit_performance':
                $data = $this->fetchFinanceStats($startDate, $endDate, $entreprise);
                $html = $this->renderView('reports/finance.html.twig', [
                    'title' => 'État Financier Global',
                    'summary' => $data,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'fin_unpaid':
                $data = $this->fetchUnpaidInvoices($entreprise);
                $html = $this->renderView('reports/finance.html.twig', [
                    'title' => 'Rapport des Impayés',
                    'summary' => $data['summary'],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'rent_active':
                $data = $this->fetchActiveLocataires($entreprise);
                $html = $this->renderView('reports/maisons_proprio.html.twig', [
                    'title' => 'Liste des Locataires Actifs',
                    'data' => $data,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'pay_daily':
            case 'pay_monthly':
                $transactions = $this->fetchTransactions($startDate, $endDate, $entreprise);
                $total = array_reduce($transactions, fn($sum, $t) => $sum + (float)$t->getAmount(), 0);
                $html = $this->renderView('reports/paiements.html.twig', [
                    'transactions' => $transactions,
                    'total_amount' => $total,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'rent_proprio':
                $data = $this->fetchProprioMaisons($entreprise);
                $html = $this->renderView('reports/maisons_proprio.html.twig', [
                    'data' => $data,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'rent_by_house':
                $data = $this->fetchRentByHouse($entreprise);
                $html = $this->renderView('reports/maisons_proprio.html.twig', [
                    'title' => 'Liste des Locataires par Maison',
                    'data' => $data,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'invoice_status':
                $data = $this->fetchInvoiceStatus($startDate, $endDate, $entreprise);
                $html = $this->renderView('reports/invoices.html.twig', [
                    'title' => 'Rapport des Factures & Règlements',
                    'factures' => $data['items'],
                    'summary' => $data['summary'],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'invoices_by_house':
                $data = $this->fetchInvoicesByHouse($startDate, $endDate, $entreprise);
                $html = $this->renderView('reports/invoices_by_house.html.twig', [
                    'title' => 'Factures de Location par Maison',
                    'data' => $data,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'payments_by_house':
                $data = $this->fetchPaymentsByHouse($startDate, $endDate, $entreprise);
                $html = $this->renderView('reports/payments_by_house.html.twig', [
                    'title' => 'Paiements Encaissés par Maison',
                    'data' => $data,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'owner_payments':
                $data = $this->fetchOwnerPayouts($startDate, $endDate, $entreprise);
                $html = $this->renderView('reports/owner_payouts.html.twig', [
                    'title' => 'Journal des Versements Propriétaires',
                    'payments' => $data,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            case 'campaign_occupancy':
                $data = $this->fetchCampaignOccupancy($entreprise);
                $html = $this->renderView('reports/campaigns.html.twig', [
                    'title' => 'Répartition des Campagnes par Appartement',
                    'campaigns' => $data,
                    'entreprise' => $entreprise,
                    'logo' => $logo
                ]);
                break;
            default:
                // Fallback to a generic message if reportId is unknown
                $html = "<h1>Rapport non disponible</h1><p>Le type de rapport '$reportId' n'est pas encore implémenté.</p>";
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    private function generateExcel($reportId, $startDate, $endDate, $entreprise, $siteId = null): Response
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $filename = "rapport_" . $reportId . "_" . date('Ymd') . ".xlsx";

        switch ($reportId) {
            case 'fin_global':
            case 'fin_revenue':
            case 'audit_performance':
            case 'audit_growth':
                $data = $this->fetchFinanceStats($startDate, $endDate, $entreprise);
                $this->fillFinanceSheet($sheet, $data, $entreprise);
                break;

            case 'fin_unpaid':
                $data = $this->fetchUnpaidInvoices($entreprise);
                $sheet->setCellValue('A1', 'Rapport des Impayés - ' . ($entreprise ? $entreprise->getNom() : ''));
                $sheet->setCellValue('A3', 'Locataire');
                $sheet->setCellValue('B3', 'Facture');
                $sheet->setCellValue('C3', 'date');
                $sheet->setCellValue('D3', 'montant');
                $sheet->setCellValue('E3', 'solde');
                
                $row = 4;
                foreach ($data['items'] as $f) {
                    $sheet->setCellValue('A' . $row, $f->getLocataire()->getNom() . ' ' . $f->getLocataire()->getPrenoms());
                    $sheet->setCellValue('B' . $row, $f->getLibFacture());
                    $sheet->setCellValue('C' . $row, $f->getDateEmission()->format('d/m/Y'));
                    $sheet->setCellValue('D' . $row, $f->getMntFact());
                    $sheet->setCellValue('E' . $row, $f->getSoldeFactLoc());
                    $row++;
                }
                break;

            case 'pay_monthly':
            case 'pay_daily':
                $transactions = $this->fetchTransactions($startDate, $endDate, $entreprise);
                $sheet->setCellValue('A1', 'Journal des Paiements');
                $sheet->setCellValue('A3', 'date');
                $sheet->setCellValue('B3', 'Référence');
                $sheet->setCellValue('C3', 'Locataire');
                $sheet->setCellValue('D3', 'Mode');
                $sheet->setCellValue('E3', 'montant');
                
                $row = 4;
                foreach ($transactions as $t) {
                    $sheet->setCellValue('A' . $row, $t->getDate()->format('d/m/Y H:i'));
                    $sheet->setCellValue('B' . $row, $t->getReference());
                    $sheet->setCellValue('C' . $row, $t->getLocataire()->getNom() . ' ' . $t->getLocataire()->getPrenoms());
                    $sheet->setCellValue('D' . $row, $t->getMode());
                    $sheet->setCellValue('E' . $row, $t->getAmount());
                    $row++;
                }
                break;

            case 'rent_proprio':
                $data = $this->fetchProprioMaisons($entreprise);
                $sheet->setCellValue('A1', 'Maisons par Propriétaire');
                $sheet->setCellValue('A3', 'Propriétaire');
                $sheet->setCellValue('B3', 'Maison');
                $sheet->setCellValue('C3', 'Adresse');
                $sheet->setCellValue('D3', 'lot');
                
                $row = 4;
                foreach ($data as $item) {
                    foreach ($item['maisons'] as $m) {
                        $sheet->setCellValue('A' . $row, $item['proprio']['nom'] . ' ' . $item['proprio']['prenoms']);
                        $sheet->setCellValue('B' . $row, $m['nom']);
                        $sheet->setCellValue('C' . $row, $m['adresse']);
                        $sheet->setCellValue('D' . $row, $m['lot']);
                        $row++;
                    }
                }
                break;

            case 'invoice_status':
                $data = $this->fetchInvoiceStatus($startDate, $endDate, $entreprise);
                $sheet->setCellValue('A1', 'Rapport des Factures & Règlements');
                $sheet->setCellValue('A3', 'date');
                $sheet->setCellValue('B3', 'Locataire');
                $sheet->setCellValue('C3', 'Libellé');
                $sheet->setCellValue('D3', 'Facturé');
                $sheet->setCellValue('E3', 'Encaissé');
                $sheet->setCellValue('F3', 'solde');
                $row = 4;
                foreach ($data['items'] as $f) {
                    $sheet->setCellValue('A' . $row, $f->getDateEmission()->format('d/m/Y'));
                    $sheet->setCellValue('B' . $row, $f->getLocataire()?->getNom() . ' ' . $f->getLocataire()?->getPrenoms());
                    $sheet->setCellValue('C' . $row, $f->getLibFacture());
                    $sheet->setCellValue('D' . $row, $f->getMntFact());
                    $sheet->setCellValue('E' . $row, (float)$f->getMntFact() - (float)$f->getSoldeFactLoc());
                    $sheet->setCellValue('F' . $row, $f->getSoldeFactLoc());
                    $row++;
                }
                break;

            case 'owner_payments':
                $data = $this->fetchOwnerPayouts($startDate, $endDate, $entreprise);
                $sheet->setCellValue('A1', 'Versements Propriétaires');
                $sheet->setCellValue('A3', 'date');
                $sheet->setCellValue('B3', 'Propriétaire');
                $sheet->setCellValue('C3', 'Libellé');
                $sheet->setCellValue('D3', 'Maison');
                $sheet->setCellValue('E3', 'montant');
                $row = 4;
                foreach ($data as $p) {
                    $sheet->setCellValue('A' . $row, $p->getDateVersement()->format('d/m/Y'));
                    $sheet->setCellValue('B' . $row, $p->getProprio()?->getNom() . ' ' . $p->getProprio()?->getPrenoms());
                    $sheet->setCellValue('C' . $row, $p->getLibelle());
                    $sheet->setCellValue('D' . $row, $p->getMaison()?->getLibMaison());
                    $sheet->setCellValue('E' . $row, $p->getMontant());
                    $row++;
                }
                break;

            default:
                $sheet->setCellValue('A1', 'Rapport non supporté pour Excel: ' . $reportId);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    private function fetchFinanceStats($startDate, $endDate, $entreprise, $siteId = null)
    {
        $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
        if ($entreprise) {
            $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
        }
        if ($startDate && $endDate) {
            $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
               ->setParameter('start', new \DateTime($startDate))
               ->setParameter('end', new \DateTime($endDate . ' 23:59:59'));
        }
        if ($siteId) {
            $qb->join('f.contrat', 'c')->join('c.appart', 'a')->join('a.maisson', 'm')
               ->andWhere('m.id = :siteId')->setParameter('siteId', $siteId);
        }
        $factures = $qb->getQuery()->getResult();

        $totalRevenue = 0; $totalOutstanding = 0; $paidAmount = 0;
        foreach ($factures as $f) {
            $totalRevenue += (float)$f->getMntFact();
            $totalOutstanding += (float)$f->getSoldeFactLoc();
            $paidAmount += ((float)$f->getMntFact() - (float)$f->getSoldeFactLoc());
        }

        return [
            'total_billed' => $totalRevenue,
            'total_paid' => $paidAmount,
            'total_outstanding' => $totalOutstanding,
            'collection_rate' => $totalRevenue > 0 ? round(($paidAmount / $totalRevenue) * 100, 2) : 0
        ];
    }

    private function fetchTransactions($startDate, $endDate, $entreprise, $siteId = null)
    {
        $qb = $this->em->getRepository(Transaction::class)->createQueryBuilder('t');
        if ($entreprise) {
            $qb->join('t.locataire', 'l')
               ->andWhere('l.entreprise = :ent')
               ->setParameter('ent', $entreprise);
        }
        if ($startDate && $endDate) {
             $qb->andWhere('t.date >= :start AND t.date <= :end')
               ->setParameter('start', new \DateTime($startDate))
               ->setParameter('end', new \DateTime($endDate . ' 23:59:59'));
        }
        if ($siteId) {
             if (!in_array('f', $qb->getAllAliases())) {
                 $qb->leftJoin('t.factureLocation', 'f');
             }
             $qb->join('f.contrat', 'c')->join('c.appart', 'a')->join('a.maisson', 'm')
                ->andWhere('m.id = :siteId')->setParameter('siteId', $siteId);
        }
        return $qb->getQuery()->getResult();
    }

    private function fetchProprioMaisons($entreprise)
    {
        $proprios = $this->em->getRepository(Proprio::class)->findBy(['entreprise' => $entreprise]);
        $result = [];
        foreach ($proprios as $p) {
            $maisons = $this->em->getRepository(Maison::class)->findBy(['proprio' => $p]);
            $result[] = [
                'proprio' => ['nom' => $p->getNom(), 'prenoms' => $p->getPrenoms()],
                'maisons_count' => count($maisons),
                'maisons' => array_map(fn($m) => [
                    'nom' => $m->getLibMaison(),
                    'adresse' => $m->getLocalisation(),
                    'lot' => $m->getLot(),
                ], $maisons)
            ];
        }
        return $result;
    }

    private function fetchUnpaidInvoices($entreprise)
    {
        $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
        if ($entreprise) {
            $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
        }
        $qb->andWhere('f.soldeFactLoc > 0');
        $factures = $qb->getQuery()->getResult();

        $totalRevenue = 0; $totalOutstanding = 0; $paidAmount = 0;
        foreach ($factures as $f) {
            $totalRevenue += (float)$f->getMntFact();
            $totalOutstanding += (float)$f->getSoldeFactLoc();
            $paidAmount += ((float)$f->getMntFact() - (float)$f->getSoldeFactLoc());
        }

        return [
            'summary' => [
                'total_billed' => $totalRevenue,
                'total_paid' => $paidAmount,
                'total_outstanding' => $totalOutstanding,
                'collection_rate' => $totalRevenue > 0 ? round(($paidAmount / $totalRevenue) * 100, 2) : 0
            ],
            'items' => $factures
        ];
    }

    private function fetchActiveLocataires($entreprise)
    {
        $criteria = ['isActive' => true];
        if ($entreprise) {
            $criteria['entreprise'] = $entreprise;
        }
        $locataires = $this->em->getRepository(\App\Entity\Locataire::class)->findBy($criteria);
        
        // Structure similar to fetchProprioMaisons for reuse in templates
        return [[
            'proprio' => ['nom' => 'Locataires', 'prenoms' => 'Actifs'],
            'maisons_count' => count($locataires),
            'maisons' => array_map(fn($l) => [
                'nom' => $l->getNom() . ' ' . $l->getPrenoms(),
                'adresse' => $l->getContacts() . ' / ' . $l->getProfession(),
                'lot' => 'Matricule: ' . ($l->getId() + 1000),
            ], $locataires)
        ]];
    }

    private function fillFinanceSheet($sheet, $data, $entreprise)
    {
        $sheet->setCellValue('A1', 'État Financier - ' . ($entreprise ? $entreprise->getNom() : ''));
        $sheet->setCellValue('A3', 'Libellé');
        $sheet->setCellValue('B3', 'montant');
        $sheet->setCellValue('A4', 'Total Facturé');
        $sheet->setCellValue('B4', $data['total_billed']);
        $sheet->setCellValue('A5', 'Total Encaissé');
        $sheet->setCellValue('B5', $data['total_paid']);
        $sheet->setCellValue('A6', 'Reste à Recouvrer');
        $sheet->setCellValue('B6', $data['total_outstanding']);
        $sheet->setCellValue('A7', 'Taux de Collecte');
        $sheet->setCellValue('B7', $data['collection_rate'] . '%');
    }

    private function getLogoBase64($entreprise): ?string
    {
        if (!$entreprise || !$entreprise->getLogo()) return null;
        
        try {
            $path = $entreprise->getLogo()->getAbsolutePath();
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        } catch (\Exception $e) {}
        
        return null;
    }

    private function fetchInvoiceStatus($startDate, $endDate, $entreprise, $siteId = null)
    {
        $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f');
        if ($entreprise) {
            $qb->andWhere('f.entreprise = :ent')->setParameter('ent', $entreprise);
        }
        if ($startDate && $endDate) {
            $qb->andWhere('f.dateEmission >= :start AND f.dateEmission <= :end')
               ->setParameter('start', new \DateTime($startDate))
               ->setParameter('end', new \DateTime($endDate . ' 23:59:59'));
        }
        if ($siteId) {
            $qb->join('f.contrat', 'c')->join('c.appart', 'a')->join('a.maisson', 'm')
               ->andWhere('m.id = :siteId')->setParameter('siteId', $siteId);
        }
        $factures = $qb->getQuery()->getResult();

        $totalRevenue = 0; $totalOutstanding = 0; $paidAmount = 0;
        foreach ($factures as $f) {
            $totalRevenue += (float)$f->getMntFact();
            $totalOutstanding += (float)$f->getSoldeFactLoc();
            $paidAmount += ((float)$f->getMntFact() - (float)$f->getSoldeFactLoc());
        }

        return [
            'summary' => [
                'total_billed' => $totalRevenue,
                'total_paid' => $paidAmount,
                'total_outstanding' => $totalOutstanding,
                'collection_rate' => $totalRevenue > 0 ? round(($paidAmount / $totalRevenue) * 100, 2) : 0
            ],
            'items' => $factures
        ];
    }

    private function fetchOwnerPayouts($startDate, $endDate, $entreprise, $siteId = null)
    {
        $qb = $this->em->getRepository(VersmtProprio::class)->createQueryBuilder('v');
        if ($entreprise) {
            $qb->join('v.proprio', 'p')
               ->andWhere('p.entreprise = :ent')
               ->setParameter('ent', $entreprise);
        }
        if ($startDate && $endDate) {
            $qb->andWhere('v.dateVersement >= :start AND v.dateVersement <= :end')
               ->setParameter('start', new \DateTime($startDate))
               ->setParameter('end', new \DateTime($endDate . ' 23:59:59'));
        }
        if ($siteId) {
            $qb->join('v.maison', 'm')
               ->andWhere('m.id = :siteId')->setParameter('siteId', $siteId);
        }
        return $qb->getQuery()->getResult();
    }

    private function fetchCampaignOccupancy($entreprise)
    {
        $criteria = [];
        if ($entreprise) {
            $criteria['entreprise'] = $entreprise;
        }
        $campaigns = $this->em->getRepository(Campagne::class)->findBy($criteria);
        
        $result = [];
        foreach ($campaigns as $c) {
            $appartements = [];
            foreach ($c->getContratLocations() as $contrat) {
                if ($contrat->getAppart()) {
                    $appartements[] = [
                        'nom' => $contrat->getAppart()->getLibAppart(),
                        'maison' => $contrat->getAppart()->getMaisson()?->getLibMaison(),
                        'locataire' => $contrat->getLocataire()?->getNom() . ' ' . $contrat->getLocataire()?->getPrenoms(),
                        'loyer' => $contrat->getMntLoyer(),
                    ];
                }
            }
            $result[] = [
                'campagne' => $c->getLibCampagne(),
                'appartements' => $appartements,
                'total_amount' => $c->getMntTotal(),
                'total_paid' => $c->getMntPaye(),
            ];
        }
        return $result;
    }

    private function fetchRentByHouse($entreprise, $siteId = null)
    {
        $qb = $this->em->getRepository(\App\Entity\ContratLocation::class)->createQueryBuilder('c');
        if ($entreprise) {
            $qb->andWhere('c.entreprise = :ent')->setParameter('ent', $entreprise);
        }
        $qb->andWhere('c.etat = 1');
        if ($siteId) {
            $qb->join('c.appart', 'a')->join('a.maisson', 'm')
               ->andWhere('m.id = :siteId')->setParameter('siteId', $siteId);
        }
        $contrats = $qb->getQuery()->getResult();
        
        $houses = [];
        foreach ($contrats as $c) {
            $maisonLib = $c->getAppart() && $c->getAppart()->getMaisson() ? $c->getAppart()->getMaisson()->getLibMaison() : 'Sans Maison';
            if (!isset($houses[$maisonLib])) {
                $houses[$maisonLib] = [
                    'proprio' => ['nom' => 'Maison', 'prenoms' => $maisonLib],
                    'maisons' => []
                ];
            }
            $loc = $c->getLocataire();
            if ($loc) {
                $houses[$maisonLib]['maisons'][] = [
                    'nom' => $loc->getNom() . ' ' . $loc->getPrenoms(),
                    'adresse' => $c->getAppart() ? $c->getAppart()->getLibAppart() : '',
                    'lot' => 'Contact: ' . $loc->getContacts(),
                ];
            }
        }
        
        foreach ($houses as &$house) {
            $house['maisons_count'] = count($house['maisons']);
        }
        return array_values($houses);
    }

    private function fetchInvoicesByHouse($startDate, $endDate, $entreprise)
    {
        $factures = $this->fetchInvoiceStatus($startDate, $endDate, $entreprise)['items'];
        $houses = [];
        foreach ($factures as $f) {
            $maisonLib = 'Autre';
            if ($f->getContrat() && $f->getContrat()->getAppart() && $f->getContrat()->getAppart()->getMaisson()) {
                $maisonLib = $f->getContrat()->getAppart()->getMaisson()->getLibMaison();
            }
            if (!isset($houses[$maisonLib])) {
                $houses[$maisonLib] = [
                    'maison' => $maisonLib,
                    'total_billed' => 0,
                    'total_paid' => 0,
                    'total_outstanding' => 0,
                    'factures' => []
                ];
            }
            
            $billed = (float)$f->getMntFact();
            $outstanding = (float)$f->getSoldeFactLoc();
            $paid = $billed - $outstanding;
            
            $houses[$maisonLib]['total_billed'] += $billed;
            $houses[$maisonLib]['total_outstanding'] += $outstanding;
            $houses[$maisonLib]['total_paid'] += $paid;
            
            $houses[$maisonLib]['factures'][] = [
                'date' => $f->getDateEmission()->format('d/m/Y'),
                'locataire' => $f->getLocataire()?->getNom() . ' ' . $f->getLocataire()?->getPrenoms(),
                'libelle' => $f->getLibFacture(),
                'facture' => $billed,
                'paye' => $paid,
                'solde' => $outstanding
            ];
        }
        return array_values($houses);
    }

    private function fetchPaymentsByHouse($startDate, $endDate, $entreprise)
    {
        $transactions = $this->fetchTransactions($startDate, $endDate, $entreprise);
        $houses = [];
        foreach ($transactions as $t) {
            $maisonLib = 'Autre';
            if ($t->getFactureLocation() && $t->getFactureLocation()->getContrat() && $t->getFactureLocation()->getContrat()->getAppart() && $t->getFactureLocation()->getContrat()->getAppart()->getMaisson()) {
                $maisonLib = $t->getFactureLocation()->getContrat()->getAppart()->getMaisson()->getLibMaison();
            }
            if (!isset($houses[$maisonLib])) {
                $houses[$maisonLib] = [
                    'maison' => $maisonLib,
                    'total_amount' => 0,
                    'transactions' => []
                ];
            }
            
            $amount = (float)$t->getAmount();
            $houses[$maisonLib]['total_amount'] += $amount;
            $houses[$maisonLib]['transactions'][] = [
                'date' => $t->getDate()->format('d/m/Y H:i'),
                'reference' => $t->getReference(),
                'locataire' => $t->getLocataire()?->getNom() . ' ' . $t->getLocataire()?->getPrenoms(),
                'mode' => $t->getMode(),
                'montant' => $amount
            ];
        }
        return array_values($houses);
    }
}
