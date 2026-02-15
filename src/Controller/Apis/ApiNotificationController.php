<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Repository\EntrepriseRepository;
use App\Repository\GroupeRepository;
use App\Repository\NotificationRepository;
use App\Service\SendMailService;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/notification')]
#[OA\Tag(name: 'notification', description: 'Gestion des notifications push Firebase')]
class ApiNotificationController extends ApiInterface
{


    /**
     * Liste toutes les catégories de mesure du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification",
        summary: "Lister toutes les notifications d'un user",
        description: "Lister toutes les notifications d'un user",
        tags: ['notification']
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function getUserNotifications(NotificationRepository $notificationRepository)
    {

        try {
            $notifications = $notificationRepository->findBy(
                ['user' => $this->getUser()],
                ['id' => 'DESC']
            );

            $response = $this->responseData($notifications, 'group1', ['Content-Type' => 'application/json'], false);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des catégories de mesure");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Met à jour le token FCM d'un utilisateur
     */
    #[Route('/fcm-token', methods: ['POST'])]
    #[OA\Post(
        path: "/api/notification/fcm-token",
        summary: "Mettre à jour le token FCM",
        description: "Met à jour le token Firebase Cloud Messaging de l'utilisateur connecté pour recevoir les notifications push.",
        tags: ['notification']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["fcm_token"],
            properties: [
                new OA\Property(
                    property: "fcm_token",
                    type: "string",
                    example: "dGhpc19pc19hX3Rva2Vu...",
                    description: "Token FCM généré par l'application mobile"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Token FCM mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "message", type: "string", example: "Token FCM mis à jour avec succès")
            ]
        )
    )]
    public function updateFcmToken(Request $request, UserRepository $userRepository, SendMailService $sendMailService): Response
    {
        $data = json_decode($request->getContent(), true);
        $fcmToken = $data['fcm_token'] ?? null;

        if (!$fcmToken) {
            $this->setMessage("Token FCM requis");
            return $this->response('[]', 400);
        }

        $user = $this->getUser();
        $user->setFcmToken($fcmToken);
        $userRepository->add($user, true);

        // S'abonner au topic de l'entreprise
        if ($user->getEntreprise()) {
            $topic = 'entreprise_' . $user->getEntreprise()->getId();
            $sendMailService->subscribeToTopic($fcmToken, $topic);
        }

        $this->setMessage("Token FCM mis à jour avec succès");
        return $this->response([]);
    }

    /**
     * Envoie une notification de test
     */
    #[Route('/test', methods: ['POST'])]
    #[OA\Post(
        path: "/api/notification/test",
        summary: "Envoyer une notification de test",
        description: "Envoie une notification push de test à l'utilisateur connecté.",
        tags: ['notification']
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "titre", type: "string", example: "Test notification"),
                new OA\Property(property: "message", type: "string", example: "Ceci est un test")
            ]
        )
    )]
    public function sendTestNotification(Request $request, SendMailService $sendMailService): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
