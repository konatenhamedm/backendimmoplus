<?php

namespace App\Controller;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Setting;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Service\JwtService;
use App\Service\SubscriptionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OpenApi\Attributes as OA;
use App\Entity\ConnectionLog;
use App\Repository\ConnectionLogRepository;
use Doctrine\ORM\EntityManagerInterface;


class AuthController extends ApiInterface
{


    #[Route('/api/login', methods: ['POST'])]
    #[OA\Post(
        summary: "Permet d'authentifier un utilisateur",
        description: "Permet d'authentifier un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "login",
                        type: "string",
                        default: "konatenhamed@gmail.com"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        default: "admin93K"
                    ),
                    new OA\Property(
                        property: "device",
                        type: "string",
                        default: "devise"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function login(
        Request $request,
        JwtService $jwtService,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepo,
        SubscriptionChecker $subscriptionChecker,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['login']) || !isset($data['password'])) {
            return $this->json(['error' => 'Données invalides'], Response::HTTP_NOT_FOUND);
        }

        $user = $userRepo->findOneBy(['login' => $data['login']]);

        if (!$user) {
            return $this->json(['error' => 'Ce utilisateur n\'existe pas'], Response::HTTP_NOT_FOUND);
        } else {
            if (!$hasher->isPasswordValid($user, $data['password'])) {
                return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            } elseif (!$user->isActive()) {
                return $this->json(['error' => 'Ce compte est désactivé'], Response::HTTP_NOT_FOUND);
            }
        }


        // --- LOG DE CONNEXION ---
        $connectionLog = new ConnectionLog();
        $connectionLog->setUser($user);
        $connectionLog->setLoginAt(new \DateTimeImmutable());
        //$connectionLog->setDevice($user->getId() . 'Unknown');
        $connectionLog->setDevice($data['device'] ?? 'Unknown');
        $connectionLog->setIpAddress($request->getClientIp());

        $entityManager->persist($connectionLog);
        $entityManager->flush();

        $token = $jwtService->generateToken([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'roles' => $user->getRoles(),
            'conn_id' => $connectionLog->getId() // Ajout de l'ID de connexion au token
        ]);

       

        return $this->responseData([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'nom' => $user->getLocataire() ? $user->getLocataire()->getNom() : ($user->getEmploye() ? $user->getEmploye()->getNom() : ''),
                'prenoms' => $user->getLocataire() ? $user->getLocataire()->getPrenoms() : ($user->getEmploye() ? $user->getEmploye()->getPrenom() : ''),
                'fcm_token' => $user->getFcmToken() ?? '',
                'groupe' => $user->getGroupe() ? ["id" => $user->getGroupe()->getId(), "code" => $user->getGroupe()->getCode(), "name" => $user->getGroupe()->getName()] : null,
                'logo' => $user->getLogo() ?? null,
                'roles' => $user->getRoles(),
                'is_active' => $user->isActive(),
                'logo_entreprise' => $user->getEntreprise() ? $user->getEntreprise()->getLogo() : null,
                'entreprise' => $user->getEntreprise() ? ["id" => $user->getEntreprise()->getId(), "denomination" => $user->getEntreprise()->getDenomination()] : null,
                'pays' => $user->getEntreprise() ? ["id" => $user->getEntreprise()->getPays()->getId()] : null,
                
               
            ],
            'token_expires_in' => $jwtService->getTtl()
        ], 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/api/logout', methods: ['POST'])]
    #[OA\Post(
        summary: "Permet de déconnecter un utilisateur",
        description: "Permet de déconnecter un utilisateur en invalidant le token JWT",
        responses: [
            new OA\Response(response: 200, description: "Déconnexion réussie"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function logout(
        Request $request,
        JwtService $jwtService,
        ConnectionLogRepository $connectionLogRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->json([
                'error' => 'Token manquant',
                'success' => false
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $matches[1];

        try {
            // Récupérer le payload pour le log de déconnexion avant d'invalidatler
            $payload = $jwtService->validateToken($token);

            if ($payload) {
                // Log de déconnexion
                if (isset($payload['conn_id'])) {
                    $connectionLog = $connectionLogRepository->find($payload['conn_id']);
                    if ($connectionLog) {
                        $connectionLog->setLogoutAt(new \DateTimeImmutable());
                        $entityManager->persist($connectionLog);
                    }
                }

                // Suppression du FCM Token
                if (isset($payload['id'])) {
                    $user = $userRepository->find($payload['id']);
                    if ($user) {
                        $user->setFcmToken(null);
                        $entityManager->persist($user);
                    }
                }
                
                $entityManager->flush();
            }

            $invalidated = $jwtService->invalidateToken($token);

            if ($invalidated) {
                return $this->json([
                    'message' => 'Déconnexion réussie',
                    'success' => true,
                    'timestamp' => time()
                ]);
            } else {
                return $this->json([
                    'error' => 'Erreur lors de la déconnexion',
                    'success' => false
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la déconnexion',
                'success' => false
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
