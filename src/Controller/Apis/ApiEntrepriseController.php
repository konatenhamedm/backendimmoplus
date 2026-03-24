<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Abonnement;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Repository\EntrepriseRepository;
use App\Repository\PaysRepository;
use App\Entity\Employe;
use App\Entity\Groupe;
use App\Repository\GroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
                    new OA\Property(property: "sigle", type: "string"),
                    new OA\Property(property: "agrements", type: "string"),
                    new OA\Property(property: "situation_geo", type: "string"),
                    new OA\Property(property: "contacts", type: "string"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "mobile", type: "string"),
                    new OA\Property(property: "fax", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "site_web", type: "string"),
                    new OA\Property(property: "directeur", type: "string"),
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
            $entreprise->setSigle($data['sigle'] ?? '');
            $entreprise->setAgrements($data['agrements'] ?? '');
            $entreprise->setSituationGeo($data['situation_geo'] ?? '');
            $entreprise->setContacts($data['contacts'] ?? 'Non renseigné');
            $entreprise->setMobile($data['mobile'] ?? '');
            $entreprise->setEmail($data['email'] ?? '');
            $entreprise->setSiteWeb($data['site_web'] ?? '');
            $entreprise->setDirecteur($data['directeur'] ?? '');
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

    #[Route('/register', methods: ['POST'])]
    #[OA\Post(
        path: "/api/entreprise/register",
        summary: "S'inscrire (Entreprise)",
        description: "Enregistre une nouvelle entreprise (14 jours d'essai), avec accès FNE et compte admin.",
        tags: ['Entreprise']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "denomination", type: "string"),
                new OA\Property(property: "pays_id", type: "integer"),
                new OA\Property(property: "contacts", type: "string"),
                new OA\Property(property: "sigle", type: "string"),
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "fneLogin", type: "string"),
                new OA\Property(property: "fnePassword", type: "string"),
                new OA\Property(property: "admin_nom", type: "string"),
                new OA\Property(property: "admin_prenoms", type: "string"),
                new OA\Property(property: "admin_login", type: "string"),
                new OA\Property(property: "admin_password", type: "string")
            ]
        )
    )]
    public function register(
        Request $request, 
       EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        PaysRepository $paysRepo,
        GroupeRepository $groupeRepo,
        \App\Repository\CiviliteRepository $civiliteRepo
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['denomination']) || !isset($data['admin_login']) || !isset($data['admin_password']) || !isset($data['pays_id'])) {
                return $this->errorResponse(null, "Données manquantes (denomination, pays_id, admin_login, admin_password requis)", 400);
            }

            $pays = $paysRepo->find($data['pays_id']);
            if (!$pays) {
                return $this->errorResponse(null, "Pays introuvable", 404);
            }

            // --- CREATION DE L'ENTREPRISE ---
            $entreprise = new Entreprise();
            $entreprise->setDenomination($data['denomination']);
            $entreprise->setCode('ENT-' . strtoupper(substr(uniqid(), -6)));
            $entreprise->setPays($pays);
            
            if (isset($data['contacts'])) $entreprise->setContacts($data['contacts']);
            if (isset($data['sigle'])) $entreprise->setSigle($data['sigle']);
            if (isset($data['email'])) $entreprise->setEmail($data['email']);
            
            // FNE
            if (isset($data['fneLogin'])) $entreprise->setFneLogin($data['fneLogin']);
            if (isset($data['fnePassword'])) $entreprise->setFnePassword($data['fnePassword']);

            // Abonnement essai 14 jours (sauvegarde dans l'entité Entreprise par rétrocompatibilité)
            $entreprise->setAbonnement('ESSAI');
            $dateFin = new \DateTime();
            $dateFin->modify('+14 days');
            $entreprise->setDateFinAbonnement($dateFin);
            $entreprise->setIsActive(true);
            $entreprise->setDateCreation(new \DateTime());

            $em->persist($entreprise);

            // --- NOUVEAU SYSTEME D'ABONNEMENT ---
            $abonnement = new Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setType('ESSAI');
            $abonnement->setEtat('ACTIF');
            $abonnement->setDateFin($dateFin);
            $em->persist($abonnement);

            // --- RECHERCHE / CREATION DU GROUPE ---
            $groupe = $groupeRepo->findOneBy(['code' => 'ADMIN']);
            if (!$groupe) {
                $groupe = new Groupe();
                $groupe->setCode('ADMIN');
                $groupe->setName('Administrateurs');
                // The global logic holds if missing
                $em->persist($groupe);
            }
            
            // --- RECHERCHE CIVILITE PAR DEFAUT ---
            $civilite = $civiliteRepo->findOneBy([]);
            if (!$civilite) {
                $civilite = new \App\Entity\Civilite();
                $civilite->setCode('M.');
                $civilite->setLibelle('Monsieur');
                $em->persist($civilite);
            }

            // --- CREATION DE L'EMPLOYE ---
            $employe = new Employe();
            $employe->setNom($data['admin_nom'] ?? 'Admin');
            $employe->setPrenom($data['admin_prenoms'] ?? '');
            $employe->setEntreprise($entreprise);
            $employe->setFonction('Super Administrateur');
            $employe->setCivilite($civilite);
            $employe->setContact($data['contacts'] ?? 'Non renseigné');
            $employe->setAdresseMail($data['email'] ?? 'admin@entreprise.com');
            $employe->setNumPiece('Non défini');
            $employe->setResidence('Non défini');
            $employe->setMatricule('MAT-'.strtoupper(substr(uniqid(), -6)));
            
            $em->persist($employe);

            // --- CREATION USER ADMIN DE L'ENTREPRISE ---
            $user = new User();
            $user->setLogin($data['admin_login']);
            $hashedPassword = $hasher->hashPassword($user, $data['admin_password']);
            $user->setPassword($hashedPassword);
            $user->setNom($data['admin_nom'] ?? 'Admin');
            $user->setPrenoms($data['admin_prenoms'] ?? '');
            $user->setRoles(['ROLE_ADMIN']);
            $user->setEntreprise($entreprise);
            $user->setEmploye($employe);
            $user->setGroupe($groupe);
            $user->setIsActive(true);

            $em->persist($user);
            $em->flush();

            return $this->responseData([
                'entreprise' => [
                    'id' => $entreprise->getId(),
                    'denomination' => $entreprise->getDenomination(),
                    'dateFinAbonnement' => $entreprise->getDateFinAbonnement()->format('Y-m-d H:i:s')
                ],
                'admin_user' => [
                    'id' => $user->getId(),
                    'login' => $user->getLogin()
                ]
            ], 'group1', ['message' => 'Inscription réussie. Vous avez 14 jours d\'essai.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/renew-subscription', methods: ['POST'])]
    #[OA\Post(
        path: "/api/entreprise/{id}/renew-subscription",
        summary: "Renouveler l'abonnement de l'entreprise",
        description: "Renouvelle l'abonnement pour une période en utilisant un module ou une durée (1_MOIS, 6_MOIS, 1_AN).",
        tags: ['Entreprise']
    )]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "module_abonnement_id", type: "integer", description: "L'ID du module d'abonnement (optionnel)"),
                new OA\Property(property: "duree", type: "string", description: "1_MOIS, 6_MOIS, 1_AN (utilisé si aucun module)")
            ]
        )
    )]
    public function renewSubscription(Request $request, Entreprise $entreprise, EntityManagerInterface $em, \App\Repository\ModuleAbonnementRepository $moduleRepo): Response
    {
        try {
            if (!$entreprise) return $this->errorResponse(null, "Entreprise non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            $moduleId = $data['module_abonnement_id'] ?? null;
            $module = $moduleId ? $moduleRepo->find($moduleId) : null;

            $currentDateFin = $entreprise->getDateFinAbonnement();
            if (!$currentDateFin || $currentDateFin < new \DateTime()) {
                $currentDateFin = new \DateTime(); // Repart d'aujourd'hui si expiré
            } else {
                $currentDateFin = \DateTime::createFromInterface($currentDateFin);
            }

            $abonnementMode = '';
            $abonnement = new \App\Entity\Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setType('RENOUVELLEMENT');
            $abonnement->setEtat('ACTIF');

            // Nouveau système : basé sur le module d'abonnement s'il est fourni
            if ($module) {
                $dureeJours = ((int) $module->getDuree()) > 0 ? (int) $module->getDuree() : 30;
                $currentDateFin->modify("+{$dureeJours} days");
                $abonnementMode = $module->getCode() ?? 'MODULE';
                $abonnement->setModuleAbonnement($module);
            } else {
                // Ancien système par défaut (si aucun module fourni)
                $duree = $data['duree'] ?? '1_MOIS';
                switch ($duree) {
                    case '1_MOIS':
                        $currentDateFin->modify('+1 month');
                        $abonnementMode = 'MENSUEL';
                        break;
                    case '6_MOIS':
                        $currentDateFin->modify('+6 months');
                        $abonnementMode = 'SEMESTRIEL';
                        break;
                    case '1_AN':
                        $currentDateFin->modify('+1 year');
                        $abonnementMode = 'ANNUEL';
                        break;
                    default:
                        return $this->errorResponse(null, "Durée invalide. Formats: 1_MOIS, 6_MOIS, ou 1_AN.", 400);
                }
            }

            $entreprise->setDateFinAbonnement($currentDateFin);
            $entreprise->setAbonnement($abonnementMode);
            
            $abonnement->setDateFin(clone $currentDateFin);
            $em->persist($abonnement);
            $em->persist($entreprise);
            $em->flush();

            return $this->responseData([
                'message' => 'Abonnement renouvelé avec succès.',
                'nouvelleDateFin' => $entreprise->getDateFinAbonnement()->format('Y-m-d H:i:s'),
                'typeAbonnement' => $entreprise->getAbonnement()
            ], 'group1');

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
                    new OA\Property(property: "sigle", type: "string"),
                    new OA\Property(property: "agrements", type: "string"),
                    new OA\Property(property: "situation_geo", type: "string"),
                    new OA\Property(property: "contacts", type: "string"),
                    new OA\Property(property: "adresse", type: "string"),
                    new OA\Property(property: "mobile", type: "string"),
                    new OA\Property(property: "fax", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "site_web", type: "string"),
                    new OA\Property(property: "directeur", type: "string"),
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
            if (isset($data['sigle'])) $entreprise->setSigle($data['sigle']);
            if (isset($data['agrements'])) $entreprise->setAgrements($data['agrements']);
            if (isset($data['situation_geo'])) $entreprise->setSituationGeo($data['situation_geo']);
            if (isset($data['contacts'])) $entreprise->setContacts($data['contacts']);
            if (isset($data['adresse'])) $entreprise->setAdresse($data['adresse']);
            if (isset($data['mobile'])) $entreprise->setMobile($data['mobile']);
            if (isset($data['fax'])) $entreprise->setFax($data['fax']);
            if (isset($data['email'])) $entreprise->setEmail($data['email']);
            if (isset($data['site_web'])) $entreprise->setSiteWeb($data['site_web']);
            if (isset($data['directeur'])) $entreprise->setDirecteur($data['directeur']);
            if (isset($data['ville'])) $entreprise->setVille($data['ville']);
            if (isset($data['numero'])) $entreprise->setNumero($data['numero']);
            if (isset($data['fneLogin'])) $entreprise->setFneLogin($data['fneLogin']);
            if (isset($data['fnePassword'])) $entreprise->setFnePassword($data['fnePassword']);
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
