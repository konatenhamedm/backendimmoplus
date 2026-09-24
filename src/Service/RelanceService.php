<?php

namespace App\Service;

use App\Entity\Agence;
use App\Entity\FactureLocation;
use App\Entity\ModeleRelance;
use App\Entity\ParametreRelance;
use App\Entity\Relance;
use App\Entity\User;
use App\Repository\ModeleRelanceRepository;
use App\Repository\ParametreRelanceRepository;
use App\Service\Sms\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Rappels (avant échéance) et relances (après échéance) des loyers impayés,
 * selon les paramètres (mode, canaux, délais) et les modèles de messages de chaque agence.
 */
class RelanceService
{
    public const ETAPE_RAPPEL = 'RAPPEL';
    public const ORIGINE_AUTO = 'AUTOMATIQUE';
    public const ORIGINE_MANUEL = 'MANUEL';

    /** Au-delà de ce nombre de jours après le dernier palier, une facture n'est plus relancée automatiquement. */
    private const FENETRE_RATTRAPAGE = 30;

    private const STATUTS_PAYES = ['payer', 'paye', 'solde'];

    public const VARIABLES = [
        '{locataire}' => 'Nom complet du locataire',
        '{nom}' => 'Nom du locataire',
        '{prenoms}' => 'Prénoms du locataire',
        '{facture}' => 'Libellé de la facture',
        '{mois}' => 'Mois facturé',
        '{montant}' => 'Montant restant à payer',
        '{montant_facture}' => 'Montant total de la facture',
        '{date_limite}' => 'Date limite de paiement',
        '{jours_restants}' => "Jours avant l'échéance (rappel)",
        '{jours_retard}' => 'Jours de retard (relance)',
        '{logement}' => 'Logement loué',
        '{agence}' => "Nom de l'agence",
        '{agence_contact}' => "Contact de l'agence",
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private ParametreRelanceRepository $parametreRepository,
        private ModeleRelanceRepository $modeleRepository,
        private SendMailService $mailService,
        private NotificationService $notificationService,
        private SmsService $smsService,
        private LoggerInterface $logger,
    ) {
    }

    /** @var array<int, bool> offre SMS active, par entreprise (évite une requête par facture) */
    private array $smsActif = [];

    /** Agence demandée (si elle appartient à l'entreprise de l'utilisateur), sinon l'agence de l'utilisateur. */
    public function resolveAgence(?User $user, mixed $agenceId): ?Agence
    {
        if (!$user || !$user->getEntreprise()) {
            return null;
        }

        if ($agenceId && $agenceId !== 'all' && $agenceId !== 'null') {
            $agence = $this->em->getRepository(Agence::class)->find((int) $agenceId);
            return $agence && $agence->getEntreprise() === $user->getEntreprise() ? $agence : null;
        }

        return $user->getAgence();
    }

    /** Paramètres de l'agence ; valeurs par défaut (non enregistrées) si l'agence n'a encore rien configuré. */
    public function getParametres(Agence $agence): ParametreRelance
    {
        return $this->parametreRepository->findOneBy(['agence' => $agence])
            ?? (new ParametreRelance())->setAgence($agence)->setEntreprise($agence->getEntreprise());
    }

    /** Modèle par défaut de l'agence, créé à la volée s'il n'existe pas encore. */
    public function getModeleParDefaut(Agence $agence): ModeleRelance
    {
        $modele = $this->modeleRepository->findDefaut($agence);
        if (!$modele) {
            $modele = (new ModeleRelance())
                ->setAgence($agence)
                ->setEntreprise($agence->getEntreprise())
                ->setParDefaut(true);
            $this->em->persist($modele);
            $this->em->flush();
        }

        return $modele;
    }

    /** Modèle actif lié au contrat de la facture, sinon le modèle par défaut de l'agence. */
    public function getModele(FactureLocation $facture): ModeleRelance
    {
        $modele = $facture->getContrat()?->getModeleRelance();
        if ($modele && $modele->isActif() && $modele->getAgence() === $facture->getAgence()) {
            return $modele;
        }

        return $this->getModeleParDefaut($facture->getAgence());
    }

