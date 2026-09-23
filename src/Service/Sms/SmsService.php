<?php

namespace App\Service\Sms;

use App\Entity\Agence;
use App\Entity\Entreprise;
use App\Entity\FactureLocation;
use App\Entity\SmsEnvoi;
use App\Repository\AbonnementRepository;
use App\Repository\SmsEnvoiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Envoi de SMS soumis à l'offre SMS de l'abonnement (activée + quota par période, -1 = illimité).
 */
class SmsService
{
    // Alphabet GSM 03.38 : 160 caractères par SMS ; sinon (emojis, certains accents) 70 caractères.
    private const GSM_7 = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private SmsSenderInterface $sender;

    /** SMS envoyés dans la requête en cours mais pas encore flushés, par entreprise (pour ne pas dépasser le quota pendant un envoi groupé). */
    private array $enAttente = [];

    public function __construct(
        #[AutowireIterator('app.sms_sender', defaultIndexMethod: 'getName')] iterable $senders,
        #[Autowire(env: 'default::SMS_FOURNISSEUR')] ?string $fournisseur,
        #[Autowire(env: 'default::SMS_EXPEDITEUR')] private ?string $expediteur,
        #[Autowire(env: 'default::SMS_INDICATIF_PAYS')] private ?string $indicatifPays,
        private EntityManagerInterface $em,
        private AbonnementRepository $abonnementRepository,
        private SmsEnvoiRepository $smsEnvoiRepository,
    ) {
        $senders = iterator_to_array($senders);
        $this->sender = $senders[$fournisseur ?: 'null'] ?? $senders['null'];
        $this->expediteur ??= 'MOTIPLUS';
        $this->indicatifPays ??= '225';
    }

    public function isFournisseurConfigure(): bool
    {
        return !$this->sender instanceof NullSmsSender;
    }

    /**
     * État de l'offre SMS de l'entreprise pour la période d'abonnement en cours.
     *
     * @return array{actif: bool, quota: int, utilises: int, restant: ?int, fournisseurConfigure: bool}
     */
    public function getOffre(Entreprise $entreprise): array
    {
        $abonnement = $this->abonnementRepository->findOneBy(['entreprise' => $entreprise, 'etat' => 'ACTIF']);
        $module = $abonnement?->getModuleAbonnement();
        $actif = (bool) $module?->isHasSms();
        $quota = $actif ? (int) $module->getSmsQuota() : 0;

        $utilises = 0;
        if ($actif) {
            $debut = $abonnement->getCreatedAt()
                ?? ($abonnement->getDateFin() ? (clone $abonnement->getDateFin())->modify('-' . ((int) $module->getDuree() ?: 30) . ' days') : new \DateTime('first day of this month'));
            $utilises = $this->smsEnvoiRepository->countEnvoyesDepuis($entreprise, $debut) + ($this->enAttente[$entreprise->getId()] ?? 0);
        }

        return [
            'actif' => $actif,
            'quota' => $quota,
            'utilises' => $utilises,
            'restant' => $quota === -1 ? null : max(0, $quota - $utilises),
            'fournisseurConfigure' => $this->isFournisseurConfigure(),
        ];
    }

    /**
     * Envoie un SMS et l'enregistre dans le journal. Ne flush pas.
     *
     * @throws \RuntimeException si l'offre SMS n'est pas active, le quota épuisé ou le numéro invalide
     */
    public function envoyer(Entreprise $entreprise, ?Agence $agence, string $numero, string $message, ?FactureLocation $facture = null): SmsEnvoi
    {
        $offre = $this->getOffre($entreprise);
        if (!$offre['actif']) {
            throw new \RuntimeException("L'offre SMS n'est pas incluse dans votre abonnement");
        }

        $nbSms = $this->compterSms($message);
        if ($offre['restant'] !== null && $offre['restant'] < $nbSms) {
            throw new \RuntimeException("Quota SMS épuisé ({$offre['utilises']}/{$offre['quota']})");
        }

        $destinataire = $this->normaliserNumero($numero);
        if (!$destinataire) {
            throw new \RuntimeException("Numéro de téléphone invalide : $numero");
        }

        $resultat = $this->sender->send($destinataire, $message, $this->expediteur);

        $sms = (new SmsEnvoi())
            ->setEntreprise($entreprise)
            ->setAgence($agence)
            ->setFacture($facture)
            ->setDestinataire($destinataire)
            ->setMessage($message)
            ->setNbSms($nbSms)
            ->setFournisseur($this->sender::getName())
            ->setStatut($resultat->success ? SmsEnvoi::STATUT_ENVOYE : SmsEnvoi::STATUT_ECHEC)
            ->setReferenceFournisseur($resultat->reference)
            ->setErreur($resultat->erreur);
        $this->em->persist($sms);

        if ($resultat->success) {
            $this->enAttente[$entreprise->getId()] = ($this->enAttente[$entreprise->getId()] ?? 0) + $nbSms;
        }

        return $sms;
    }

    /** Nombre de SMS facturés pour ce message (découpage des messages longs). */
    public function compterSms(string $message): int
    {
        $longueur = mb_strlen($message);
        $gsm = (bool) preg_match('/^[' . preg_quote(self::GSM_7, '/') . ']*$/u', $message);
        [$simple, $concatene] = $gsm ? [160, 153] : [70, 67];

        return $longueur <= $simple ? 1 : (int) ceil($longueur / $concatene);
    }

    /** Numéro au format international (+XXX…), ou null s'il est invalide. */
    public function normaliserNumero(string $numero): ?string
    {
        $numero = trim($numero);
        $chiffres = preg_replace('/\D/', '', $numero);

        if (str_starts_with($numero, '+')) {
            $international = $chiffres;
        } elseif (str_starts_with($chiffres, '00')) {
            $international = substr($chiffres, 2);
        } elseif (str_starts_with($chiffres, $this->indicatifPays) && strlen($chiffres) > 10) {
            $international = $chiffres;
        } else {
            $international = $this->indicatifPays . $chiffres;
        }

        return preg_match('/^\d{8,15}$/', $international) ? '+' . $international : null;
    }
}
