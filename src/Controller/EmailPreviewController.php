<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/email-preview')]
class EmailPreviewController extends AbstractController
{
    #[Route('/', name: 'app_email_preview_list', methods: ['GET'])]
    public function index(): Response
    {
        $templates = [
            ['id' => 'otp', 'title' => 'Code de vérification (OTP)', 'icon' => '🔐'],
            ['id' => 'lead_contact', 'title' => 'Demande de Contact (Lead)', 'icon' => '🚀'],
            ['id' => 'password_reset', 'title' => 'Réinitialisation Password', 'icon' => '🔑'],
            ['id' => 'abonnement', 'title' => 'Confirmation Abonnement', 'icon' => '💳'],
            ['id' => 'bienvenue', 'title' => 'Bienvenue (Nouveau)', 'icon' => '👋'],
            ['id' => 'content_mail', 'title' => 'Création de Compte', 'icon' => '📧'],
            ['id' => 'content_validation', 'title' => 'Validation de Dossier', 'icon' => '✅'],
            ['id' => 'nouvellesinscription', 'title' => 'Alerte Nouvelle Inscription', 'icon' => '📝'],
            ['id' => 'paiement_email', 'title' => 'Reçu de Paiement', 'icon' => '💰'],
            ['id' => 'payment_collected_agent', 'title' => 'Encaissement Agent', 'icon' => '🤑'],
            ['id' => 'renew_mail', 'title' => 'Renouvellement Abonnement', 'icon' => '🔄'],
            ['id' => 'stock_alert_email', 'title' => 'Alerte Stock Critique', 'icon' => '🚨'],
            ['id' => 'vente_email', 'title' => 'Confirmation de Vente', 'icon' => '🛍️'],
            ['id' => 'welcome_user' , 'title' => 'Bienvenue Utilisateur', 'icon' => '🤝'],
        ];

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Dashboard | Immoplus</title>
            <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap' rel='stylesheet'>
            <style>
                body { font-family: 'Inter', sans-serif; background: #f8fafc; margin: 0; padding: 50px; color: #1e293b; }
                .container { max-width: 1200px; margin: 0 auto; }
                .header { margin-bottom: 50px; text-align: center; }
                .header h1 { font-size: 42px; font-weight: 800; color: #7C3AED; margin: 0; letter-spacing: -1px; }
                .header p { color: #64748b; font-size: 18px; margin-top: 10px; }
                .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
                .card { background: white; border-radius: 20px; padding: 30px; border: 1px solid #e2e8f0; transition: all 0.3s ease; text-decoration: none; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                .card:hover { transform: translateY(-10px); border-color: #7C3AED; box-shadow: 0 20px 25px -5px rgba(124, 58, 237, 0.1); }
                .icon { font-size: 40px; margin-bottom: 20px; background: #f1f5f9; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 60px; transition: background 0.3s; }
                .card:hover .icon { background: #7C3AED10; }
                .title { font-weight: 700; font-size: 16px; color: #0f172a; margin-bottom: 8px; }
                .subtitle { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Immoplus Email Studio</h1>
                    <p>Interface de test et de prévisualisation des communications</p>
                </div>
                <div class='grid'>";
        
        foreach ($templates as $t) {
            $url = "/api/email-preview/{$t['id']}";
            $html .= "
                <a href='$url' class='card' target='_blank'>
                    <div class='icon'>{$t['icon']}</div>
                    <div class='title'>{$t['title']}</div>
                    <div class='subtitle'>Template: {$t['id']}</div>
                </a>";
        }
        
        $html .= "</div></div></body></html>";

        return new Response($html);
    }

    #[Route('/{template}', name: 'app_email_preview', methods: ['GET'])]
    public function preview(string $template): Response
    {
        $data = $this->getDummyData($template);
        
        return $this->render("emails/$template.html.twig", $data);
    }

    private function getDummyData(string $template): array
    {
        return match ($template) {
            'otp' => [
                'otp_code' => '123456'
            ],
            'lead_contact' => [
                'planName' => 'PRO (ANNUEL)',
                'name' => 'Jean Dupont',
                'company' => 'Agence Immobilière Phoenix',
                'email_content' => 'j.dupont@agence.com',
                'phone' => '+225 07 45 89 22 10',
                'message' => "Bonjour, je souhaiterais passer au plan annuel pour mes 3 agences."
            ],
            'password_reset' => [
                'user' => (object)['email' => 'utilisateur@motiplus.pro'],
                'url' => 'https://web.motiplus.pro/reset-password/confirm?token=xyz123'
            ],
            'abonnement' => [
                'abonnement' => (object)[
                    'numAbonnement' => 'ABO-2026-001',
                    'dateFin' => new \DateTime('+1 year'),
                    'etat' => true,
                    'moduleAbonnement' => (object)[
                        'code' => 'PRO',
                        'maxAgences' => 5,
                        'maxEmployes' => 20,
                        'maxBiens' => 100,
                        'maxResidences' => 10
                    ]
                ]
            ],
            'content_mail' => [
                'info_user' => (object)[
                    'login' => 'admin_agence_01'
                ]
            ],
            'paiement_email' => [
                'name' => 'M. Coulibaly',
                'date' => new \DateTime(),
                'planName' => 'BASIQUE (MENSUEL)',
                'amount' => 95000,
                'transactionId' => 'TX_9876543210'
            ],
            'welcome_user' => [
                'user' => (object)[
                    'nom' => 'KOFFI',
                    'prenoms' => 'Ange',
                    'login' => 'a.koffi'
                ]
            ],
            'stock_alert_email' => [
                'boutique_name' => 'Showroom Abidjan',
                'priority_level' => 'CRITIQUE',
                'total_items_in_shortage' => 3,
                'client_name' => 'Mme Sara Bamba',
                'client_phone' => '01 02 03 04 05',
                'entreprise_name' => 'ImmoPlus S.A',
                'total_amount' => 1500000,
                'advance_amount' => 500000,
                'remaining_amount' => 1000000,
                'reservation_id' => 'RES-2026-99',
                'stock_deficits' => [
                    (object)['modele_name' => 'Climatiseur 2CV', 'quantity_requested' => 5, 'quantity_available' => 2, 'deficit' => 3, 'deficit_percentage' => 60],
                    (object)['modele_name' => 'Cuisinière Gaz 4 Feux', 'quantity_requested' => 2, 'quantity_available' => 1, 'deficit' => 1, 'deficit_percentage' => 50]
                ]
            ],
            'payment_collected_agent' => [
                'admin_name' => 'Admin Motiplus',
                'amount' => 250000,
                'agent_name' => 'Marc Konan',
                'locataire_name' => 'Ibrahim Touré',
                'facture_libelle' => 'Loyer Mars 2026',
                'mode' => 'Mobile Money',
                'date' => new \DateTime()
            ],
            'renew_mail' => [
                'expirationDate' => new \DateTime('+1 year'),
                'planName' => 'ENTERPRISE'
            ],
            'content_validation' => [
                'info_user' => (object)[
                    'etape' => 'validation',
                    'nom' => 'AMANI Paul',
                    'profession' => 'Professionnel de Santé',
                    'annee' => '2026'
                ]
            ],
            'nouvellesinscription' => [
                'entreprise' => (object)[
                    'libelle' => 'Global Immo Services',
                    'sigle' => 'GIS',
                    'contacts' => '+225 27 22 45 45 45',
                    'email' => 'contact@gis.ci'
                ],
                'admin' => (object)[
                    'nom' => 'Yao',
                    'prenoms' => 'Thierry'
                ]
            ],
            'vente_email' => [
                'name' => 'Client Passage',
                'date' => new \DateTime(),
                'amount' => 45000,
                'transactionId' => 'VNT_123',
                'items' => [
                    (object)['name' => 'Ampoule LED', 'quantity' => 10, 'total' => 15000],
                    (object)['name' => 'Prise murale', 'quantity' => 5, 'total' => 30000]
                ]
            ],
            default => []
        };
    }
}