    /**
     * Factures impayées de l'agence pour lesquelles un rappel ou une relance est dû aujourd'hui
     * et n'a pas encore été envoyé.
     *
     * Pour les relances, seul le palier le plus élevé atteint est proposé : si la tâche n'a pas tourné
     * pendant quelques jours, on rattrape sans renvoyer les paliers intermédiaires.
     *
     * @return array<int, array{facture: FactureLocation, etape: string, jours: int}>
     */
    public function getEcheances(ParametreRelance $parametres, ?\DateTimeInterface $aujourdhui = null): array
    {
        $aujourdhui = \DateTimeImmutable::createFromInterface($aujourdhui ?? new \DateTimeImmutable())->setTime(0, 0);
        $paliers = $parametres->isRelanceActif() ? $parametres->getJoursApresEcheance() : [];
        $avant = $parametres->isRappelActif() ? $parametres->getJoursAvantEcheance() : 0;

        if (!$avant && !$paliers) {
            return [];
        }

        $debut = $aujourdhui->modify('-' . ($paliers ? max($paliers) + self::FENETRE_RATTRAPAGE : 0) . ' days');
        $fin = $aujourdhui->modify('+' . ($avant + 1) . ' days');

        /** @var FactureLocation[] $factures */
        $factures = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f')
            ->where('f.agence = :agence')
            ->andWhere('f.statut IS NULL OR f.statut NOT IN (:payes)')
            ->andWhere('f.soldeFactLoc > 0')
            ->andWhere('f.dateLimite >= :debut AND f.dateLimite < :fin')
            ->setParameter('agence', $parametres->getAgence())
            ->setParameter('payes', self::STATUTS_PAYES)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('f.dateLimite', 'ASC')
            ->getQuery()
            ->getResult();

        $dejaEnvoyees = $this->getEtapesEnvoyees($factures);
        $echeances = [];

        foreach ($factures as $facture) {
            $ecart = $this->joursDepuisEcheance($facture, $aujourdhui);

            if ($ecart < 0) {
                $etape = -$ecart <= $avant ? self::ETAPE_RAPPEL : null;
            } else {
                $atteints = array_filter($paliers, fn (int $p) => $p <= $ecart);
                $etape = $atteints ? 'RELANCE_J' . max($atteints) : null;
            }

            if ($etape && !isset($dejaEnvoyees[$facture->getId()][$etape])) {
                $echeances[] = ['facture' => $facture, 'etape' => $etape, 'jours' => abs($ecart)];
            }
        }

        return $echeances;
    }

    /**
     * Sujet, message (email / notification) et texte SMS personnalisés pour une facture, à partir de son modèle.
     * L'étape est déduite de la date limite si elle n'est pas fournie.
     */
    public function construireMessage(FactureLocation $facture, ?string $etape = null): array
    {
        $modele = $this->getModele($facture);
        $ecart = $this->joursDepuisEcheance($facture, new \DateTimeImmutable('today'));
        $etape ??= $ecart < 0 ? self::ETAPE_RAPPEL : 'RELANCE';
        $estRappel = $etape === self::ETAPE_RAPPEL;

        return [
            'etape' => $etape,
            'modele' => ['id' => $modele->getId(), 'libelle' => $modele->getLibelle()],
            'sujet' => $this->remplacer($estRappel ? $modele->getRappelSujet() : $modele->getRelanceSujet(), $facture, $ecart),
            'message' => $this->remplacer($estRappel ? $modele->getRappelMessage() : $modele->getRelanceMessage(), $facture, $ecart),
            'sms' => $this->remplacer($estRappel ? $modele->getRappelSms() : $modele->getRelanceSms(), $facture, $ecart),
        ];
    }

