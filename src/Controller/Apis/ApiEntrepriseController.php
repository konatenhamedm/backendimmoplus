<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Entreprise;
use App\Repository\EntrepriseRepository;
use App\Repository\PaysRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contrôleur pour la gestion des entreprises
 */
#[Route('/api/entreprise')]
#[OA\Tag(name: 'Entreprise', description: 'Gestion des entreprises')]
class ApiEntrepriseController extends ApiInterface
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
        path: "/api/entreprise/",
        summary: "Lister les entreprises",
        description: "Retourne la liste des entreprises.",
        tags: ['Entreprise']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, EntrepriseRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $entreprises = $repository->findAll();

            if ($withPagination == "true") {
                $entreprises = $this->paginationService->paginate($entreprises);
            }

            return $this->responseData($entreprises, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/entreprise/create",
        summary: "Créer une entreprise",
        description: "Ajoute une nouvelle entreprise avec support de logo.",
        tags: ['Entreprise']
    )]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "denomination", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "Sigle", type: "string"),
                    new OA\Property(property: "Agrements", type: "string"),
                    new OA\Property(property: "situation_geo", type: "string"),
                    new OA\Property(property: "contacts", type: "string"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "mobile", type: "string"),
                    new OA\Property(property: "fax", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "site_web", type: "string"),
                    new OA\Property(property: "Directeur", type: "string"),
                    new OA\Property(property: "ville", type: "string"),
                    new OA\Property(property: "numero", type: "string"),
                    new OA\Property(property: "pays_id", type: "integer"),
                    new OA\Property(property: "isActive", type: "boolean"),
                    new OA\Property(property: "logo", type: "string", format: "binary")
                ]
            )
        )
    )]
    public function create(Request $request, EntrepriseRepository $repository, PaysRepository $paysRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();
            $entreprise = new Entreprise();
            
            if (isset($data['denomination'])) {
                $entreprise->setDenomination($data['denomination']);
            } else {
                $entreprise->setDenomination("Nouvelle Entreprise");
            }

            // Génération forcée du code dans l'API
            $entreprise->setCode('ENT-' . strtoupper(substr(uniqid(), -6)));
            $entreprise->setSigle($data['Sigle'] ?? '');
            $entreprise->setAgrements($data['Agrements'] ?? '');
            $entreprise->setSituationGeo($data['situation_geo'] ?? '');
            $entreprise->setContacts($data['contacts'] ?? 'Non renseigné');
            $entreprise->setMobile($data['mobile'] ?? '');
            $entreprise->setEmail($data['email'] ?? '');
            $entreprise->setSiteWeb($data['site_web'] ?? '');
            $entreprise->setDirecteur($data['Directeur'] ?? '');
            $entreprise->setVille($data['ville'] ?? '');
            $entreprise->setNumero($data['numero'] ?? '');
            $entreprise->setIsActive(isset($data['isActive']) ? filter_var($data['isActive'], FILTER_VALIDATE_BOOLEAN) : true);

            if (isset($data['adresse'])) $entreprise->setAdresse($data['adresse']);
            if (isset($data['fax'])) $entreprise->setFax($data['fax']);

            if (isset($data['pays_id'])) {
                $pays = $paysRepository->find($data['pays_id']);
                if ($pays) $entreprise->setPays($pays);
            }

            if (isset($data['dateCreation'])) {
                $entreprise->setDateCreation(new \DateTime($data['dateCreation']));
            }

            // Gestion du logo
            $uploadedFile = $request->files->get('logo');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('logo_'.uniqid());
                $filePath = $this->getUploadDir('entreprises', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'entreprises')) {
                    $entreprise->setLogo($fichier);
                }
            }

            $this->updateAuditFields($entreprise, true);
            $repository->save($entreprise, true);

            return $this->responseData($entreprise, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/entreprise/{id}",
        summary: "Afficher une entreprise",
        description: "Retourne les détails d'une entreprise.",
        tags: ['Entreprise']
    )]
    public function show(Entreprise $entreprise): Response
    {
        try {
            if (!$entreprise) return $this->errorResponse(null, "Entreprise non trouvée", 404);
            return $this->responseData($entreprise, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])] // POST supporté pour l'upload de fichiers via multipart
    #[OA\Post(
        path: "/api/entreprise/{id}",
        summary: "Modifier une entreprise",
        description: "Met à jour une entreprise existante avec support de logo.",
        tags: ['Entreprise']
    )]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "denomination", type: "string"),
                    new OA\Property(property: "code", type: "string"),
                    new OA\Property(property: "Sigle", type: "string"),
                    new OA\Property(property: "Agrements", type: "string"),
                    new OA\Property(property: "situation_geo", type: "string"),
                    new OA\Property(property: "contacts", type: "string"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "mobile", type: "string"),
                    new OA\Property(property: "fax", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "site_web", type: "string"),
                    new OA\Property(property: "Directeur", type: "string"),
                    new OA\Property(property: "ville", type: "string"),
                    new OA\Property(property: "numero", type: "string"),
                    new OA\Property(property: "pays_id", type: "integer"),
                    new OA\Property(property: "isActive", type: "boolean"),
                    new OA\Property(property: "logo", type: "string", format: "binary")
                ]
            )
        )
    )]
    public function update(Request $request, Entreprise $entreprise, EntrepriseRepository $repository, PaysRepository $paysRepository): Response
    {
        try {
            if (!$entreprise) return $this->errorResponse(null, "Entreprise non trouvée", 404);

            $data = json_decode($request->getContent(), true) ?? $request->request->all();
            
            if (isset($data['denomination'])) $entreprise->setDenomination($data['denomination']);
            // Le code ne peut pas être modifié manuellement
            if (isset($data['Sigle'])) $entreprise->setSigle($data['Sigle']);
            if (isset($data['Agrements'])) $entreprise->setAgrements($data['Agrements']);
            if (isset($data['situation_geo'])) $entreprise->setSituationGeo($data['situation_geo']);
            if (isset($data['contacts'])) $entreprise->setContacts($data['contacts']);
            if (isset($data['adresse'])) $entreprise->setAdresse($data['adresse']);
            if (isset($data['mobile'])) $entreprise->setMobile($data['mobile']);
            if (isset($data['fax'])) $entreprise->setFax($data['fax']);
            if (isset($data['email'])) $entreprise->setEmail($data['email']);
            if (isset($data['site_web'])) $entreprise->setSiteWeb($data['site_web']);
            if (isset($data['Directeur'])) $entreprise->setDirecteur($data['Directeur']);
            if (isset($data['ville'])) $entreprise->setVille($data['ville']);
            if (isset($data['numero'])) $entreprise->setNumero($data['numero']);
            if (isset($data['isActive'])) $entreprise->setIsActive(filter_var($data['isActive'], FILTER_VALIDATE_BOOLEAN));

            if (isset($data['pays_id'])) {
                $pays = $paysRepository->find($data['pays_id']);
                if ($pays) $entreprise->setPays($pays);
            }

            if (isset($data['dateCreation'])) {
                $entreprise->setDateCreation(new \DateTime($data['dateCreation']));
            }

            // Gestion du logo
            $uploadedFile = $request->files->get('logo');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('logo_'.uniqid());
                $filePath = $this->getUploadDir('entreprises', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'entreprises')) {
                    $entreprise->setLogo($fichier);
                }
            }

            $this->updateAuditFields($entreprise);
            $repository->save($entreprise, true);

            return $this->responseData($entreprise, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/entreprise/{id}",
        summary: "Supprimer une entreprise",
        description: "Supprime une entreprise.",
        tags: ['Entreprise']
    )]
    public function delete(Entreprise $entreprise, EntrepriseRepository $repository): Response
    {
        try {
            if (!$entreprise) return $this->errorResponse(null, "Entreprise non trouvée", 404);
            $repository->remove($entreprise, true);
            return $this->response(['message' => 'Entreprise supprimée avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
