<?php

namespace App\Service;

use App\Entity\Agence;
use App\Entity\ContratLocation;
use App\Entity\Entreprise;
use App\Entity\FactureLocation;
use App\Entity\Maison;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Notifications de la gestion locative : chaque événement crée une ligne dans la table
 * `notification` du ou des utilisateurs concernés (affichée dans l'onglet Notifications
 * de l'application) et envoie un push si l'appareil est enregistré.
 *
 * Destinataires :
 *  - le locataire (s'il a un compte) pour ce qui touche ses factures et son contrat ;
 *  - les gestionnaires : administrateur d'entreprise (ADMIN) et admin de l'agence concernée (ADMINAG) ;
 *  - l'agent de recouvrement du site.
 * L'auteur de l'action n'est jamais notifié de sa propre action.
 * Les envois (base, push, e-mail) partent après la réponse HTTP pour ne pas ralentir l'action.
 */
class NotificationsLocation
{
    public function __construct(
        private NotificationService $notifications,
        private SendMailService $mail,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private TachesDifferees $differe,
    ) {
    }

    // ---------- Factures ----------

    public function nouvelleFacture(FactureLocation $facture): void
    {
        $montant = $this->fcfa((int) $facture->getMntFact());
        $message = $facture->getStatut() === 'paye'
            ? sprintf('Votre facture %s de %s a été réglée avec votre avance.', $facture->getLibFacture(), $montant)
            : sprintf('Votre facture %s de %s est disponible. À régler avant le %s.', $facture->getLibFacture(), $montant, $facture->getDateLimite()?->format('d/m/Y'));

        $this->auLocataire($facture->getLocataire()?->getUser(), $facture->getEntreprise(), '🧾 Nouvelle facture de loyer', $message, [
            'type' => 'facture',
            'facture_id' => $facture->getId(),
        ]);
    }

    public function paiementEncaisse(FactureLocation $facture, int $montant, ?User $auteur): void
    {
        $locataire = $facture->getLocataire();
        $solde = (int) $facture->getSoldeFactLoc();
        $entreprise = $facture->getEntreprise() ?? $facture->getContrat()?->getEntreprise();
        $data = ['type' => 'paiement', 'facture_id' => $facture->getId()];

        $this->auLocataire(
            $locataire?->getUser(),
            $entreprise,
            '✅ Paiement reçu',
            $solde > 0
                ? sprintf('Nous avons reçu %s pour %s. Reste à payer : %s.', $this->fcfa($montant), $facture->getLibFacture(), $this->fcfa($solde))
                : sprintf('Nous avons reçu %s. Votre facture %s est entièrement réglée. Merci !', $this->fcfa($montant), $facture->getLibFacture()),
            $data,
            $auteur
        );

        $this->auxGestionnaires(
            $entreprise,
            $facture->getAgence(),
            '💰 Paiement encaissé',
            sprintf(
                '%s a encaissé %s de %s (%s).',
                $auteur?->getNomPrenoms() ?? 'Un agent',
                $this->fcfa($montant),
                $locataire?->getNPrenoms() ?? 'un locataire',
                $facture->getLibFacture()
            ),
            $data,
            $auteur,
            // E-mail récapitulatif aux gestionnaires, comme auparavant
            [
                'amount' => $montant,
                'agent_name' => $auteur?->getNomPrenoms(),
                'locataire_name' => $locataire?->getNPrenoms(),
                'facture_libelle' => $facture->getLibFacture(),
                'date' => new \DateTime(),
            ]
        );
    }

    public function penaliteAppliquee(FactureLocation $facture, int $montant): void
    {
        $this->auLocataire(
            $facture->getLocataire()?->getUser(),
            $facture->getEntreprise(),
            '⏰ Pénalité de retard',
            sprintf(
                'Une pénalité de %s a été ajoutée à votre facture %s. Nouveau reste à payer : %s.',
                $this->fcfa($montant),
                $facture->getLibFacture(),
                $this->fcfa((int) $facture->getSoldeFactLoc())
            ),
            ['type' => 'penalite', 'facture_id' => $facture->getId()]
        );
    }

    // ---------- Contrats ----------

    public function contratCree(ContratLocation $contrat, ?User $auteur): void
    {
        $logement = $this->logement($contrat);
        $data = ['type' => 'contrat', 'contrat_id' => $contrat->getId()];

        $this->auLocataire(
            $contrat->getLocataire()?->getUser(),
            $contrat->getEntreprise(),
            '🏠 Bienvenue dans votre logement',
            sprintf('Votre contrat pour %s est actif. Loyer : %s par mois.', $logement, $this->fcfa((int) $contrat->getMntLoyer())),
            $data,
            $auteur
        );

        $this->auxGestionnaires(
            $contrat->getEntreprise(),
            $contrat->getAgence(),
            '📝 Nouveau contrat',
            sprintf('%s a signé pour %s.', $contrat->getLocataire()?->getNPrenoms() ?? 'Un locataire', $logement),
            $data,
            $auteur
        );

        $agent = $contrat->getAppart()?->getMaisson()?->getIdAgent();
        if ($agent && $agent !== $auteur) {
            $this->notifier($agent, $contrat->getEntreprise(), '📝 Nouveau locataire', sprintf(
                '%s emménage dans %s. Ses factures apparaîtront dans vos encaissements.',
                $contrat->getLocataire()?->getNPrenoms() ?? 'Un locataire',
                $logement
            ), $data);
        }
    }

    public function contratResilie(ContratLocation $contrat, ?User $auteur): void
    {
        $data = ['type' => 'resiliation', 'contrat_id' => $contrat->getId()];

        $this->auLocataire(
            $contrat->getLocataire()?->getUser(),
            $contrat->getEntreprise(),
            'Contrat résilié',
            sprintf('Votre contrat pour %s a été résilié. Contactez votre agence pour toute question.', $this->logement($contrat)),
            $data,
            $auteur
        );

        $this->auxGestionnaires(
            $contrat->getEntreprise(),
            $contrat->getAgence(),
            '📕 Contrat résilié',
            sprintf('Le contrat de %s (%s) a été résilié.', $contrat->getLocataire()?->getNPrenoms() ?? 'un locataire', $this->logement($contrat)),
            $data,
            $auteur
        );
    }

    // ---------- Sites ----------

    public function siteConfie(Maison $maison, ?User $auteur): void
    {
        $agent = $maison->getIdAgent();
        if (!$agent || $agent === $auteur) {
            return;
        }
        $this->notifier($agent, $maison->getAgence()?->getEntreprise(), '🏢 Nouveau site à suivre', sprintf(
            'Le site « %s » vous a été confié pour le recouvrement des loyers.',
            $maison->getLibMaison()
        ), ['type' => 'site', 'maison_id' => $maison->getId()]);
    }

    // ---------- Destinataires ----------

    private function auLocataire(?User $user, ?Entreprise $entreprise, string $titre, string $message, array $data, ?User $auteur = null): void
    {
        if ($user && $user !== $auteur) {
            $this->notifier($user, $entreprise, $titre, $message, $data);
        }
    }

    /** Administrateurs de l'entreprise + admins de l'agence concernée. */
    private function auxGestionnaires(?Entreprise $entreprise, ?Agence $agence, string $titre, string $message, array $data, ?User $auteur, ?array $email = null): void
    {
        if (!$entreprise) {
            return;
        }

        $qb = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->join('u.groupe', 'g')
            ->where('u.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);
        if ($agence) {
            $qb->andWhere("g.code = 'ADMIN' OR (g.code = 'ADMINAG' AND u.agence = :agence)")->setParameter('agence', $agence);
        } else {
            $qb->andWhere("g.code = 'ADMIN'");
        }

        foreach ($qb->getQuery()->getResult() as $gestionnaire) {
            if ($gestionnaire === $auteur) {
                continue;
            }
            $this->notifier($gestionnaire, $entreprise, $titre, $message, $data);
            if ($email !== null) {
                $this->differe->ajouter(function () use ($gestionnaire, $titre, $email) {
                    try {
                        $this->mail->send('no-reply@immoplus.com', $gestionnaire->getLogin(), $titre, 'payment_collected_agent', $email + ['admin_name' => $gestionnaire->getNomPrenoms()]);
                    } catch (\Throwable $e) {
                        $this->logger->error("E-mail « $titre » à #{$gestionnaire->getId()} impossible : {$e->getMessage()}");
                    }
                });
            }
        }
    }

    private function notifier(User $user, ?Entreprise $entreprise, string $titre, string $message, array $data): void
    {
        $entreprise ??= $user->getEntreprise();
        if (!$entreprise) {
            return;
        }
        $this->differe->ajouter(function () use ($user, $entreprise, $titre, $message, $data) {
            try {
                $this->notifications->notify($user->getId(), $entreprise, $titre, $message, $data);
            } catch (\Throwable $e) {
                // Une notification ratée ne doit jamais bloquer l'action métier
                $this->logger->error("Notification « $titre » à l'utilisateur #{$user->getId()} impossible : {$e->getMessage()}");
            }
        });
    }

    private function logement(ContratLocation $contrat): string
    {
        $appart = $contrat->getAppart();
        $maison = $appart?->getMaisson()?->getLibMaison();

        return trim(($appart?->getLibAppart() ?? 'le logement') . ($maison ? " · $maison" : ''));
    }

    private function fcfa(int $montant): string
    {
        return number_format($montant, 0, ',', ' ') . ' FCFA';
    }
}