    /** Canaux réellement utilisables pour ce locataire, parmi ceux activés par l'agence. */
    public function getCanauxDisponibles(ParametreRelance $parametres, FactureLocation $facture): array
    {
        $locataire = $facture->getLocataire();
        $disponibles = [];

        foreach ($parametres->getCanaux() as $canal) {
            if ($canal === ParametreRelance::CANAL_EMAIL && filter_var($locataire?->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $disponibles[] = $canal;
            }
            if ($canal === ParametreRelance::CANAL_SMS && $this->telephone($facture) && $this->isSmsActif($facture)) {
                $disponibles[] = $canal;
            }
            if ($canal === ParametreRelance::CANAL_NOTIFICATION && $locataire?->getUser()) {
                $disponibles[] = $canal;
            }
        }

        return $disponibles;
    }

    /**
     * Envoie le message sur chaque canal disponible et enregistre une Relance par envoi réussi.
     * Ne flush pas : l'appelant regroupe les écritures.
     *
     * @return array{envoyes: string[], erreurs: string[]}
     */
    public function envoyer(ParametreRelance $parametres, FactureLocation $facture, string $etape, string $origine, ?User $agent = null): array
    {
        $contenu = $this->construireMessage($facture, $etape);
        $locataire = $facture->getLocataire();
        $agence = $parametres->getAgence();
        $resultat = ['envoyes' => [], 'erreurs' => []];

        $canaux = $this->getCanauxDisponibles($parametres, $facture);
        if (!$canaux) {
            $resultat['erreurs'][] = 'Aucun canal disponible (ni email, ni SMS, ni compte locataire)';
            return $resultat;
        }

        foreach ($canaux as $canal) {
            try {
                if ($canal === ParametreRelance::CANAL_EMAIL) {
                    $from = $agence->getEmail() ?: $agence->getEntreprise()?->getEmail() ?: 'noreply@immoplus.pro';
                    $this->mailService->sendCustom($from, $locataire->getEmail(), $contenu['sujet'], nl2br(htmlspecialchars($contenu['message'])));
                    $observation = "Envoyé par email à {$locataire->getEmail()}";
                } elseif ($canal === ParametreRelance::CANAL_SMS) {
                    $sms = $this->smsService->envoyer($facture->getEntreprise() ?? $agence->getEntreprise(), $agence, $this->telephone($facture), $contenu['sms'], $facture);
                    if ($sms->getErreur()) {
                        throw new \RuntimeException($sms->getErreur());
                    }
                    $observation = "Envoyé par SMS au {$sms->getDestinataire()}";
                } else {
                    $this->notificationService->notify(
                        $locataire->getUser()->getId(),
                        $facture->getEntreprise() ?? $agence->getEntreprise(),
                        $contenu['sujet'],
                        $contenu['message'],
                        ['type' => 'relance', 'facture_id' => $facture->getId(), 'etape' => $etape]
                    );
                    $observation = 'Notification envoyée sur le compte du locataire';
                }
            } catch (\Throwable $e) {
                $this->logger->error("Relance facture #{$facture->getId()} ($canal) : {$e->getMessage()}");
                $resultat['erreurs'][] = "$canal : {$e->getMessage()}";
                continue;
            }

            $relance = (new Relance())
                ->setFacture($facture)
                ->setType($canal)
                ->setEtape($etape)
                ->setOrigine($origine)
                ->setObservation(($etape === self::ETAPE_RAPPEL ? 'Rappel' : 'Relance') . " ($etape) – $observation. Modèle : {$contenu['modele']['libelle']}")
                ->setAgent($agent)
                ->setAgence($agence)
                ->setEntreprise($facture->getEntreprise() ?? $agence->getEntreprise())
                ->setDateEffective(new \DateTime());
            $this->em->persist($relance);
            $resultat['envoyes'][] = $canal;
        }

        return $resultat;
    }

    /**
     * Envoi manuel d'un message à un locataire (application mobile de l'administrateur) :
     * texte fourni ou, à défaut, modèle de la facture. Enregistre une Relance d'origine MANUEL.
     *
     * @throws \RuntimeException avec un message affichable si l'envoi est impossible
     */
    public function envoyerMessageManuel(FactureLocation $facture, string $canal, ?string $sujet, ?string $texte, ?User $agent): Relance
    {
        $contenu = $this->construireMessage($facture);
        $locataire = $facture->getLocataire();
        $agence = $facture->getAgence();
        $entreprise = $facture->getEntreprise() ?? $agence?->getEntreprise();

        if ($canal === ParametreRelance::CANAL_SMS) {
            $telephone = $this->telephone($facture);
            if (!$telephone) {
                throw new \RuntimeException("Ce locataire n'a pas de numéro de téléphone.");
            }
            $sms = $this->smsService->envoyer($entreprise, $agence, $telephone, trim($texte ?: $contenu['sms']), $facture);
            if ($sms->getErreur()) {
                throw new \RuntimeException($sms->getErreur());
            }
            $observation = "SMS envoyé au {$sms->getDestinataire()}";
        } elseif ($canal === ParametreRelance::CANAL_EMAIL) {
            $email = $locataire?->getEmail();
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException("Ce locataire n'a pas d'adresse e-mail valide.");
            }
            $from = $agence?->getEmail() ?: $entreprise?->getEmail() ?: 'noreply@immoplus.pro';
            $this->mailService->sendCustom($from, $email, trim($sujet ?: $contenu['sujet']), nl2br(htmlspecialchars(trim($texte ?: $contenu['message']))));
            $observation = "E-mail envoyé à $email";
        } else {
            throw new \RuntimeException('Canal inconnu : EMAIL ou SMS.');
        }

        $relance = (new Relance())
            ->setFacture($facture)
            ->setType($canal)
            ->setOrigine(self::ORIGINE_MANUEL)
            ->setObservation("Message manuel – $observation")
            ->setAgent($agent)
            ->setAgence($agence)
            ->setEntreprise($entreprise)
            ->setDateEffective(new \DateTime());
        $this->em->persist($relance);
        $this->em->flush();

        return $relance;
    }

