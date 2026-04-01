<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\LeadContact;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[Route('/api/lead-contact')]
#[OA\Tag(name: 'LeadContact', description: 'Gestion des leads et demandes de contact')]
class ApiLeadContactController extends ApiInterface
{
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/lead-contact/create",
        summary: "Créer une demande de contact (Lead)",
        description: "Enregistre une demande et envoie un mail à l'administration."
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "phone", type: "string"),
                new OA\Property(property: "company", type: "string"),
                new OA\Property(property: "planName", type: "string"),
                new OA\Property(property: "message", type: "string")
            ]
        )
    )]
    public function create(
        Request $request, 
        EntityManagerInterface $em, 
        SendMailService $mailService
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['name']) || !isset($data['email']) || !isset($data['phone'])) {
                return $this->errorResponse(null, "Données manquantes (nom, email ou téléphone)", 400);
            }

            $lead = new LeadContact();
            $lead->setName($data['name']);
            $lead->setEmail($data['email']);
            $lead->setPhone($data['phone']);
            $lead->setCompany($data['company'] ?? '');
            $lead->setPlanName($data['planName'] ?? 'Non spécifié');
            $lead->setMessage($data['message'] ?? '');

            $em->persist($lead);
            $em->flush();

            // Envoi de l'email à l'administration
            try {
                $mailService->send(
                    'contact@motiplus.pro', // From
                    'konate@motiplus.pro', // To (Admin)
                    "🚀 Nouvelle demande de plan annuel : " . ($data['planName'] ?? 'Contact'),
                    'lead_contact',
                    [
                        'name' => $lead->getName(),
                        'email' => $lead->getEmail(),
                        'phone' => $lead->getPhone(),
                        'company' => $lead->getCompany(),
                        'planName' => $lead->getPlanName(),
                        'message' => $lead->getMessage()
                    ]
                );
            } catch (\Exception $mailEx) {
                // On log l'erreur mail mais on ne bloque pas la réponse client
                error_log("Erreur envoi email LeadContact: " . $mailEx->getMessage());
            }

            return $this->response([
                'message' => 'Votre demande a été enregistrée avec succès. Notre équipe vous contactera sous peu.'
            ]);

        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
