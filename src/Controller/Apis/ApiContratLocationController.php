<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ContratLocation;
use App\Repository\AppartementRepository;
use App\Repository\ContratLocationRepository;
use App\Repository\LocataireRepository;
use App\Repository\MotifRepository;
use App\Repository\NatureRepository;
use App\Repository\RegimeRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contrat-location')]
#[OA\Tag(name: 'ContratLocation', description: 'Gestion des contrats de location')]
class ApiContratLocationController extends ApiInterface
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
        path: "/api/contrat-location/",
        summary: "Lister les contrats",
        description: "Retourne la liste des contrats (filtrée par entreprise). Par défaut, retourne les actifs.",
        tags: ['ContratLocation']
    )]
    #[OA\Parameter(name: "etat", in: "query", description: "Filtrer par état (1=actif, 0=résilié)", schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "with_pagination", in: "query", description: "Activer la pagination (true/false, défaut: false)", schema: new OA\Schema(type: "string"))]
    public function index(Request $request, ContratLocationRepository $repository): Response
    {
        try {
            $withPagination = $request->get('with_pagination', "false");
            $etat = $request->query->get('etat', null);

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $qb = $repository->createQueryBuilder('c')
                    ->join('c.locataire', 'l')
                    ->andWhere('l.entreprise = :entreprise')
                    ->setParameter('entreprise', $this->getUser()->getEntreprise());

                if ($etat !== null && $etat !== '') {
                    $qb->andWhere('c.etat = :etat')
                        ->setParameter('etat', $etat);
                }

                $contrats = $qb->getQuery()->getResult();
            } else {
                $criteria = [];
                if ($etat !== null && $etat !== '') {
                    $criteria['etat'] = $etat;
                }
                $contrats = $repository->findBy($criteria, ['id' => 'DESC']);
            }

            if ($withPagination == "true") {
                $contrats = $this->paginationService->paginate($contrats);
            }

            return $this->responseData($contrats, 'group1', [], $withPagination == "true" ? true : false);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/contrat-location/create",
        summary: "Créer un contrat",
        description: "Crée un nouveau contrat de location.",
        tags: ['ContratLocation']
    )]
    public function create(Request $request, ContratLocationRepository $repository, AppartementRepository $appartementRepository, LocataireRepository $locataireRepository, NatureRepository $natureRepository, RegimeRepository $regimeRepository): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $contrat = new ContratLocation();

            if (isset($data['locataire_id'])) {
                $locataire = $locataireRepository->find($data['locataire_id']);
                if (!$locataire) return $this->errorResponse(null, "Locataire non trouvé", 404);
                $contrat->setLocataire($locataire);
            }

            $appartement = null;
            if (isset($data['appartement_id'])) {
                $appartement = $appartementRepository->find($data['appartement_id']);
                if ($appartement) {
                    $contrat->setAppart($appartement);
                    $contrat->setMntLoyer($appartement->getLoyer()); // Set rent from apartment
                } else {
                    return $this->errorResponse(null, "Appartement non trouvé", 404);
                }
            } else {
                return $this->errorResponse(null, "L'ID de l'appartement est requis", 400);
            }

            if (isset($data['dateDebut'])) $contrat->setDateDebut(new \DateTime($data['dateDebut']));
            if (isset($data['dateFin'])) $contrat->setDateFin(new \DateTime($data['dateFin']));
            if (isset($data['dateEntree'])) $contrat->setDateEntree(new \DateTime($data['dateEntree']));

            if (isset($data['nbMoisCaution'])) $contrat->setNbMoisCaution($data['nbMoisCaution']);
            if (isset($data['mntCaution'])) $contrat->setMntCaution($data['mntCaution']);
            if (isset($data['jourGenerationFacture'])) $contrat->setJourGenerationFacture($data['jourGenerationFacture']);

            if (isset($data['nbMoisAvance'])) $contrat->setNbMoisAvance($data['nbMoisAvance']);
            if (isset($data['mntAvance'])) $contrat->setMntAvance($data['mntAvance']);

            if (isset($data['fraisanex'])) $contrat->setFraisanex($data['fraisanex']);
            if (isset($data['reglement'])) $contrat->setReglement($data['reglement']);
            if (isset($data['isEcheance'])) $contrat->setIsEcheance(filter_var($data['isEcheance'], FILTER_VALIDATE_BOOLEAN));
            if (isset($data['nbEcheance'])) $contrat->setNbEcheance((int)$data['nbEcheance']);
            if (isset($data['mntLoyer'])) $contrat->setMntLoyer($data['mntLoyer']); // Override allowed?
            if (isset($data['nature_id'])) {
                $nature = $natureRepository->find($data['nature_id']);
                if ($nature) {
                    $contrat->setNature($nature);
                }
            }

            if (isset($data['regime_id'])) {
                $regime = $regimeRepository->find($data['regime_id']);
                if ($regime) {
                    $contrat->setRegime($regime);
                }
            }

            // Upload ScanContrat
            $uploadedFile = $request->files->get('scan_contrat');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('scan_contrat_' . uniqid());
                $filePath = $this->getUploadDir('contrats', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'contrats')) {
                    $contrat->setScanContrat($fichier);
                }
            }

            // Calculation
            $caution = $contrat->getMntCaution() ?? 0;
            $avance = $contrat->getMntAvance() ?? 0;
            $frais = $contrat->getFraisanex() ?? 0;
            $somme = $caution + $avance + $frais;
            $contrat->setTotVerse((string)$somme);

            $contrat->setEtat(1);

            if ($this->getUser() && $this->getUser()->getEntreprise()) {
                $contrat->setEntreprise($this->getUser()->getEntreprise());
            }

            $this->updateAuditFields($contrat, true);

            $repository->save($contrat, true);

            // Update Appartement status
            if ($appartement) {
                $appartement->setOqp(1);
                $appartementRepository->save($appartement, true);
            }

            return $this->responseData($contrat, 'group1');
        } catch (\Throwable $exception) {
            $this->setStatusCode(500);
            return $this->response([
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
        }
    }

    #[Route('/{id}', methods: ['POST', 'PUT'])]
    #[OA\Put(
        path: "/api/contrat-location/{id}",
        summary: "Modifier un contrat",
        description: "Met à jour un contrat existant.",
        tags: ['ContratLocation']
    )]
    public function update(Request $request, ContratLocation $contrat, ContratLocationRepository $repository, AppartementRepository $appartementRepository, NatureRepository $natureRepository, RegimeRepository $regimeRepository): Response
    {
        try {
            if (!$contrat) return $this->errorResponse(null, "Contrat non trouvé", 404);

            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $oldAppart = $contrat->getAppart();

            // Update fields...
            if (isset($data['dateDebut'])) $contrat->setDateDebut(new \DateTime($data['dateDebut']));
            if (isset($data['dateFin'])) $contrat->setDateFin(new \DateTime($data['dateFin']));
            if (isset($data['dateEntree'])) $contrat->setDateEntree(new \DateTime($data['dateEntree']));
            if (isset($data['jourGenerationFacture'])) $contrat->setJourGenerationFacture($data['jourGenerationFacture']);
            if (isset($data['nbMoisCaution'])) $contrat->setNbMoisCaution($data['nbMoisCaution']);
            if (isset($data['mntCaution'])) $contrat->setMntCaution($data['mntCaution']);
            if (isset($data['nbMoisAvance'])) $contrat->setNbMoisAvance($data['nbMoisAvance']);
            if (isset($data['mntAvance'])) $contrat->setMntAvance($data['mntAvance']);
            if (isset($data['fraisanex'])) $contrat->setFraisanex($data['fraisanex']);
            if (isset($data['reglement'])) $contrat->setReglement($data['reglement']);
            if (isset($data['isEcheance'])) $contrat->setIsEcheance(filter_var($data['isEcheance'], FILTER_VALIDATE_BOOLEAN));
            if (isset($data['nbEcheance'])) $contrat->setNbEcheance((int)$data['nbEcheance']);
            if (isset($data['mntLoyer'])) $contrat->setMntLoyer($data['mntLoyer']);
            if (isset($data['nature_id'])) {
                $nature = $natureRepository->find($data['nature_id']);
                if ($nature) {
                    $contrat->setNature($nature);
                }
            }

            if (isset($data['regime_id'])) {
                $regime = $regimeRepository->find($data['regime_id']);
                if ($regime) {
                    $contrat->setRegime($regime);
                }
            }

            $signatureAdded = false;
            if (isset($data['signatureLocataire']) && $data['signatureLocataire']) {
                $filePath = $this->getUploadDir('signatures_contrats', true);
                if ($fichier = $this->utils->sauvegardeBase64($data['signatureLocataire'], $filePath, 'locataire_' . uniqid(), 'signatures_contrats')) {
                    $contrat->setSignatureLocataire($fichier);
                    $signatureAdded = true;
                }
            }
            if (isset($data['signatureBailleur']) && $data['signatureBailleur']) {
                $filePath = $this->getUploadDir('signatures_contrats', true);
                if ($fichier = $this->utils->sauvegardeBase64($data['signatureBailleur'], $filePath, 'bailleur_' . uniqid(), 'signatures_contrats')) {
                    $contrat->setSignatureBailleur($fichier);
                    $signatureAdded = true;
                }
            }
            if ($signatureAdded && !$contrat->getDateSignature()) {
                $contrat->setDateSignature(new \DateTime());
            }

            // Recalculate Total
            $caution = $contrat->getMntCaution() ?? 0;
            $avance = $contrat->getMntAvance() ?? 0;
            $frais = $contrat->getFraisanex() ?? 0;
            $somme = $caution + $avance + $frais;
            $contrat->setTotVerse((string)$somme);

            // Upload ScanContrat
            $uploadedFile = $request->files->get('scan_contrat');
            if ($uploadedFile) {
                $filePrefix = $this->slugger->slug('scan_contrat_' . uniqid());
                $filePath = $this->getUploadDir('contrats', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, 'contrats')) {
                    $contrat->setScanContrat($fichier);
                }
            }

            // Upload FichierResiliation
            $uploadedResiliation = $request->files->get('fichier_resiliation');
            if ($uploadedResiliation) {
                $filePrefix = $this->slugger->slug('resiliation_' . uniqid());
                $filePath = $this->getUploadDir('contrats', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedResiliation, 'contrats')) {
                    $contrat->setFichierResiliation($fichier);
                }
            }

            // Handle Appartement change
            if (isset($data['appartement_id']) && $oldAppart && $oldAppart->getId() != $data['appartement_id']) {
                $newAppart = $appartementRepository->find($data['appartement_id']);
                if (!$newAppart) return $this->errorResponse(null, "Nouvel appartement non trouvé", 404);

                // Free old apartment
                $oldAppart->setOqp(0);
                $appartementRepository->save($oldAppart, true);

                // Occupy new apartment
                $newAppart->setOqp(1);
                $appartementRepository->save($newAppart, true);

                $contrat->setAppart($newAppart);
            }

            $this->updateAuditFields($contrat);

            $repository->save($contrat, true);

            return $this->responseData($contrat, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/resilier', methods: ['POST', 'PUT'])]
    #[OA\Post(
        path: "/api/contrat-location/{id}/resilier",
        summary: "Résilier un contrat",
        description: "Met fin au contrat et libère l'appartement. Permet l'upload du fichier de résiliation.",
        tags: ['ContratLocation']
    )]
    public function resilier(Request $request, ContratLocation $contrat, ContratLocationRepository $repository, AppartementRepository $appartementRepository, MotifRepository $motifRepository): Response
    {
        try {
            if (!$contrat) return $this->errorResponse(null, "Contrat non trouvé", 404);

            // Allow processing JSON or Form Data
            $data = json_decode($request->getContent(), true);
            if (null === $data) {
                $data = $request->request->all();
            }

            $contrat->setEtat(0); // 0 = Résilié

            if (isset($data['motif_id'])) {
                $motif = $motifRepository->find($data['motif_id']);
                if ($motif) {
                    $contrat->setMotif($motif);
                }
            }

            if (isset($data['dateResiliation'])) {
                $contrat->setDateFin(new \DateTime($data['dateResiliation']));
            } else {
                $contrat->setDateFin(new \DateTime());
            }

            if (isset($data['details'])) {
                $contrat->setDetails($data['details']);
            }

            // Upload FichierResiliation
            $uploadedResiliation = $request->files->get('fichier_resiliation');
            if ($uploadedResiliation) {
                $filePrefix = $this->slugger->slug('resiliation_' . uniqid());
                $filePath = $this->getUploadDir('contrats', true);
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedResiliation, 'contrats')) {
                    $contrat->setFichierResiliation($fichier);
                }
            }

            $this->updateAuditFields($contrat);
            $repository->save($contrat, true);

            $appartement = $contrat->getAppart();
            if ($appartement) {
                $appartement->setOqp(0); // Libérer l'appartement
                $appartementRepository->save($appartement, true);
            }

            return $this->response(['message' => 'Contrat résilié avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/contrat-location/{id}",
        summary: "Supprimer un contrat",
        description: "Supprime un contrat.",
        tags: ['ContratLocation']
    )]
    public function delete(ContratLocation $contrat, ContratLocationRepository $repository): Response
    {
        try {
            if (!$contrat) return $this->errorResponse(null, "Contrat non trouvé", 404);
            $repository->remove($contrat, true);
            return $this->response(['message' => 'Contrat supprimé avec succès']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/mon-contrat', methods: ['GET'])]
    #[OA\Get(
        path: "/api/contrat-location/mon-contrat",
        summary: "Mon contrat (Espace Locataire)",
        tags: ['ContratLocation']
    )]
    public function monContrat(ContratLocationRepository $repository): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getLocataire()) {
                return $this->errorResponse(null, "Profil locataire non trouvé", 404);
            }

            $contrats = $repository->findBy(['locataire' => $user->getLocataire()->getId()], ['id' => 'DESC']);
            return $this->responseData($contrats, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/mon-contrat/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/contrat-location/mon-contrat/{id}",
        summary: "Détails d'un contrat locataire",
        tags: ['ContratLocation']
    )]
    public function monContratDetails(ContratLocation $contrat): Response
    {
        try {
            $user = $this->getUser();
            if (!$user || !$user->getLocataire() || $contrat->getLocataire()->getId() !== $user->getLocataire()->getId()) {
                return $this->errorResponse(null, "Accès non autorisé", 403);
            }

            return $this->responseData($contrat, 'group1');
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }

    #[Route('/{id}/imprimer', methods: ['GET'])]
    #[OA\Get(
        path: "/api/contrat-location/{id}/imprimer",
        summary: "Imprimer le contrat",
        description: "Génère le PDF du bail d'habitation.",
        tags: ['ContratLocation']
    )]
    public function imprimer(ContratLocation $contrat, \App\Repository\EtatLieuxRepository $etatLieuxRepository): Response
    {
        try {
            if (!$contrat) return $this->errorResponse(null, "Contrat non trouvé", 404);

            $appartement = $contrat->getAppart();
            $locataire = $contrat->getLocataire();
            $proprio = null;
            if ($appartement && $appartement->getMaisson()) {
                $proprio = $appartement->getMaisson()->getProprio();
            }

            $etatLieux = $etatLieuxRepository->findOneBy(['contratLocation' => $contrat]);

            $formatter = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
            $montantLoyerLettres = strtoupper($formatter->format($contrat->getMntLoyer() ?? 0));
            $montantCautionLettres = strtoupper($formatter->format($contrat->getMntCaution() ?? 0));

            $html = $this->renderView('contrats/bail.html.twig', [
                'contrat' => $contrat,
                'appartement' => $appartement,
                'locataire' => $locataire,
                'proprio' => $proprio,
                'etatLieux' => $etatLieux,
                'montantLoyerLettres' => $montantLoyerLettres,
                'montantCautionLettres' => $montantCautionLettres
            ]);

            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $canvas = $dompdf->getCanvas();
            $font = $dompdf->getFontMetrics()->get_font("helvetica", "normal");
            $canvas->page_text($canvas->get_width()/2 - 30, $canvas->get_height() - 30, "Page {PAGE_NUM} sur {PAGE_COUNT}", $font, 9, array(0,0,0));

            $filename = "bail_habitation_" . $contrat->getId() . ".pdf";

            return new Response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->response(['message' => $exception->getMessage()]);
        }
    }
}
