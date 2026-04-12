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
    protected function getUser(): ?User
    {
        return parent::getUser();
    }

    // Parent sendMailService used instead of local property


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
                new OA\Property(property: "admin_password", type: "string"),
                new OA\Property(property: "module_abonnement_id", type: "integer", nullable: true)
            ]
        )
    )]
    public function register(
        Request $request, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        PaysRepository $paysRepo,
        GroupeRepository $groupeRepo,
        \App\Repository\CiviliteRepository $civiliteRepo,
        \App\Repository\ModuleAbonnementRepository $moduleAbonnementRepo,
        \App\Service\MenuGeneratorService $menuService,
        \App\Service\EntrepriseRegistrationService $registrationService
    ): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['denomination']) || !isset($data['admin_login']) || !isset($data['admin_password']) || !isset($data['pays_id'])) {
                return $this->errorResponse(null, "Données manquantes (denomination, pays_id, admin_login, admin_password requis)", 400);
            }

            $entreprise = $registrationService->processRegistration($data);

            return $this->responseData($entreprise, 'group1', ['message' => 'Inscription réussie.']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/initiate-payment', methods: ['POST'])]
    public function initiateRegistrationPayment(
        Request $request,
        EntityManagerInterface $em,
        \App\Repository\ModuleAbonnementRepository $moduleAbonnementRepo,
        \App\Service\PaiementService $paiementService
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            // On vérifie si l'utilisateur existe déjà avant de lancer le paiement
            if (isset($data['admin_login'])) {
                $existingUser = $em->getRepository(User::class)->findOneBy(['login' => $data['admin_login']]);
                if ($existingUser) {
                    return $this->errorResponse(null, "Le compte utilisateur (login: " . $data['admin_login'] . ") existe déjà. Veuillez en choisir un autre pour votre inscription.", 400);
                }
            }
            
            if (!isset($data['module_abonnement_id'])) {
                return $this->errorResponse(null, "Module abonnement requis pour le paiement", 400);
            }

            $module = $moduleAbonnementRepo->find($data['module_abonnement_id']);
            if (!$module) {
                return $this->errorResponse(null, "Module introuvable", 404);
            }

            // On initie le paiement en stockant les données d'inscription dans la transaction
            $result = $paiementService->traiterPaiementInscription($data, $module, $data);

            if ($result['code'] !== 200) {
                return $this->json($result, 400);
            }

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->errorResponse(null, $e->getMessage(), 500);
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

            // 1. Désactiver les abonnements actifs précédents
            $activeAbonnements = $em->getRepository(Abonnement::class)->findBy([
                'entreprise' => $entreprise,
                'etat' => 'ACTIF'
            ]);
            foreach ($activeAbonnements as $oldAb) {
                $oldAb->setEtat('EXPIRE');
                $em->persist($oldAb);
            }

            $abonnementMode = '';
            $abonnement = new Abonnement();
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

    #[Route('/{id}/admin-renew-subscription', methods: ['POST'])]
    #[OA\Post(
        path: "/api/entreprise/{id}/admin-renew-subscription",
        summary: "Mise à jour administrative de l'abonnement",
        description: "Permet à l'administrateur de mettre à jour l'abonnement d'une entreprise manuellement et d'envoyer un email de notification.",
        tags: ['Entreprise']
    )]
    public function adminRenewSubscription(
        Request $request, 
        Entreprise $entreprise, 
        EntityManagerInterface $em, 
        \App\Repository\ModuleAbonnementRepository $moduleRepo
    ): Response
    {
        try {
            if (!$entreprise) return $this->errorResponse(null, "Entreprise non trouvée", 404);

            $data = json_decode($request->getContent(), true);
            $moduleId = $data['module_abonnement_id'] ?? null;
            $module = $moduleId ? $moduleRepo->find($moduleId) : null;

            if (!$module) {
                return $this->errorResponse(null, "Un module d'abonnement valide est requis pour cette opération administrative.", 400);
            }

            // 1. Désactiver les abonnements actifs précédents
            $activeAbonnements = $em->getRepository(Abonnement::class)->findBy([
                'entreprise' => $entreprise,
                'etat' => 'ACTIF'
            ]);
            foreach ($activeAbonnements as $oldAb) {
                $oldAb->setEtat('EXPIRE');
                $em->persist($oldAb);
            }

            // Calculer la nouvelle date de fin (repart de la date actuelle ou prolonge)
            $newDateFin = new \DateTime();
            $dureeJours = ((int) $module->getDuree()) > 0 ? (int) $module->getDuree() : 30;
            $newDateFin->modify("+{$dureeJours} days");

            $abonnement = new Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setType('MISE_A_JOUR_ADMIN');
            $abonnement->setEtat('ACTIF');
            $abonnement->setModuleAbonnement($module);
            $abonnement->setDateFin(clone $newDateFin);

            $entreprise->setDateFinAbonnement($newDateFin);
            $entreprise->setAbonnement($module->getCode() ?? 'EQUIPEE');
            
            $em->persist($abonnement);
            $em->persist($entreprise);
            $em->flush();

            // --- ENVOI DE L'EMAIL DE NOTIFICATION ---
            try {
                $recipientEmail = $entreprise->getEmail();
                if (!$recipientEmail) {
                    // Fallback sur le premier administrateur de l'entreprise
                    $admin = $em->getRepository(User::class)->findOneBy(['entreprise' => $entreprise]);
                    $recipientEmail = $admin ? $admin->getLogin() : null;
                }

                if ($recipientEmail) {
                    $this->sendMailService->send(
                        'contact@motiplus.pro',
                        $recipientEmail,
                        "🚀 Votre abonnement Motiplus a été mis à jour !",
                        'subscription_updated',
                        [
                            'entreprise' => $entreprise,
                            'module' => $module,
                            'expiration_date' => $newDateFin
                        ]
                    );
                }
            } catch (\Exception $e) {
                error_log("Erreur envoi email mise à jour abonnement: " . $e->getMessage());
            }

            return $this->responseData([
                'message' => 'L\'abonnement de l\'entreprise a été mis à jour et l\'email de notification a été envoyé.',
                'nouvelleDateFin' => $entreprise->getDateFinAbonnement()->format('d/m/Y'),
                'formule' => $entreprise->getAbonnement()
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
