<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ConfigApp;
use App\Repository\ConfigAppRepository;
use App\Repository\FichierRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/config-app')]
#[OA\Tag(name: 'ConfigApp', description: 'Configuration de l\'application')]
class ApiConfigAppController extends ApiInterface
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
        path: "/api/config-app/",
        summary: "Obtenir la configuration",
        description: "Retourne la configuration de l'application pour l'entreprise connectée.",
        tags: ['ConfigApp']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, ConfigAppRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $config = $repository->findOneByEntreprise($this->getUser()->getEntreprise());
                if (!$config) {
                    return $this->response(['message' => 'Aucune configuration trouvée'], 404);
                }
                return $this->responseData($config, 'group1', [], false); // Config unique, pas de pagination
            }
            return $this->errorResponse(null, "Entreprise non identifiée", 400);

        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/save', methods: ['POST'])]
    #[OA\Post(
        path: "/api/config-app/save",
        summary: "Enregistrer la configuration",
        description: "Crée ou met à jour la configuration de l'application.",
        tags: ['ConfigApp']
    )]
    public function save(Request $request, ConfigAppRepository $repository, FichierRepository $fichierRepository): Response
    {
        try {
            if (!$this->getUser() || !$this->getUser()->getEntreprise()) {
                return $this->errorResponse(null, "Identifiez-vous avec une entreprise", 401);
            }

            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }
            
            $entreprise = $this->getUser()->getEntreprise();
            
            $config = $repository->findOneByEntreprise($entreprise);
            $isNew = false;
            if (!$config) {
                $config = new ConfigApp();
                $config->setEntreprise($entreprise);
                $isNew = true;
            }

            if (isset($data['mainColorAdmin'])) $config->setMainColorAdmin($data['mainColorAdmin']);
            if (isset($data['defaultColorAdmin'])) $config->setDefaultColorAdmin($data['defaultColorAdmin']);
            if (isset($data['mainColorLogin'])) $config->setMainColorLogin($data['mainColorLogin']);
            if (isset($data['defaultColorLogin'])) $config->setDefaultColorLogin($data['defaultColorLogin']);

            // Handle file uploads
            $files = [
                'logo' => 'setLogo',
                'favicon' => 'setFavicon',
                'imageLogin' => 'setImageLogin',
                'logoLogin' => 'setLogoLogin'
            ];

            foreach ($files as $field => $setter) {
                $uploadedFile = $request->files->get($field);
                if ($uploadedFile) {
                    $filePrefix = $this->slugger->slug($field . '_' . uniqid());
                    $filePath = $this->getUploadDir('config_app', true);
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'config_app')) {
                        $config->$setter($fichier);
                    }
                } elseif (isset($data[$field . '_id'])) {
                     $fichier = $fichierRepository->find($data[$field . '_id']);
                     if ($fichier) $config->$setter($fichier);
                }
            }

            $this->updateAuditFields($config, $isNew);
            $repository->save($config, true);

            return $this->responseData($config);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
