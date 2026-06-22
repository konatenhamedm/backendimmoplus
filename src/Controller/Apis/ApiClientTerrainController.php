<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ClientTerrain;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/client-terrain')]
#[OA\Tag(name: 'ClientTerrain', description: 'Gestion des clients (acheteurs) de terrains')]
class ApiClientTerrainController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Entreprise non trouvée", 400);
            }

            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            $agence = $isSuperAdmin ? null : $user->getAgence();
            
            $criteria = ['entreprise' => $user->getEntreprise()];
            if ($agence) {
                $criteria['agence'] = $agence;
            }

            $clients = $em->getRepository(ClientTerrain::class)->findBy($criteria);

            return $this->responseData($clients, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $client = new ClientTerrain();
            if (isset($data['nom'])) $client->setNom($data['nom']);
            if (isset($data['prenoms'])) $client->setPrenoms($data['prenoms']);
            if (isset($data['contact'])) $client->setContact($data['contact']);
            if (isset($data['email'])) $client->setEmail($data['email']);
            if (isset($data['adresse'])) $client->setAdresse($data['adresse']);
            if (isset($data['pieceIdentite'])) $client->setPieceIdentite($data['pieceIdentite']);
            
            $client->setEntreprise($user->getEntreprise());
            if ($user->getAgence()) {
                $client->setAgence($user->getAgence());
            } elseif (isset($data['agence_id'])) {
                $agence = $em->getRepository(\App\Entity\Agence::class)->find($data['agence_id']);
                if ($agence) $client->setAgence($agence);
            }

            $this->updateAuditFields($client, true);
            $em->persist($client);
            $em->flush();

            return $this->responseData($client, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, ClientTerrain $client, EntityManagerInterface $em): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['nom'])) $client->setNom($data['nom']);
            if (isset($data['prenoms'])) $client->setPrenoms($data['prenoms']);
            if (isset($data['contact'])) $client->setContact($data['contact']);
            if (isset($data['email'])) $client->setEmail($data['email']);
            if (isset($data['adresse'])) $client->setAdresse($data['adresse']);
            if (isset($data['pieceIdentite'])) $client->setPieceIdentite($data['pieceIdentite']);
            
            $this->updateAuditFields($client);
            $em->flush();

            return $this->responseData($client, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(ClientTerrain $client, EntityManagerInterface $em): Response
    {
        try {
            $em->remove($client);
            $em->flush();
            return $this->response(['message' => 'Client supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