    private function telephone(FactureLocation $facture): ?string
    {
        $locataire = $facture->getLocataire();

        return trim($locataire?->getContacts() ?? '') ?: (trim($locataire?->getTelWhatsapp() ?? '') ?: null);
    }

    private function isSmsActif(FactureLocation $facture): bool
    {
        $entreprise = $facture->getEntreprise() ?? $facture->getAgence()?->getEntreprise();
        if (!$entreprise) {
            return false;
        }

        return $this->smsActif[$entreprise->getId()] ??= $this->smsService->getOffre($entreprise)['actif'];
    }

    /** Jours écoulés depuis la date limite (négatif = échéance à venir). */
    private function joursDepuisEcheance(FactureLocation $facture, \DateTimeImmutable $aujourdhui): int
    {
        if (!$facture->getDateLimite()) {
            return 0;
        }
        $limite = \DateTimeImmutable::createFromInterface($facture->getDateLimite())->setTime(0, 0);

        return (int) $limite->diff($aujourdhui->setTime(0, 0))->format('%r%a');
    }

    /** @return array<int, array<string, true>> facture id => étapes déjà envoyées */
    private function getEtapesEnvoyees(array $factures): array
    {
        if (!$factures) {
            return [];
        }

        $lignes = $this->em->createQueryBuilder()
            ->select('IDENTITY(r.facture) AS facture_id', 'r.etape')
            ->from(Relance::class, 'r')
            ->where('r.facture IN (:factures)')
            ->andWhere('r.etape IS NOT NULL')
            ->setParameter('factures', $factures)
            ->getQuery()
            ->getArrayResult();

        $envoyees = [];
        foreach ($lignes as $ligne) {
            $envoyees[(int) $ligne['facture_id']][$ligne['etape']] = true;
        }

        return $envoyees;
    }

    private function remplacer(string $modele, FactureLocation $facture, int $ecart): string
    {
        $locataire = $facture->getLocataire();
        $agence = $facture->getAgence();
        $format = fn (?int $n) => number_format((int) $n, 0, ',', ' ') . ' FCFA';

        return strtr($modele, [
            '{locataire}' => trim(($locataire?->getNom() ?? '') . ' ' . ($locataire?->getPrenoms() ?? '')),
            '{nom}' => $locataire?->getNom() ?? '',
            '{prenoms}' => $locataire?->getPrenoms() ?? '',
            '{facture}' => $facture->getLibFacture() ?? '',
            '{mois}' => trim(($facture->getMois()?->getLibMois() ?? '') . ' ' . ($facture->getDateLimite()?->format('Y') ?? '')),
            '{montant}' => $format($facture->getSoldeFactLoc() ?? $facture->getMntFact()),
            '{montant_facture}' => $format($facture->getMntFact()),
            '{date_limite}' => $facture->getDateLimite()?->format('d/m/Y') ?? '',
            '{jours_restants}' => (string) max(0, -$ecart),
            '{jours_retard}' => (string) max(0, $ecart),
            '{logement}' => $facture->getAppartement()?->getLibAppart() ?? '',
            '{agence}' => $agence?->getNom() ?? '',
            '{agence_contact}' => $agence?->getContact() ?? '',
        ]);
    }
}
