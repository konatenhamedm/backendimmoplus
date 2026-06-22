<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\TypeFraisTerrain;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/type-frais-terrain')]
#[OA\Tag(name: 'TypeFraisTerrain', description: 'Paramètres : types de frais de démarche foncière')]
class ApiTypeFraisTerrainController extends ApiInterface
{
    /**
     * Retourne tous les types de frais pour l'entreprise.
     * Injecte automatiquement les défauts si aucun n'est configuré.
     */
    #[Route('/', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Entreprise non trouvée", 400);
            }

            $types = $em->getRepository(TypeFraisTerrain::class)->findBy(
                ['entreprise' => $user->getEntreprise()],
                ['id' => 'ASC']
            );

            // Injection automatique si aucun type
            if (count($types) === 0) {
                $types = $this->injecterDefauts($em, $user->getEntreprise());
            }

            return $this->responseData($types, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Crée un nouveau type de frais */
    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $type = new TypeFraisTerrain();
            $type->setNom($data['nom']);
            $type->setDescription($data['description'] ?? null);
            $type->setMontantDefaut($data['montantDefaut'] ?? '0');
            $type->setIsActif($data['isActif'] ?? true);
            if (isset($data['typeEtapeDemarche_id'])) {
                $etape = $em->getRepository(\App\Entity\TypeEtapeDemarche::class)->find($data['typeEtapeDemarche_id']);
                $type->setTypeEtapeDemarche($etape);
            }
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

    /** Modifie un type de frais */
    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(Request $request, TypeFraisTerrain $type, EntityManagerInterface $em): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['nom'])) $type->setNom($data['nom']);
            if (isset($data['description'])) $type->setDescription($data['description']);
            if (isset($data['montantDefaut'])) $type->setMontantDefaut($data['montantDefaut']);
            if (isset($data['isActif'])) $type->setIsActif((bool)$data['isActif']);
            if (isset($data['typeEtapeDemarche_id'])) {
                $etape = $em->getRepository(\App\Entity\TypeEtapeDemarche::class)->find($data['typeEtapeDemarche_id']);
                $type->setTypeEtapeDemarche($etape);
            }

            $this->updateAuditFields($type);
            $em->flush();

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Supprime un type de frais */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(TypeFraisTerrain $type, EntityManagerInterface $em): Response
    {
        try {
            $em->remove($type);
            $em->flush();
            return $this->response(['message' => 'Type de frais supprimé']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Réinitialise aux valeurs par défaut */
    #[Route('/initialiser', methods: ['POST'])]
    public function initialiser(EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $existants = $em->getRepository(TypeFraisTerrain::class)->findBy(['entreprise' => $user->getEntreprise()]);
            foreach ($existants as $e) {
                $em->remove($e);
            }
            $em->flush();

            $types = $this->injecterDefauts($em, $user->getEntreprise());

            return $this->responseData($types, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /** Service interne : injecte les valeurs par défaut */
    private function injecterDefauts(EntityManagerInterface $em, $entreprise): array
    {
        $types = [];
        foreach (TypeFraisTerrain::DEFAULTS as $def) {
            $type = new TypeFraisTerrain();
            $type->setNom($def['nom']);
            $type->setDescription($def['description']);
            $type->setMontantDefaut($def['montantDefaut']);
            $type->setIsActif(true);
            $type->setEntreprise($entreprise);
            $em->persist($type);
            $types[] = $type;
        }
        $em->flush();
        return $types;
    }
}
