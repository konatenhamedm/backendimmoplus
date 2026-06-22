<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Terrain;
use App\Repository\TerrainRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/terrain')]
#[OA\Tag(name: 'Terrain', description: 'Gestion des lots de terrains')]
class ApiTerrainController extends ApiInterface
{
    #[Route('/', methods: ['GET'])]
    public function index(Request $request, TerrainRepository $repository): Response
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
            if ($request->query->has('site_id')) {
                $criteria['site'] = $request->query->get('site_id');
            }

            $terrains = $repository->findBy($criteria);

            return $this->responseData($terrains, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request, TerrainRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getEntreprise()) {
                return $this->errorResponse(null, "Non autorisé", 403);
            }

            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            $terrain = new Terrain();
            if (isset($data['num'])) $terrain->setNum($data['num']);
            if (isset($data['superfice'])) $terrain->setSuperfice($data['superfice']);
            if (isset($data['prix'])) $terrain->setPrix($data['prix']);
            if (isset($data['etat'])) $terrain->setEtat($data['etat']);
            if (isset($data['dimensions'])) $terrain->setDimensions($data['dimensions']);
            if (isset($data['coordonneesPolygone'])) $terrain->setCoordonneesPolygone($data['coordonneesPolygone']);
            
            if (isset($data['site_id'])) {
                $site = $this->em->getRepository(\App\Entity\Site::class)->find($data['site_id']);
                if ($site) $terrain->setSite($site);
            }

            $terrain->setEntreprise($user->getEntreprise());
            if ($user->getAgence()) {
                $terrain->setAgence($user->getAgence());
            } elseif (isset($data['agence_id'])) {
                $agence = $this->em->getRepository(\App\Entity\Agence::class)->find($data['agence_id']);
                if ($agence) $terrain->setAgence($agence);
            }

            $uploadedFile = $request->files->get('planTopographique');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('topo_'.uniqid());
                $filePath = $this->getUploadDir('terrains', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'terrains')) {
                    $terrain->setPlanTopographique($fichier);
                }
            }

            $this->updateAuditFields($terrain, true);
            $repository->save($terrain, true);

            return $this->responseData($terrain, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    public function update(Request $request, Terrain $terrain, TerrainRepository $repository): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? $request->request->all();

            if (isset($data['num'])) $terrain->setNum($data['num']);
            if (isset($data['superfice'])) $terrain->setSuperfice($data['superfice']);
            if (isset($data['prix'])) $terrain->setPrix($data['prix']);
            if (isset($data['etat'])) {
                $terrain->setEtat($data['etat']);
                if ($terrain->getSite()) {
                    $terrain->getSite()->updateEtatAutomatique();
                }
            }
            if (isset($data['dimensions'])) $terrain->setDimensions($data['dimensions']);
            if (isset($data['coordonneesPolygone'])) $terrain->setCoordonneesPolygone($data['coordonneesPolygone']);

            
            if (isset($data['site_id'])) {
                $site = $this->em->getRepository(\App\Entity\Site::class)->find($data['site_id']);
                if ($site) $terrain->setSite($site);
            }

            $uploadedFile = $request->files->get('planTopographique');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('topo_'.uniqid());
                $filePath = $this->getUploadDir('terrains', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'terrains')) {
                    $terrain->setPlanTopographique($fichier);
                }
            }

            $this->updateAuditFields($terrain);
            $repository->save($terrain, true);

            return $this->responseData($terrain, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Terrain $terrain, TerrainRepository $repository): Response
    {
        try {
            $repository->remove($terrain, true);
            return $this->response(['message' => 'Terrain supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
