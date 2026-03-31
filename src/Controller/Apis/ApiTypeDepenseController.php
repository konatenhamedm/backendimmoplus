<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\TypeDepense;
use App\Repository\TypeDepenseRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/type-depense')]
#[OA\Tag(name: 'TypeDepense', description: "Gestion des types de dépenses d'agence")]
class ApiTypeDepenseController extends ApiInterface
{
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    /**
     * Liste tous les types de dépenses de l'entreprise courante.
     */
    #[Route('', methods: ['GET'])]
    public function index(TypeDepenseRepository $repo): Response
    {
        try {
            $entreprise = $this->getUser()->getEntreprise();
            $types = $repo->findBy(['entreprise' => $entreprise], ['libelle' => 'ASC']);
            return $this->responseData($types, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /**
     * Créer un nouveau type de dépense.
     */
    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, TypeDepenseRepository $repo): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (empty($data['libelle'])) {
                $this->setStatusCode(400);
                return $this->response(['message' => 'Le libellé est requis']);
            }

            $type = new TypeDepense();
            $type->setLibelle(trim($data['libelle']));
            $type->setDescription($data['description'] ?? null);
            $type->setEntreprise($this->getUser()->getEntreprise());

            $this->updateAuditFields($type, true);
            $repo->save($type, true);

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /**
     * Modifier un type de dépense.
     */
    #[Route('/{id}', methods: ['PUT', 'POST'])]
    public function update(Request $request, TypeDepense $type, TypeDepenseRepository $repo): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['libelle'])) $type->setLibelle(trim($data['libelle']));
            if (array_key_exists('description', $data)) $type->setDescription($data['description']);

            $this->updateAuditFields($type);
            $repo->save($type, true);

            return $this->responseData($type, 'group1');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }

    /**
     * Supprimer un type de dépense.
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(TypeDepense $type, TypeDepenseRepository $repo): Response
    {
        try {
            // Vérifie si des dépenses sont liées à ce type
            if ($type->getDepenses()->count() > 0) {
                $this->setStatusCode(409);
                return $this->response([
                    'message' => "Impossible de supprimer ce type : {$type->getDepenses()->count()} dépense(s) y sont liées."
                ]);
            }
            $repo->remove($type, true);
            return $this->response(['message' => 'Type de dépense supprimé avec succès']);
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            return $this->response(['message' => $e->getMessage()]);
        }
    }
}