/* 
        if (!$user->getFcmToken()) {
            $this->setMessage("Aucun token FCM enregistré pour cet utilisateur");
            return $this->response('[]', 400);
        } */

        $notificationData = [
            'titre' => $data['titre'] ?? 'Test notification',
            'libelle' => $data['message'] ?? 'Ceci est une notification de test',
            'type' => 'test',
            'user' => $user,
            'entreprise' => $user->getEntreprise(),
            'fcm_token' => $user->getFcmToken()
        ];

        $sendMailService->sendNotification($notificationData);

        $this->setMessage("Notification de test envoyée");
        return $this->response([]);
    }

    /**
     * Marque une notification comme lue
     */
    #[Route('/{id}/read', methods: ['POST'])]
    #[OA\Put(
        path: "/api/notification/{id}/mark-as-read",
        summary: "Marquer une notification comme lue",
        description: "Marque une notification spécifique comme lue pour l'utilisateur connecté.",
        tags: ['notification']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID de la notification",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Notification marquée comme lue",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "message", type: "string", example: "Notification marquée comme lue")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Notification non trouvée")]
    #[OA\Response(response: 403, description: "Accès non autorisé")]
    public function markAsRead(int $id, NotificationRepository $notificationRepository): Response
    {
        try {
            $notification = $notificationRepository->find($id);

            if (!$notification) {
                $this->setMessage("Notification non trouvée");
                return $this->response('[]', 404);
            }

            // Vérifier que la notification appartient à l'utilisateur connecté
            if ($notification->getUser() !== $this->getUser()) {
                $this->setMessage("Vous n'êtes pas autorisé à modifier cette notification");
                return $this->response('[]', 403);
            }

            // Marquer comme lue (etat = false signifie "lue")
            $notification->setEtat(false);
            $notificationRepository->add($notification, true);

            $this->setMessage("Notification marquée comme lue");
            return $this->response([]);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la notification");
            return $this->response([]);
        }
    }

    /**
     * Marque toutes les notifications comme lues
     */
    #[Route('/read-all', methods: ['POST'])]
    #[OA\Post(
        path: "/api/notification/read-all",
        summary: "Marquer toutes les notifications comme lues",
        description: "Marque toutes les notifications non lues de l'utilisateur connecté comme lues.",
        tags: ['notification']
    )]
    public function markAllAsRead(NotificationRepository $notificationRepository): Response
    {
        try {
            $unreadNotifications = $notificationRepository->findBy([
                'user' => $this->getUser(),
                'etat' => true
            ]);

            foreach ($unreadNotifications as $index => $notification) {
                $notification->setEtat(false);
                // Flush on the last element
                $isLast = ($index === count($unreadNotifications) - 1);
                $notificationRepository->add($notification, $isLast);
            }

            $this->setMessage("Toutes les notifications ont été marquées comme lues");
            return $this->response([]);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour des notifications");
            return $this->response([]);
        }
    }

    /**
     * Retourne le nombre de notifications non lues
     */
    #[Route('/count-unread', methods: ['GET'])]
    #[OA\Get(
        path: "/api/notification/count-unread",
        summary: "Compter les notifications non lues",
        description: "Retourne le nombre total de notifications non lues pour l'utilisateur connecté.",
        tags: ['notification']
    )]
    #[OA\Response(
        response: 200,
        description: "Nombre de notifications non lues récupéré avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "count", type: "integer", example: 5)
            ]
        )
    )]
    public function countUnread(NotificationRepository $notificationRepository): Response
    {
        try {
            $count = $notificationRepository->count([
                'user' => $this->getUser(),
                'etat' => true // true = active/non lue
            ]);

            return $this->responseData(['count' => $count], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors du comptage des notifications");
            return $this->response([]);
        }
    }

    /**
     * Envoie une notification à un groupe d'utilisateurs (Broadcast)
     */
    #[Route('/broadcast', methods: ['POST'])]
    #[OA\Post(
        path: "/api/notification/broadcast",
        summary: "Envoyer une notification de masse",
        description: "Envoie une notification à tous les utilisateurs ou seulement aux administrateurs.",
        tags: ['notification']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["titre", "message", "target"],
            properties: [
                new OA\Property(property: "titre", type: "string", example: "Maintenance prévue"),
                new OA\Property(property: "message", type: "string", example: "L'application sera en maintenance ce soir."),
                new OA\Property(property: "target", type: "string", enum: ["all", "admins"], example: "all"),
                new OA\Property(property: "entreprise_id", type: "integer", description: "ID de l'entreprise (optionnel, sinon entreprise de l'admin connecté)", nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Notifications envoyées",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "count", type: "integer", example: 15),
                new OA\Property(property: "message", type: "string", example: "Notification envoyée à 15 utilisateurs")
            ]
        )
    )]
    public function broadcast(
        Request $request,
        UserRepository $userRepository,
        GroupeRepository $typeUserRepository,
        EntrepriseRepository $entrepriseRepository,
        SendMailService $sendMailService
    ): Response {
        // Sécurité : Seuls les admins ou super admins devraient pouvoir faire ça
        // Ici on suppose que l'utilisateur connecté a le droit, ou on ajoute une vérification de rôle
        // if (!$this->isGranted('ROLE_ADMIN')) { ... }

        $data = json_decode($request->getContent(), true);
        $titre = $data['titre'] ?? null;
        $message = $data['message'] ?? null;
        $target = $data['target'] ?? 'all';
        $entrepriseId = $data['entreprise_id'] ?? null;

        if (!$titre || !$message) {
            $this->setMessage("Titre et message requis");
            return $this->response([], 400);
        }

        $criteria = ['isActive' => true];
        
        // Filtrer par entreprise si demandé ou si l'utilisateur connecté est restreint à son entreprise
        if ($entrepriseId) {
            $entreprise = $entrepriseRepository->find($entrepriseId);
            if ($entreprise) {
                $criteria['entreprise'] = $entreprise;
            }
        } elseif ($this->getUser()->getEntreprise()) {
             // Par défaut, restreindre à l'entreprise de l'utilisateur connecté (sauf si Super Admin global, à ajuster selon besoins)
            $criteria['entreprise'] = $this->getUser()->getEntreprise();
        }

        // Si cible = admins, on filtre par le type SADM
        if ($target === 'admins') {
            $typeSadm = $typeUserRepository->findOneBy(['code' => 'SADM']);
            if ($typeSadm) {
                $criteria['type'] = $typeSadm;
            }
        }

        $users = $userRepository->findBy($criteria);
        $count = 0;

        foreach ($users as $user) {
            $notificationData = [
                'titre' => $titre,
                'libelle' => $message,
                'type' => 'broadcast',
                'user' => $user,
                'entreprise' => $user->getEntreprise(),
                'fcm_token' => $user->getFcmToken()
            ];

            try {
                $sendMailService->sendNotification($notificationData);
                $count++;
            } catch (\Exception $e) {
                // On continue même si une erreur survient pour un user
                error_log("Erreur envoi broadcast user " . $user->getId() . ": " . $e->getMessage());
            }
        }

        $this->setMessage("Notification envoyée à {$count} utilisateurs");
        return $this->response(['count' => $count]);
    }

    /**
     * Supprime une notification
     */
    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/notification/{id}",
        summary: "Supprimer une notification",
        description: "Supprime une notification spécifique de l'utilisateur connecté.",
        tags: ['notification']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID de la notification",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Notification supprimée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "status", type: "string", example: "SUCCESS"),
                new OA\Property(property: "message", type: "string", example: "Notification supprimée avec succès")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Notification non trouvée")]
    #[OA\Response(response: 403, description: "Accès non autorisé")]
    public function deleteNotification(int $id, NotificationRepository $notificationRepository): Response
    {
        try {
            $notification = $notificationRepository->find($id);

            if (!$notification) {
                $this->setMessage("Notification non trouvée");
                return $this->response('[]', 404);
            }

            // Vérifier que la notification appartient à l'utilisateur connecté
            if ($notification->getUser() !== $this->getUser()) {
                $this->setMessage("Vous n'êtes pas autorisé à supprimer cette notification");
                return $this->response('[]', 403);
            }

            $notificationRepository->remove($notification, true);

            $this->setMessage("Notification supprimée avec succès");
            return $this->response([]);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la notification");
            return $this->response([]);
        }
    }
}
