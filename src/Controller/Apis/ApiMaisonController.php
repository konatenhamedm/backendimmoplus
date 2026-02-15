<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Appartement;
use App\Entity\Maison;
use App\Repository\MaisonRepository;
use App\Repository\ProprioRepository;
use App\Repository\QuartierRepository;
use App\Repository\TypeMaisonRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/maison')]
#[OA\Tag(name: 'Maison', description: 'Gestion des maisons')]
class ApiMaisonController extends ApiInterface
{
    /**
     * @return \App\Entity\User|null
     */
    protected function getUser(): ?\App\Entity\User
    {
        return parent::getUser();
    }

    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/maison/",
        summary: "Lister les maisons",
        description: "Retourne la liste des maisons (filtrées par entreprise du propriétaire).",
        tags: ['Maison']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, MaisonRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $maisons = $repository->findAllByEntreprise($this->getUser()->getEntreprise());
            } else {
                $maisons = $repository->findAll();
            }

            if ($withPagination == "true") {
                $maisons = $this->paginationService->paginate($maisons);
            }

            return $this->responseData($maisons, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/maison/create",
        summary: "Créer une maison",
        description: "Ajoute une nouvelle maison.",
        tags: ['Maison']
    )]
    public function create(Request $request, MaisonRepository $repository, QuartierRepository $quartierRepository, ProprioRepository $proprioRepository, TypeMaisonRepository $typeMaisonRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $maison = new Maison();
            
            if (isset($data['LibMaison'])) $maison->setLibMaison($data['LibMaison']);
            if (isset($data['Lot'])) $maison->setLot($data['Lot']);
            if (isset($data['Ilot'])) $maison->setIlot($data['Ilot']);
            if (isset($data['MntCom'])) $maison->setMntCom($data['MntCom']);
            if (isset($data['Localisation'])) $maison->setLocalisation($data['Localisation']);
            if (isset($data['TFoncier'])) $maison->setTFoncier($data['TFoncier']);

            if (isset($data['quartier_id'])) {
                $quartier = $quartierRepository->find($data['quartier_id']);
                if (!$quartier) return $this->errorResponse(null, "Quartier non trouvé", 404);
                $maison->setQuartier($quartier);
            }
            if (isset($data['proprio_id'])) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
                $maison->setProprio($proprio);
            }
            if (isset($data['type_maison_id'])) {
                $typeMaison = $typeMaisonRepository->find($data['type_maison_id']);
                if (!$typeMaison) return $this->errorResponse(null, "Type de maison non trouvé", 404);
                $maison->setTypeMaison($typeMaison);
            }

            if ($this->getUser()) {
                $maison->setIdAgent($this->getUser());
            }

            // Gestion des appartements inclus
            if (isset($data['appartements']) && is_array($data['appartements'])) {
                foreach ($data['appartements'] as $appartData) {
                    $appartement = new Appartement();
                    if (isset($appartData['LibAppart'])) $appartement->setLibAppart($appartData['LibAppart']);
                    if (isset($appartData['NbrePieces'])) $appartement->setNbrePieces($appartData['NbrePieces']);
                    if (isset($appartData['NumEtage'])) $appartement->setNumEtage($appartData['NumEtage']);
                    if (isset($appartData['Loyer'])) $appartement->setLoyer($appartData['Loyer']);
                    if (isset($appartData['Details'])) $appartement->setDetails($appartData['Details']);
                    //if (isset($appartData['Oqp'])) $appartement->setOqp($appartData['Oqp']);
                    
                    $appartement->setMaisson($maison);
                    $this->updateAuditFields($appartement, true);
                    $maison->addAppartement($appartement);
                }
            }

            $this->updateAuditFields($maison, true);

            $repository->save($maison, true);

            return $this->responseData($maison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(
        path: "/api/maison/{id}",
        summary: "Modifier une maison",
        description: "Met à jour une maison existante et ses appartements.",
        tags: ['Maison']
    )]
    public function update(Request $request, Maison $maison, MaisonRepository $repository, QuartierRepository $quartierRepository, ProprioRepository $proprioRepository, TypeMaisonRepository $typeMaisonRepository, \App\Repository\AppartementRepository $appartementRepository): Response
    {
        try {
            if (!$maison) return $this->errorResponse(null, "Maison non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            
            if (isset($data['LibMaison'])) $maison->setLibMaison($data['LibMaison']);
            if (isset($data['Lot'])) $maison->setLot($data['Lot']);
            if (isset($data['Ilot'])) $maison->setIlot($data['Ilot']);
            if (isset($data['MntCom'])) $maison->setMntCom($data['MntCom']);
            if (isset($data['Localisation'])) $maison->setLocalisation($data['Localisation']);
            if (isset($data['TFoncier'])) $maison->setTFoncier($data['TFoncier']);

            if (isset($data['quartier_id'])) {
                $quartier = $quartierRepository->find($data['quartier_id']);
                if (!$quartier) return $this->errorResponse(null, "Quartier non trouvé", 404);
                $maison->setQuartier($quartier);
            }
            if (isset($data['proprio_id'])) {
                $proprio = $proprioRepository->find($data['proprio_id']);
                if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
                $maison->setProprio($proprio);
            }
             if (isset($data['type_maison_id'])) {
                $typeMaison = $typeMaisonRepository->find($data['type_maison_id']);
                if (!$typeMaison) return $this->errorResponse(null, "Type de maison non trouvé", 404);
                $maison->setTypeMaison($typeMaison);
            }

            // Gestion des appartements inclus (Mise à jour ou Ajout)
            if (isset($data['appartements']) && is_array($data['appartements'])) {
                foreach ($data['appartements'] as $appartData) {
                    if (isset($appartData['id'])) {
                        // Update existing apartment if it belongs to this maison
                        $appartement = $appartementRepository->find($appartData['id']);
                        if ($appartement && $appartement->getMaisson() === $maison) {
                            if (isset($appartData['LibAppart'])) $appartement->setLibAppart($appartData['LibAppart']);
                            if (isset($appartData['NbrePieces'])) $appartement->setNbrePieces($appartData['NbrePieces']);
                            if (isset($appartData['NumEtage'])) $appartement->setNumEtage($appartData['NumEtage']);
                            if (isset($appartData['Loyer'])) $appartement->setLoyer($appartData['Loyer']);
                            if (isset($appartData['Details'])) $appartement->setDetails($appartData['Details']);
                             // Only update 'Oqp' if explicitely provided
                            if (isset($appartData['Oqp'])) $appartement->setOqp($appartData['Oqp']);
                            
                            $this->updateAuditFields($appartement);
                        }
                    } else {
                        // Create new apartment
                        $appartement = new Appartement();
                        if (isset($appartData['LibAppart'])) $appartement->setLibAppart($appartData['LibAppart']);
                        if (isset($appartData['NbrePieces'])) $appartement->setNbrePieces($appartData['NbrePieces']);
                        if (isset($appartData['NumEtage'])) $appartement->setNumEtage($appartData['NumEtage']);
                        if (isset($appartData['Loyer'])) $appartement->setLoyer($appartData['Loyer']);
                        if (isset($appartData['Details'])) $appartement->setDetails($appartData['Details']);
                        if (isset($appartData['Oqp'])) $appartement->setOqp($appartData['Oqp']);
                        
                        $this->updateAuditFields($appartement, true);
                        $maison->addAppartement($appartement);
                    }
                }
            }

            $this->updateAuditFields($maison);

            $repository->save($maison, true);

            return $this->responseData($maison, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/maison/{id}",
        summary: "Supprimer une maison",
        description: "Supprime une maison.",
        tags: ['Maison']
    )]
    public function delete(Maison $maison, MaisonRepository $repository): Response
    {
        try {
            if (!$maison) return $this->errorResponse(null, "Maison non trouvée", 404);
            $repository->remove($maison, true);
            return $this->response(['message' => 'Maison supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
