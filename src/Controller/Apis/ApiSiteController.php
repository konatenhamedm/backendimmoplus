<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Site;
use App\Repository\SiteRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/site')]
#[OA\Tag(name: 'Site', description: 'Gestion des sites de terrains')]
class ApiSiteController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, SiteRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Entreprise non trouvée", 400);
            }

            $isSuperAdmin = ($user->getGroupe() && $user->getGroupe()->getCode() === 'ADMIN');
            $agence = $isSuperAdmin ? null : $user->getAgence();
            
            // Simple filtering: we can just findBy(['entreprise' => $user->getEntreprise(), 'agence' => $agence])
            // Or via a custom repo method if it existed, but findBy is safe.
            $criteria = ['entreprise' => $user->getEntreprise()];
            if ($agence) {
                $criteria['agence'] = $agence;
            }

            $sites = $repository->findBy($criteria);

            return $this->responseData($sites, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, SiteRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $site = new Site();
            if (isset($data['nom'])) $site->setNom($data['nom']);
            if (isset($data['localisation'])) $site->setLocalisation($data['localisation']);
            if (isset($data['description'])) $site->setDescription($data['description']);
            if (isset($data['etat'])) $site->setEtat($data['etat']);
            if (isset($data['superficieTotale'])) $site->setSuperficieTotale($data['superficieTotale']);
            if (isset($data['situationGeographique'])) $site->setSituationGeographique($data['situationGeographique']);

            $site->setEntreprise($user->getEntreprise());
            if ($user->getAgence()) {
                $site->setAgence($user->getAgence());
            } elseif (isset($data['agence_id'])) {
                $agence = $this->em->getRepository(\App\Entity\Agence::class)->find($data['agence_id']);
                if ($agence) $site->setAgence($agence);
            }

            // Upload Plan
            $uploadedFile = $request->files->get('planLotissement');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('plan_site_'.uniqid());
                $filePath = $this->getUploadDir('sites', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'sites')) {
                    $site->setPlanLotissement($fichier);
                }
            }

            $this->updateAuditFields($site, true);
            $repository->save($site, true);

            return $this->responseData($site, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    public function update(Request $request, Site $site, SiteRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['nom'])) $site->setNom($data['nom']);
            if (isset($data['localisation'])) $site->setLocalisation($data['localisation']);
            if (isset($data['description'])) $site->setDescription($data['description']);
            if (isset($data['etat'])) $site->setEtat($data['etat']);
            if (isset($data['superficieTotale'])) $site->setSuperficieTotale($data['superficieTotale']);
            if (isset($data['situationGeographique'])) $site->setSituationGeographique($data['situationGeographique']);

            $uploadedFile = $request->files->get('planLotissement');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('plan_site_'.uniqid());
                $filePath = $this->getUploadDir('sites', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'sites')) {
                    $site->setPlanLotissement($fichier);
                }
            }

            $this->updateAuditFields($site);
            $repository->save($site, true);

            return $this->responseData($site, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Site $site, SiteRepository $repository): Response
    {
        try {
            $repository->remove($site, true);
            return $this->response(['message' => 'Site supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
