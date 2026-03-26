<?php

namespace App\Controller\Apis;

use App\Entity\FactureLocation;
use App\Repository\FactureLocationRepository;
use App\Service\PaiementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

use App\Controller\Apis\Config\ApiInterface;

#[Route('/api/paiement')]
class ApiPaiementController extends ApiInterface
{
    public function __construct(
        \Doctrine\ORM\EntityManagerInterface $em,
        \Symfony\Component\String\Slugger\SluggerInterface $slugger,
        \App\Service\SendMailService $sendMailService,
        \App\Service\SubscriptionChecker $subscriptionChecker,
        \App\Service\Utils $utils,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher,
        \Symfony\Contracts\HttpClient\HttpClientInterface $client,
        \Symfony\Component\Serializer\SerializerInterface $serializer,
        \Symfony\Component\Validator\Validator\ValidatorInterface $validator,
        \App\Repository\UserRepository $userRepository,
        \App\Service\PaginationService $paginationService,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(param: 'send_mail')] string $sendMail,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(param: 'super_admin')] string $superAdmin,
        private PaiementService $paiementService,
        ?\App\Service\NotificationService $notificationService = null
    ) {
        parent::__construct(
            $em, $slugger, $sendMailService, $subscriptionChecker, $utils,
            $hasher, $client, $serializer, $validator, $userRepository,
            $paginationService, $sendMail, $superAdmin, $notificationService
        );
    }

    #[Route('/webhook', name: 'api_paiement_webhook', methods: ['POST', 'GET'])]
    public function webhook(Request $request): JsonResponse
    {
        // Handle both GET (if provider redirects) and POST (webhook data)
        $data = $request->query->all();
        $postData = json_decode($request->getContent(), true);

        if ($postData) {
            $data = array_merge($data, $postData);
        }

        // Sometimes data comes as form-data
        if ($request->request->count() > 0) {
            $data = array_merge($data, $request->request->all());
        }

        $response = $this->paiementService->handleWebhook($data);

        return $this->json($response);
    }

    /**
     * Initie un paiement pour une facture de location
     */
    #[Route('/initie/paiement/facture/location/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/initie/paiement/facture/location/{id}",
        summary: "Initier le paiement d'une facture de location",
        tags: ['Paiement']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["email", "numero", "operateur"],
            properties: [
                new OA\Property(property: "email", type: "string", example: "client@email.com"),
                new OA\Property(property: "numero", type: "string", example: "0708091011"),
                new OA\Property(property: "operateur", type: "string", example: "ORANGE_CI"),
                new OA\Property(property: "returnURL", type: "string", example: "https://myapp.com/success")
            ]
        )
    )]
    public function initiePaiementFactureLocation(
        Request $request,
        FactureLocation $factureLocation,
        PaiementService $paiementService
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$factureLocation) {
             return $this->json(['message' => 'Facture non trouvée'], 404);
        }

        if ($factureLocation->getStatut() === 'payer') {
             return $this->json(['message' => 'Cette facture est déjà payée'], 400);
        }

        $user = $this->getUser();
        if (!$user) {
             return $this->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $result = $paiementService->traiterPaiement($data, $user, $factureLocation);

        if (isset($result['code']) && $result['code'] !== 200) {
            return $this->json($result, 400);
        }

        return $this->json($result);
    }

    #[Route('/initie/paiement/abonnement/{id}', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/initie/paiement/abonnement/{id}",
        summary: "Initier le paiement d'un abonnement",
        tags: ['Paiement']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["email", "numero", "operateur", "module_abonnement_id"],
            properties: [
                new OA\Property(property: "email", type: "string", example: "client@email.com"),
                new OA\Property(property: "numero", type: "string", example: "0708091011"),
                new OA\Property(property: "operateur", type: "string", example: "MOBILE"),
                new OA\Property(property: "module_abonnement_id", type: "integer", example: 1),
                new OA\Property(property: "returnURL", type: "string", example: "https://myapp.com/success")
            ]
        )
    )]
    public function initiePaiementAbonnement(
        Request $request,
        \App\Entity\Entreprise $entreprise,
        PaiementService $paiementService,
        \App\Repository\ModuleAbonnementRepository $moduleRepo
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$entreprise) {
             return $this->json(['message' => 'Entreprise non trouvée'], 404);
        }

        $moduleId = $data['module_abonnement_id'] ?? null;
        if (!$moduleId) {
             return $this->json(['message' => 'Module abonnement manquant'], 400);
        }
        $module = $moduleRepo->find($moduleId);
        if (!$module) {
             return $this->json(['message' => 'Module introuvable'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
             return $this->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $result = $paiementService->traiterPaiementAbonnement($data, $user, $entreprise, $module);

        if (isset($result['code']) && $result['code'] !== 200) {
            return $this->json($result, 400);
        }

        return $this->json($result);
    }

    #[Route('', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement",
        summary: "Liste de toutes les transactions",
        tags: ['Paiement']
    )]
    public function index(\App\Repository\TransactionRepository $repository, Request $request): Response
    {
        try {
            $agenceId = $request->query->get('agence_id');
            $qb = $repository->createQueryBuilder('t')
                ->orderBy('t.date', 'DESC');
            
            if ($agenceId && $agenceId !== 'all') {
                $qb->leftJoin('t.locataire', 'l')
                   ->andWhere('l.agence = :agenceId')
                   ->setParameter('agenceId', $agenceId);
            }
            
            $transactions = $qb->getQuery()->getResult();
            return $this->responseData($transactions, 'group1');
        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 500);
        }
    }

    #[Route('/mes-paiements', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement/mes-paiements",
        summary: "Mes paiements (Espace Locataire)",
        tags: ['Paiement']
    )]
    public function mesPaiements(\App\Repository\TransactionRepository $repository): Response
    {
        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if (!$user || !$user->getLocataire()) {
                return $this->json(['message' => 'Profil locataire non trouvé'], 404);
            }
            
            $transactions = $repository->findBy(['locataire' => $user->getLocataire()->getId()], ['date' => 'DESC']);
            return $this->responseData($transactions, 'group1');
        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 500);
        }
    }
}
