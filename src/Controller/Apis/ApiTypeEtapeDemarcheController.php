<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\TypeEtapeDemarche;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/type-etape-demarche')]
#[OA\Tag(name: 'TypeEtapeDemarche', description: 'Paramètres : types d\'étapes de démarche administrative')]
class ApiTypeEtapeDemarcheController extends ApiInterface
{
    /**
     * Retourne toutes les étapes configurées pour l'entreprise.
     * Si aucune n'existe, injecte automatiquement les valeurs par défaut.
     */
    #[Route('/', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Entreprise non trouvée", 400);
            }

            $types = $em->getRepository(TypeEtapeDemarche::class)->findBy(
                ['entreprise' => $user->getEntreprise()],
                ['ordre' => 'ASC']
            );

            // Injection automatique des défauts si aucun type configuré
            if (count($types) === 0) {
                $types = $this->injecterDefauts($em, $user->getEntreprise());
            }

            return $this->responseData($types, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Crée une nouvelle étape personnalisée */
    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $type = new TypeEtapeDemarche();
            $type->setNom($data['nom']);
            $type->setDescription($data['description'] ?? null);
            $type->setOrdre((int)($data['ordre'] ?? 99));
            $type->setIsActif($data['isActif'] ?? true);
            $type->setEntreprise($user->getEntreprise());

            $this->updateAuditFields($type, true);
            $em->persist($type);
            $em->flush();

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Modifie une étape existante */
    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(Request $request, TypeEtapeDemarche $type, EntityManagerInterface $em): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['nom'])) $type->setNom($data['nom']);
            if (isset($data['description'])) $type->setDescription($data['description']);
            if (isset($data['ordre'])) $type->setOrdre((int)$data['ordre']);
            if (isset($data['isActif'])) $type->setIsActif((bool)$data['isActif']);

            $this->updateAuditFields($type);
            $em->flush();

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Supprime une étape */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(TypeEtapeDemarche $type, EntityManagerInterface $em): Response
    {
        try {
            $em->remove($type);
            $em->flush();
            return $this->response(['message' => 'Étape supprimée']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /**
     * Réinitialise toutes les étapes aux valeurs par défaut.
     * Supprime les existantes et recrée les 7 étapes standard.
     */
    #[Route('/initialiser', methods: ['POST'])]
    public function initialiser(EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            // Supprimer les existantes
            $existants = $em->getRepository(TypeEtapeDemarche::class)->findBy(['entreprise' => $user->getEntreprise()]);
            foreach ($existants as $e) {
                $em->remove($e);
            }
            $em->flush();

            // Recréer les défauts
            $types = $this->injecterDefauts($em, $user->getEntreprise());

            return $this->responseData($types, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Service interne : injecte les valeurs par défaut pour une entreprise */
    private function injecterDefauts(EntityManagerInterface $em, $entreprise): array
    {
        $types = [];
        foreach (TypeEtapeDemarche::DEFAULTS as $def) {
            $type = new TypeEtapeDemarche();
            $type->setNom($def['nom']);
            $type->setDescription($def['description']);
            $type->setOrdre($def['ordre']);
            $type->setIsActif(true);
            $type->setEntreprise($entreprise);
            $em->persist($type);
            $types[] = $type;
        }
        $em->flush();
        return $types;
    }
}
