<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\StockDeficit;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service central de gestion des notifications :
 * - Enregistre en base
 * - Envoie la notification push
 */
class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private PushNotificationService $pushService,
        private SendMailService $mailService,
        private LoggerInterface $logger
    ) {}

    /**
     * Crée et envoie une notification à un utilisateur.
     */
    public function notify(int $userId, Entreprise $entreprise, string $title, string $message, array $data = []): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->warning("❌ Notification ignorée : utilisateur #$userId introuvable.");
            return;
        }

        // Enrichir la data pour le mobile
        $data = array_merge($data, [
            'notification_id' => uniqid('notif_'),
            'user_id' => $user->getId(),
            'entreprise_id' => $entreprise->getId(),
            'timestamp' => time(),
        ]);

        // Enregistrer en base
        $notification = (new Notification())
            ->setUser($user)
            ->setTitre($title)
            ->setEntreprise($entreprise)
            ->setLibelle($message)
            ->setUpdatedBy($user)
            ->setCreatedBy($user)
            ->setEtat(false);

        $this->em->persist($notification);
        $this->em->flush();

        // Envoyer push si un token FCM existe
        $token = $user->getFcmToken();
        if (!$token) {
            $this->logger->info("ℹ️ Aucun token FCM pour l'utilisateur #{$user->getId()}");
            return;
        }

        try {
            $this->pushService->sendPush($token, $title, $message, $data);
            $this->logger->info("✅ Notification push envoyée à l'utilisateur #{$user->getId()}");
        } catch (\Throwable $e) {
            $this->logger->error("⚠️ Erreur FCM pour l'utilisateur #{$user->getId()}: " . $e->getMessage());
        }
    }

    /**
     * Envoie une notification push spécialisée pour les alertes de stock insuffisant
     * 
     * @param User $admin L'administrateur à notifier
     * @param Entreprise $entreprise L'entreprise concernée
     * @param string $boutiqueName Nom de la boutique concernée
     * @param array $stockDeficits Tableau d'objets StockDeficit
     * @param array $reservationInfo Informations sur la réservation (client, montant, etc.)
     */
    public function sendStockAlertNotification(
        User $admin,
        Entreprise $entreprise,
        string $boutiqueName,
        array $stockDeficits,
        array $reservationInfo
    ): void {
        try {
            // Calculer le nombre d'articles en rupture
            $itemCount = count($stockDeficits);
            
            // Créer le titre de la notification
            $title = "🚨 Alerte Stock - {$boutiqueName}";
            
            // Créer le message concis pour la notification push
            $message = $this->buildStockAlertMessage($itemCount, $reservationInfo['client_name'] ?? 'Client');
            
            // Préparer les données enrichies pour la notification
            $data = [
                'type' => 'stock_alert',
                'boutique_name' => $boutiqueName,
                'reservation_id' => $reservationInfo['reservation_id'] ?? null,
                'client_name' => $reservationInfo['client_name'] ?? '',
                'total_amount' => $reservationInfo['total_amount'] ?? 0,
                'withdrawal_date' => $reservationInfo['withdrawal_date'] ?? '',
                'items_count' => $itemCount,
                'deficits' => array_map(fn(StockDeficit $deficit) => $deficit->toArray(), $stockDeficits),
                'priority' => 'high',
                'action_required' => true
            ];
            
            // Enregistrer en base avec priorité élevée
            $notification = (new Notification())
                ->setUser($admin)
                ->setTitre($title)
                ->setEntreprise($entreprise)
                ->setLibelle($message)
                ->setUpdatedBy($admin)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setCreatedBy($admin)
                ->setEtat(false); // Non lu initialement
            
            $this->em->persist($notification);
            $this->em->flush();
            
            // Envoyer la notification push avec priorité élevée
            $token = $admin->getFcmToken();
            if (!$token) {
                $this->logger->warning("⚠️ Aucun token FCM pour l'admin #{$admin->getId()} - Alerte stock non envoyée en push");
                return;
            }
            
            try {
                $this->pushService->sendPush($token, $title, $message, $data);
                $this->logger->info("✅ Alerte stock envoyée à l'admin #{$admin->getId()} pour la boutique {$boutiqueName}");
            } catch (\Throwable $e) {
                $this->logger->error("❌ Erreur envoi alerte stock FCM pour admin #{$admin->getId()}: " . $e->getMessage());
                // Ne pas lever l'exception pour ne pas bloquer le processus de réservation
            }
            
        } catch (\Throwable $e) {
            $this->logger->error("❌ Erreur critique lors de l'envoi d'alerte stock: " . $e->getMessage());
            // Ne pas lever l'exception pour ne pas bloquer le processus de réservation
        }
    }

    /**
     * Notifie tous les administrateurs d'une entreprise.
     */
    public function notifyAdmins(Entreprise $entreprise, string $title, string $message, array $data = []): void
    {
        $admins = $this->userRepository->createQueryBuilder('u')
            ->join('u.groupe', 'g')
            ->where('u.entreprise = :entreprise')
            ->andWhere('g.code = :role OR g.name LIKE :admin')
            ->setParameter('entreprise', $entreprise)
            ->setParameter('role', 'ADM')
            ->setParameter('admin', '%ADMIN%')
            ->getQuery()
            ->getResult();

        foreach ($admins as $admin) {
            $this->notify($admin->getId(), $entreprise, $title, $message, $data);
            
            // Envoyer un email si l'option est présente dans $data
            if (isset($data['send_email']) && $data['send_email']) {
                try {
                    $this->mailService->send(
                        'no-reply@immoplus.com',
                        $admin->getLogin(),
                        $title,
                        $data['email_template'] ?? 'payment_collected_agent',
                        array_merge($data, ['admin_name' => $admin->getNomPrenoms()])
                    );
                } catch (\Exception $e) {
                    $this->logger->error("❌ Erreur envoi email à l'admin #{$admin->getId()}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Construit le message concis pour la notification push d'alerte de stock
     */
    private function buildStockAlertMessage(int $itemCount, string $clientName): string
    {
        if ($itemCount === 1) {
            return "Réservation de {$clientName} : 1 article en rupture de stock. Action requise.";
        } else {
            return "Réservation de {$clientName} : {$itemCount} articles en rupture de stock. Action requise.";
        }
    }
}
