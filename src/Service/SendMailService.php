<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Entreprise;
use App\Service\StockDeficit;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SendMailService
{
    private $mailer;
    private $tokenStorage;
    private $firebase;

    public function __construct(
        MailerInterface $mailer,
        private EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ) {
        $this->mailer = $mailer;
        $this->tokenStorage = $tokenStorage;
        
        // Initialiser Firebase avec gestion d'erreur
        try {
            $credentialsPath = __DIR__ . '/../../config/firebase_credentials.json';
            if (file_exists($credentialsPath) && filesize($credentialsPath) > 0) {
                $this->firebase = (new Factory)
                    ->withServiceAccount($credentialsPath)
                    ->createMessaging();
            } else {
                error_log('Fichier Firebase credentials manquant ou vide');
                $this->firebase = null;
            }
        } catch (\Exception $e) {
            error_log('Erreur initialisation Firebase: ' . $e->getMessage());
            $this->firebase = null;
        }
    }

    /**
     * Récupère l'utilisateur connecté
     */
    public function getCurrentUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    public function send(
        string $from,
        string $to,
        string $subject,
        string $template,
        array $context
    ): void {
        //On crée le mail
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate("emails/$template.html.twig")
            ->context($context);

        // On envoie le mail
        $this->mailer->send($email);
    }

    public function sendCustom(
        string $from,
        string $to,
        string $subject,
        string $content,
        ?string $cc = null
    ): void {
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($content);

        if ($cc) {
            $email->cc($cc);
        }

        $this->mailer->send($email);
    }

    public function sendNotification($data = [])
    {
        $currentUser = $this->getCurrentUser();

        // Créer la notification en base
        $notification = new Notification();
        $notification->setLibelle($data['libelle']);
        $notification->setTitre($data['titre']);
        $notification->setIsActive(true);
        $notification->setEntreprise($data['entreprise']);
        $notification->setUser($data['user']);
        $notification->setUpdatedBy($currentUser);
        $notification->setCreatedBy($currentUser);
        $notification->setUpdatedAt();
        $notification->setCreatedAtValue();

        $this->em->persist($notification);
        $this->em->flush();

        // Envoyer la notification push Firebase
        $this->sendPushNotification($data, $data['user']); // TO DO pour activer l'envoie des notifications push
    }

    /**
     * Envoie une notification push Firebase
     */
    public function sendPushNotification($data = [], User $user)
    {
        if ($this->firebase === null) {
            error_log('Firebase non initialisé, impossible d\'envoyer la notification push');
            return;
        }

        try {
            $firebaseNotification = FirebaseNotification::create(
                $data['titre'] ?? 'Nouvelle notification',
                $data['libelle'] ?? ''
            );

            // Si un token FCM spécifique est fourni
            if ($user->getFcmToken() !== null && !empty($user->getFcmToken())) {
                $message = CloudMessage::withTarget('token', $user->getFcmToken())
                    ->withNotification($firebaseNotification)
                    ->withData([
                        'type' => $data['type'] ?? 'general',
                        'user_id' => (string)($data['user']?->getId() ?? ''),
                        'entreprise_id' => (string)($data['entreprise']?->getId() ?? ''),
                        'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
                    ]);

                $this->firebase->send($message);
            } else {
                // Log only, do not broadcast to topic as fallback
                error_log(sprintf('User %s (ID: %d) has no FCM token. Push notification skipped.', $user->getLogin(), $user->getId()));
            }

        } catch (\Exception $e) {
            error_log('Erreur envoi notification push: ' . $e->getMessage());
        }
    }

    /**
     * S'abonner un utilisateur à un topic Firebase
     */
    public function subscribeToTopic(string $fcmToken, string $topic): bool
    {
        if ($this->firebase === null) {
            return false;
        }

        try {
            $this->firebase->subscribeToTopic($topic, [$fcmToken]);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur abonnement topic: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Désabonner un utilisateur d'un topic Firebase
     */
    public function unsubscribeFromTopic(string $fcmToken, string $topic): bool
    {
        if ($this->firebase === null) {
            return false;
        }

        try {
            $this->firebase->unsubscribeFromTopic($topic, [$fcmToken]);
            return true;
        } catch (\Exception $e) {
            error_log('Erreur désabonnement topic: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un email d'alerte de stock insuffisant à l'administrateur
     * 
     * @param string $fromEmail Email expéditeur
     * @param User $admin Administrateur destinataire
     * @param Entreprise $entreprise Entreprise concernée
     * @param string $boutiqueName Nom de la boutique
     * @param array $stockDeficits Tableau d'objets StockDeficit
     * @param array $reservationInfo Informations sur la réservation
     */
    public function sendStockAlertEmail(
        string $fromEmail,
        User $admin,
        Entreprise $entreprise,
        string $boutiqueName,
        array $stockDeficits,
        array $reservationInfo
    ): void {
        try {
            // Préparer le contexte pour le template email
            $context = [
                'admin_name' => $admin->getNom() && $admin->getPrenoms() 
                    ? $admin->getNom() . ' ' . $admin->getPrenoms() 
                    : $admin->getLogin(),
                'entreprise_name' => $entreprise->getLibelle(),
                'boutique_name' => $boutiqueName,
                'client_name' => $reservationInfo['client_name'] ?? 'Client',
                'client_phone' => $reservationInfo['client_phone'] ?? '',
                'reservation_id' => $reservationInfo['reservation_id'] ?? null,
                'total_amount' => $reservationInfo['total_amount'] ?? 0,
                'advance_amount' => $reservationInfo['advance_amount'] ?? 0,
                'remaining_amount' => $reservationInfo['remaining_amount'] ?? 0,
                'withdrawal_date' => $reservationInfo['withdrawal_date'] ?? '',
                'created_by' => $reservationInfo['created_by'] ?? '',
                'created_at' => $reservationInfo['created_at'] ?? date('d/m/Y H:i'),
                'stock_deficits' => array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits),
                'total_items_in_shortage' => count($stockDeficits),
                'total_deficit_amount' => $this->calculateTotalDeficitAmount($stockDeficits),
                'priority_level' => $this->determinePriorityLevel($stockDeficits)
            ];

            // Créer le sujet de l'email
            $itemCount = count($stockDeficits);
            $subject = "🚨 Alerte Stock Urgent - {$boutiqueName} ({$itemCount} article" . ($itemCount > 1 ? 's' : '') . " en rupture)";

            // Envoyer l'email avec le template spécialisé
            $this->send(
                $fromEmail,
                $admin->getLogin(), // Utiliser getLogin() au lieu de getEmail()
                $subject,
                'stock_alert_email', // Template à créer
                $context
            );

            error_log("✅ Email d'alerte stock envoyé à {$admin->getLogin()} pour la boutique {$boutiqueName}");

        } catch (\Exception $e) {
            error_log("❌ Erreur envoi email alerte stock: " . $e->getMessage());
            // Ne pas lever l'exception pour ne pas bloquer le processus de réservation
        }
    }

    /**
     * Calcule le montant total des déficits (estimation)
     */
    private function calculateTotalDeficitAmount(array $stockDeficits): int
    {
        // Pour l'instant, on retourne 0 car nous n'avons pas les prix unitaires
        // Cette méthode peut être étendue si les prix sont disponibles
        return 0;
    }

    /**
     * Détermine le niveau de priorité basé sur les déficits
     */
    private function determinePriorityLevel(array $stockDeficits): string
    {
        $itemCount = count($stockDeficits);
        $totalDeficit = array_sum(array_map(fn(StockDeficit $deficit) => $deficit->getDeficit(), $stockDeficits));

        if ($itemCount >= 5 || $totalDeficit >= 50) {
            return 'CRITIQUE';
        } elseif ($itemCount >= 3 || $totalDeficit >= 20) {
            return 'ÉLEVÉE';
        } else {
            return 'NORMALE';
        }
    }
}
