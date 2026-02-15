<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Proprio;
use App\Repository\EntrepriseRepository;
use App\Repository\ProprioRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/proprio')]
#[OA\Tag(name: 'Proprio', description: 'Gestion des propriétaires')]
class ApiProprioController extends ApiInterface
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
        path: "/api/proprio/",
        summary: "Lister les propriétaires",
        description: "Retourne la liste des propriétaires.",
        tags: ['Proprio']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, ProprioRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $proprios = $repository->findBy(['entreprise' => $this->getUser()->getEntreprise()], ['id' => 'DESC']);
            } else {
                $proprios = $repository->findBy([], ['id' => 'DESC']);
            }

            if ($withPagination == "true") {
                $proprios = $this->paginationService->paginate($proprios);
            }

            return $this->responseData($proprios, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/proprio/create",
        summary: "Créer un propriétaire",
        description: "Ajoute un nouveau propriétaire.",
        tags: ['Proprio']
    )]
    public function create(Request $request, ProprioRepository $repository, EntrepriseRepository $entrepriseRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $proprio = new Proprio();
            
            // Champs obligatoires selon l'entité
            if (isset($data['nom'])) $proprio->setNom($data['nom']);
            if (isset($data['prenoms'])) $proprio->setPrenoms($data['prenoms']);
            if (isset($data['contacts'])) $proprio->setContacts($data['contacts']);
            if (isset($data['addresse'])) $proprio->setAddresse($data['addresse']);
            if (isset($data['numCni'])) $proprio->setNumCni($data['numCni']);
            if (isset($data['lieuNaiss'])) $proprio->setLieuNaiss($data['lieuNaiss']);
            if (isset($data['prefession'])) $proprio->setPrefession($data['prefession']);
            
            if (isset($data['dateNaiss'])) $proprio->setDateNaiss(new \DateTime($data['dateNaiss']));
            if (isset($data['dateCni'])) $proprio->setDateCni(new \DateTime($data['dateCni']));

            // Champs optionnels
            if (isset($data['email'])) $proprio->setEmail($data['email']);
            if (isset($data['nomPere'])) $proprio->setNomPere($data['nomPere']);
            if (isset($data['nomMere'])) $proprio->setNomMere($data['nomMere']);
            if (isset($data['whatsApp'])) $proprio->setWhatsApp($data['whatsApp']);
            
            // Représentant Légal
            if (isset($data['nomPrenomsR'])) $proprio->setNomPrenomsR($data['nomPrenomsR']);
            if (isset($data['contactsR'])) $proprio->setContactsR($data['contactsR']);
            if (isset($data['emailR'])) $proprio->setEmailR($data['emailR']);
            if (isset($data['adresseR'])) $proprio->setAdresseR($data['adresseR']);
            if (isset($data['nomPrereR'])) $proprio->setNomPrereR($data['nomPrereR']);
            if (isset($data['nomMereR'])) $proprio->setNomMereR($data['nomMereR']);
            if (isset($data['whatsAppR'])) $proprio->setWhatsAppR($data['whatsAppR']);
            if (isset($data['lieuNaissR'])) $proprio->setLieuNaissR($data['lieuNaissR']);
            if (isset($data['professionR'])) $proprio->setProfessionR($data['professionR']);
            if (isset($data['numCniR'])) $proprio->setNumCniR($data['numCniR']);
            if (isset($data['dateNaissR'])) $proprio->setDateNaissR(new \DateTime($data['dateNaissR']));
            if (isset($data['dateCniR'])) $proprio->setDateCniR(new \DateTime($data['dateCniR']));

            // Upload Cni
            $uploadedCni = $request->files->get('cni');
            if ($uploadedCni) {
                $filePrefix = $this->slugger->slug('cni_'.uniqid());
                $filePath = $this->getUploadDir('proprios', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedCni, 'proprios')) {
                    $proprio->setCni($fichier);
                }
            }

            // Upload Lien
            $uploadedLien = $request->files->get('lien');
            if ($uploadedLien) {
                $filePrefix = $this->slugger->slug('lien_'.uniqid());
                $filePath = $this->getUploadDir('proprios', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedLien, 'proprios')) {
                    $proprio->setLien($fichier);
                }
            }


            if (isset($data['entreprise_id'])) {
                $entreprise = $entrepriseRepository->find($data['entreprise_id']);
                if ($entreprise) $proprio->setEntreprise($entreprise);
            } elseif ($this->getUser() && $this->getUser()->getEntreprise()) {
                $proprio->setEntreprise($this->getUser()->getEntreprise());
            }

            $this->updateAuditFields($proprio, true);

            $repository->save($proprio, true);

            return $this->responseData($proprio, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    #[OA\Put(
        path: "/api/proprio/{id}",
        summary: "Modifier un propriétaire",
        description: "Met à jour un propriétaire existant.",
        tags: ['Proprio']
    )]
    public function update(Request $request, Proprio $proprio, ProprioRepository $repository, EntrepriseRepository $entrepriseRepository): Response
    {
        try {
            if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }
            
             // Mise à jour des champs si présents
            if (isset($data['nom'])) $proprio->setNom($data['nom']);
            if (isset($data['prenoms'])) $proprio->setPrenoms($data['prenoms']);
            if (isset($data['contacts'])) $proprio->setContacts($data['contacts']);
            if (isset($data['addresse'])) $proprio->setAddresse($data['addresse']);
            if (isset($data['numCni'])) $proprio->setNumCni($data['numCni']);
            if (isset($data['lieuNaiss'])) $proprio->setLieuNaiss($data['lieuNaiss']);
            if (isset($data['prefession'])) $proprio->setPrefession($data['prefession']);
            
            if (isset($data['dateNaiss'])) $proprio->setDateNaiss(new \DateTime($data['dateNaiss']));
            if (isset($data['dateCni'])) $proprio->setDateCni(new \DateTime($data['dateCni']));

            if (isset($data['email'])) $proprio->setEmail($data['email']);
            if (isset($data['nomPere'])) $proprio->setNomPere($data['nomPere']);
            if (isset($data['nomMere'])) $proprio->setNomMere($data['nomMere']);
            if (isset($data['whatsApp'])) $proprio->setWhatsApp($data['whatsApp']);

            // Représentant Légal
            if (isset($data['nomPrenomsR'])) $proprio->setNomPrenomsR($data['nomPrenomsR']);
            if (isset($data['contactsR'])) $proprio->setContactsR($data['contactsR']);
            if (isset($data['emailR'])) $proprio->setEmailR($data['emailR']);
            if (isset($data['adresseR'])) $proprio->setAdresseR($data['adresseR']);
            if (isset($data['nomPrereR'])) $proprio->setNomPrereR($data['nomPrereR']);
            if (isset($data['nomMereR'])) $proprio->setNomMereR($data['nomMereR']);
            if (isset($data['whatsAppR'])) $proprio->setWhatsAppR($data['whatsAppR']);
            if (isset($data['lieuNaissR'])) $proprio->setLieuNaissR($data['lieuNaissR']);
            if (isset($data['professionR'])) $proprio->setProfessionR($data['professionR']);
            if (isset($data['numCniR'])) $proprio->setNumCniR($data['numCniR']);
            if (isset($data['dateNaissR'])) $proprio->setDateNaissR(new \DateTime($data['dateNaissR']));
            if (isset($data['dateCniR'])) $proprio->setDateCniR(new \DateTime($data['dateCniR']));
            
             // Upload Cni
            $uploadedCni = $request->files->get('cni');
            if ($uploadedCni) {
                $filePrefix = $this->slugger->slug('cni_'.uniqid());
                $filePath = $this->getUploadDir('proprios', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedCni, 'proprios')) {
                    $proprio->setCni($fichier);
                }
            }

            // Upload Lien
            $uploadedLien = $request->files->get('lien');
            if ($uploadedLien) {
                $filePrefix = $this->slugger->slug('lien_'.uniqid());
                $filePath = $this->getUploadDir('proprios', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedLien, 'proprios')) {
                    $proprio->setLien($fichier);
                }
            }

            $this->updateAuditFields($proprio);

            $repository->save($proprio, true);

            return $this->responseData($proprio, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/proprio/{id}",
        summary: "Supprimer un propriétaire",
        description: "Supprime un propriétaire.",
        tags: ['Proprio']
    )]
    public function delete(Proprio $proprio, ProprioRepository $repository): Response
    {
        try {
            if (!$proprio) return $this->errorResponse(null, "Propriétaire non trouvé", 404);
            $repository->remove($proprio, true);
            return $this->response(['message' => 'Propriétaire supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
