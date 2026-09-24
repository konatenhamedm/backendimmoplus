<?php

namespace App\Entity;

use App\Repository\ParametrePenaliteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pénalités de retard d'une agence : montant ajouté aux factures dont la date de paiement est dépassée.
 */
#[ORM\Entity(repositoryClass: ParametrePenaliteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ParametrePenalite
{
    use TraitEntity;

    public const TYPE_FIXE = 'FIXE';
    public const TYPE_POURCENTAGE = 'POURCENTAGE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Entreprise $entreprise = null;

    #[ORM\Column]
    private bool $actif = false;

    /** FIXE : montant en FCFA ; POURCENTAGE : pourcentage du montant de la facture. */
    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_FIXE;

    #[ORM\Column]
    private float $valeur = 0;

    /** Jours après la date limite avant la première pénalité. */
    #[ORM\Column]
    private int $delaiGrace = 5;

    /** Nouvelle pénalité à chaque mois de retard supplémentaire. */
    #[ORM\Column]
    private bool $recurrenceMensuelle = true;

    /** Total maximum des pénalités d'une facture (null = sans plafond). */
    #[ORM\Column(nullable: true)]
    private ?int $plafond = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(Agence $agence): static
    {
        $this->agence = $agence;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type === self::TYPE_POURCENTAGE ? self::TYPE_POURCENTAGE : self::TYPE_FIXE;

        return $this;
    }

    public function getValeur(): float
    {
        return $this->valeur;
    }

    public function setValeur(float $valeur): static
    {
        $this->valeur = max(0, $valeur);

        return $this;
    }

    public function getDelaiGrace(): int
    {
        return $this->delaiGrace;
    }

    public function setDelaiGrace(int $jours): static
    {
        $this->delaiGrace = max(0, min(90, $jours));

        return $this;
    }

    public function isRecurrenceMensuelle(): bool
    {
        return $this->recurrenceMensuelle;
    }

    public function setRecurrenceMensuelle(bool $recurrence): static
    {
        $this->recurrenceMensuelle = $recurrence;

        return $this;
    }

    public function getPlafond(): ?int
    {
        return $this->plafond;
    }

    public function setPlafond(?int $plafond): static
    {
        $this->plafond = $plafond && $plafond > 0 ? $plafond : null;

        return $this;
    }
}
