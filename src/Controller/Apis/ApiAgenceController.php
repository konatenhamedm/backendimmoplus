<?php

namespace App\Controller\Apis;

use App\Entity\Agence;
use App\Repository\AgenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use App\Controller\Apis\Config\ApiInterface;

#[Route('/api/agence')]
class ApiAgenceController extends ApiInterface
{
    #[Route('/', name: 'api_agence_index', methods: ['GET'])]
    #[OA\Get(
        path: "/api/agence/",
        summary: "Liste de toutes les agences de l'entreprise (Admin) ou l'agence de l'utilisateur",
        tags: ['Agence']
    )]
    public function index(Request $request, AgenceRepository $agenceRepository): Response
    {
        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['message' => 'Non autorisé'], 401);
            }

            $entreprise = $user->getEntreprise();
            if (!$entreprise) {
                 return $this->responseData([], 'group1');
            }

            // Si ADMIN ou SADM, renvoie toutes les agences de l'entreprise
            if ($user->getGroupe() && in_array($user->getGroupe()->getCode(), ['ADMIN', 'SADM'])) {
                $agences = $agenceRepository->findBy(['entreprise' => $entreprise, 'isActive' => true], ['id' => 'DESC']);
                return $this->responseData($agences, 'group1');
            }

            // Sinon (employé classique), on renvoie uniquement son agence
            if ($user->getAgence()) {
                return $this->responseData([$user->getAgence()], 'group1');
            }

            return $this->responseData([], 'group1');

        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 500);
        }
    }

    #[Route('/create', name: 'api_agence_create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/agence/create",
        summary: "Créer une nouvelle agence (Admin uniquement)",
        tags: ['Agence']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["nom"],
            properties: [
                new OA\Property(property: "nom", type: "string", example: "Agence Centrale"),
                new OA\Property(property: "adresse", type: "string", example: "Cocody Riviera 2"),
                new OA\Property(property: "contact", type: "string", example: "0102030405"),
                new OA\Property(property: "email", type: "string", example: "centrale@monagence.com")
            ]
        )
    )]
    public function create(Request $request, AgenceRepository $agenceRepository, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !in_array($user->getGroupe()->getCode(), ['ADMIN', 'SADM'])) {
                return $this->json(['message' => 'Non autorisé ou rôle insuffisant'], 403);
            }

            $entreprise = $user->getEntreprise();
            if (!$entreprise) {
                return $this->json(['message' => 'Entreprise non trouvée'], 404);
            }

            // TODO: Vérifier le forfait (ENTERPRISE) si nécessaire, ou on laisse ouvert par défaut et on bloque plus tard.
            
            $data = json_decode($request->getContent(), true);

            if (empty($data['nom'])) {
                return $this->json(['message' => 'Le nom de l\'agence est obligatoire'], 400);
            }

            $agence = new Agence();
            $agence->setNom($data['nom']);
            $agence->setAdresse($data['adresse'] ?? null);
            $agence->setContact($data['contact'] ?? null);
            $agence->setEmail($data['email'] ?? null);
            $agence->setEntreprise($entreprise);
            $agence->setIsActive(true);

            $em->persist($agence);
            $em->flush();

            return $this->responseData($agence, 'group1');

        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 500);
        }
    }

    #[Route('/{id}/update', name: 'api_agence_update', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/agence/{id}/update",
        summary: "Modifier une agence",
        tags: ['Agence']
    )]
    public function update(Agence $agence, Request $request, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        try {
             $user = $this->getUser();
             if (!$user || !in_array($user->getGroupe()->getCode(), ['ADMIN', 'SADM'])) {
                return $this->json(['message' => 'Non autorisé'], 403);
             }

             if ($agence->getEntreprise() !== $user->getEntreprise()) {
                 return $this->json(['message' => 'Cette agence ne vous appartient pas'], 403);
             }

             $data = json_decode($request->getContent(), true);

             if (isset($data['nom'])) $agence->setNom($data['nom']);
             if (isset($data['adresse'])) $agence->setAdresse($data['adresse']);
             if (isset($data['contact'])) $agence->setContact($data['contact']);
             if (isset($data['email'])) $agence->setEmail($data['email']);
             if (array_key_exists('isActive', $data)) $agence->setIsActive($data['isActive']);

             $em->flush();

             return $this->responseData($agence, 'group1');

        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 500);
        }
    }

    #[Route('/{id}', name: 'api_agence_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/agence/{id}",
        summary: "Supprimer (désactiver) une agence",
        tags: ['Agence']
    )]
    public function delete(Agence $agence, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !in_array($user->getGroupe()->getCode(), ['ADMIN', 'SADM'])) {
                return $this->json(['message' => 'Non autorisé'], 403);
            }

            if ($agence->getEntreprise() !== $user->getEntreprise()) {
                return $this->json(['message' => 'Ceci n\'est pas à vous'], 403);
            }

            // Soft delete ou Hard delete ? Soft delete recommandé
            $agence->setIsActive(false);
            $em->flush();

            return $this->json(['message' => 'Agence supprimée avec succès']);

        } catch (\Exception $exception) {
            return $this->json(['message' => $exception->getMessage()], 500);
        }
    }
}
