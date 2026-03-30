<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\User;
use App\Repository\EmployeRepository;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/user')]
#[OA\Tag(name: 'User', description: 'Gestion des utilisateurs')]
class ApiUserController extends ApiInterface
{
    /**
     * @return User|null
     */
    protected function getUser(): ?User
    {
        return parent::getUser();
    }

    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/user/",
        summary: "Lister les utilisateurs de l'entreprise",
        description: "Retourne la liste des utilisateurs de l'entreprise connectée.",
        tags: ['User']
    )]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, UserRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $role = $request->get('role');
            $user = $this->getUser();
            if (!$user) return $this->errorResponse(null, "Non authentifié", 401);

            if ($role) {
                $users = $repository->findByRole($role, $user->getEntreprise());
            } else {
                $criteria = [];
                $criteria['entreprise'] = $user->getEntreprise();
                $users = $repository->findBy($criteria, ['id' => 'DESC']);
            }

            if ($withPagination == "true") {
                $users = $this->paginationService->paginate($users);
            }

            return $this->responseData($users, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/user/create",
        summary: "Créer un utilisateur",
        description: "Ajoute un nouvel utilisateur.",
        tags: ['User']
    )]
    public function create(Request $request, EmployeRepository $employeRepository, UserRepository $repository, UserPasswordHasherInterface $hasher, \App\Repository\GroupeRepository $groupeRepository, \App\Repository\LocataireRepository $locataireRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $user = new User();

            if (isset($data['login'])) $user->setLogin($data['login']);
            if (isset($data['nom'])) $user->setNom($data['nom']);
            if (isset($data['prenoms'])) $user->setPrenoms($data['prenoms']);
            if (isset($data['password'])) {
                $user->setPassword($hasher->hashPassword($user, $data['password']));
            }

            if (isset($data['employe_id'])) {
                $employe = $employeRepository->find($data['employe_id']);
                if (!$employe) return $this->errorResponse(null, "Employe non trouvé", 404);
                $user->setEmploye($employe);
            }


            if (isset($data['groupe_id'])) {
                $groupe = $groupeRepository->find($data['groupe_id']);
                if (!$groupe) return $this->errorResponse(null, "Groupe non trouvé", 404);
                $user->setGroupe($groupe);

                if ($groupe->getCode() === 'ADMIN') {
                    $user->setRoles(['ROLE_ADMIN']);
                }
                if ($groupe->getCode() === 'PROPRIETAIRE') {
                    $user->setRoles(['ROLE_PROPRIETAIRE']);
                }

                if ($groupe->getCode() === 'AGENT') {
                    $user->setRoles(['ROLE_AGENT']);
                }
                if ($groupe->getCode() === 'AGENTADMINAG') {
                    $user->setRoles(['ROLE_AGENTADMINAG']);
                }
                if ($groupe->getCode() === 'SADM') {
                    $user->setRoles(['ROLE_SADM']);
                }
                if ($groupe->getCode() === 'COMPTABLE') {
                    $user->setRoles(['ROLE_COMPTABLE']);
                }
                if ($groupe->getCode() === 'LOCATAIRE') {
                    $user->setRoles(['ROLE_LOCATAIRE']);
                }
            }

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $user->setLocataire($locataire);
                // $user->setRoles(['ROLE_LOCATAIRE']);
            }

            // Upload logo (avatar)
            $uploadedFile = $request->files->get('logo');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('avatar_' . uniqid());
                $filePath = $this->getUploadDir('avatars', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'avatars')) {
                    $user->setLogo($fichier);
                }
            }

            // Link to current enterprise
            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $user->setEntreprise($this->getUser()->getEntreprise());
            }

            $repository->save($user, true);

            return $this->responseData($user, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    #[OA\Put(
        path: "/api/user/{id}",
        summary: "Modifier un utilisateur",
        description: "Met à jour un utilisateur existant.",
        tags: ['User']
    )]
    public function update(Request $request, EmployeRepository $employeRepository, User $user, UserRepository $repository, UserPasswordHasherInterface $hasher, \App\Repository\GroupeRepository $groupeRepository, \App\Repository\LocataireRepository $locataireRepository): Response
    {
        try {
            if (!$user) return $this->errorResponse(null, "Utilisateur non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }


            if (isset($data['login'])) $user->setLogin($data['login']);
            if (isset($data['nom'])) $user->setNom($data['nom']);
            if (isset($data['prenoms'])) $user->setPrenoms($data['prenoms']);

            if (isset($data['employe_id'])) {
                $employe = $employeRepository->find($data['employe_id']);
                if (!$employe) return $this->errorResponse(null, "Employe non trouvé", 404);
                $user->setEmploye($employe);
            }


            if (isset($data['groupe_id'])) {
                $groupe = $groupeRepository->find($data['groupe_id']);
                if (!$groupe) return $this->errorResponse(null, "Groupe non trouvé", 404);
                $user->setGroupe($groupe);

                if ($groupe->getCode() === 'ADMIN') {
                    $user->setRoles(['ROLE_ADMIN']);
                }
                if ($groupe->getCode() === 'PROPRIETAIRE') {
                    $user->setRoles(['ROLE_PROPRIETAIRE']);
                }

                if ($groupe->getCode() === 'AGENT') {
                    $user->setRoles(['ROLE_AGENT']);
                }
                if ($groupe->getCode() === 'AGENTADMINAG') {
                    $user->setRoles(['ROLE_AGENTADMINAG']);
                }
                if ($groupe->getCode() === 'SADM') {
                    $user->setRoles(['ROLE_SADM']);
                }
                if ($groupe->getCode() === 'COMPTABLE') {
                    $user->setRoles(['ROLE_COMPTABLE']);
                }
                if ($groupe->getCode() === 'LOCATAIRE') {
                    $user->setRoles(['ROLE_LOCATAIRE']);
                }
            }

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $user->setLocataire($locataire);
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $user->setPassword($hasher->hashPassword($user, $data['password']));
            }

            // Upload logo (avatar)
            $uploadedFile = $request->files->get('logo');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('avatar_' . uniqid());
                $filePath = $this->getUploadDir('avatars', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'avatars')) {
                    $user->setLogo($fichier);
                }
            }

            $repository->save($user, true);

            return $this->responseData($user, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/user/{id}",
        summary: "Supprimer un utilisateur",
        description: "Supprime un utilisateur.",
        tags: ['User']
    )]
    public function delete(User $user, UserRepository $repository): Response
    {
        try {
            if (!$user) return $this->errorResponse(null, "Utilisateur non trouvé", 404);
            $repository->remove($user, true);
            return $this->response(['message' => 'Utilisateur supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
