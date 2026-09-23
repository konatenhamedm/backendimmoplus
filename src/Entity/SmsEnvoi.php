<?php

namespace App\Entity;

use App\Repository\SmsEnvoiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Journal des SMS envoyés (ou tentés). Sert aussi au décompte du quota SMS de l'abonnement.
 */
#[ORM\Entity(repositoryClass: SmsEnvoiRepository::class)]
#[ORM\Index(columns: ['statut', 'date_envoi'])]
class SmsEnvoi
{
    public const STATUT_ENVOYE = 'ENVOYE';
    public const STATUT_ECHEC = 'ECHEC';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group_sms'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FactureLocation $facture = null;

    #[ORM\Column(length: 30)]
    #[Groups(['group_sms'])]
    private ?string $destinataire = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['group_sms'])]
    private ?string $message = null;

    /** Nombre de SMS facturés (un message long est découpé en plusieurs SMS). */
    #[ORM\Column]
    #[Groups(['group_sms'])]
    private int $nbSms = 1;

    #[ORM\Column(length: 20)]
    #[Groups(['group_sms'])]
    private string $statut = self::STATUT_ENVOYE;

    #[ORM\Column(length: 50)]
    #[Groups(['group_sms'])]
    private ?string $fournisseur = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $referenceFournisseur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['group_sms'])]
    private ?string $erreur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['group_sms'])]
    private ?\DateTimeInterface $dateEnvoi = null;

    public function __construct()
    {
        $this->dateEnvoi = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;

        return $this;
    }

    public function getFacture(): ?FactureLocation
    {
        return $this->facture;
    }

    public function setFacture(?FactureLocation $facture): static
    {
        $this->facture = $facture;

        return $this;
    }

    public function getDestinataire(): ?string
    {
        return $this->destinataire;
    }

    public function setDestinataire(string $destinataire): static
    {
        $this->destinataire = $destinataire;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getNbSms(): int
    {
        return $this->nbSms;
    }

    public function setNbSms(int $nbSms): static
    {
        $this->nbSms = $nbSms;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getFournisseur(): ?string
    {
        return $this->fournisseur;
    }

    public function setFournisseur(string $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getReferenceFournisseur(): ?string
    {
        return $this->referenceFournisseur;
    }

    public function setReferenceFournisseur(?string $referenceFournisseur): static
    {
        $this->referenceFournisseur = $referenceFournisseur;

        return $this;
    }

    public function getErreur(): ?string
    {
        return $this->erreur;
    }

    public function setErreur(?string $erreur): static
    {
        $this->erreur = $erreur;

        return $this;
    }

    public function getDateEnvoi(): ?\DateTimeInterface
    {
        return $this->dateEnvoi;
    }
}
