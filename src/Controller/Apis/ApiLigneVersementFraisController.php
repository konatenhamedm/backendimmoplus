<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\LigneVersementFrais;
use App\Entity\CompteCltT;
use App\Repository\LigneVersementFraisRepository;
use App\Repository\CompteCltTRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/ligne-versement-frais')]
#[OA\Tag(name: 'LigneVersementFrais', description: 'Gestion des paiements de frais (terrains)')]
class ApiLigneVersementFraisController extends ApiInterface
{
    #[Route('/compte/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/ligne-versement-frais/compte/{id}",
        summary: "Lister les versements pour un compte spécifique",
        tags: ['LigneVersementFrais']
    )]
    public function getByCompte(LigneVersementFraisRepository $repository, int $id): Response
    {
        try {
            $versements = $repository->findBy(['comptecltT' => $id]);
            return $this->responseData($versements);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/ligne-versement-frais/create",
        summary: "Créer un versement de frais",
        description: "Enregistre un paiement et met à jour le solde du compte client.",
        tags: ['LigneVersementFrais']
    )]
    public function create(Request $request, LigneVersementFraisRepository $repository, CompteCltTRepository $compteRepository, EntityManagerInterface $em): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $versement = new LigneVersementFrais();

            if (!isset($data['compte_id']) || !isset($data['montant'])) {
                return $this->errorResponse(null, "Compte ID et montant sont requis", 400);
            }

            $compte = $compteRepository->find($data['compte_id']);
            if (!$compte) {
                return $this->errorResponse(null, "Compte non trouvé", 404);
            }

            $montantVerse = (float)$data['montant'];
            $soldeActuel = (float)$compte->getSolde();

            if ($montantVerse > $soldeActuel) {
                return $this->errorResponse(null, "Le montant versé dépasse le solde restant ($soldeActuel)", 400);
            }

            $versement->setComptecltT($compte);
            $versement->setMontantverse((string)$montantVerse);
            $versement->setDateversementfrais(isset($data['date']) ? new \DateTime($data['date']) : new \DateTime());

            // Update Account Balance
            $nouveauSolde = $soldeActuel - $montantVerse;
            $compte->setSolde((string)$nouveauSolde);

            $em->persist($versement);
            $em->persist($compte);
            $em->flush();

            return $this->responseData($versement);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/ligne-versement-frais/{id}",
        summary: "Supprimer un versement",
        description: "Supprime le versement et rajoute le montant au solde du compte.",
        tags: ['LigneVersementFrais']
    )]
    public function delete(LigneVersementFrais $versement, LigneVersementFraisRepository $repository, EntityManagerInterface $em): Response
    {
        try {
            if (!$versement) return $this->errorResponse(null, "Versement non trouvé", 404);

            $compte = $versement->getComptecltT();
            if ($compte) {
                $montantVerse = (float)$versement->getMontantverse();
                $soldeActuel = (float)$compte->getSolde();
                $compte->setSolde((string)($soldeActuel + $montantVerse));
                $em->persist($compte);
            }

            $repository->remove($versement, true);
            $em->flush();

            return $this->response(['message' => 'Versement supprimé et solde mis à jour']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
