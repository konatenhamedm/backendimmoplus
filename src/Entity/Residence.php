<?php

namespace App\Entity;

use App\Repository\ResidenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ResidenceRepository::class)]
#[ORM\Table(name: '_residence')]
class Residence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1','group2'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1','group2'])]
    private ?string $libelle = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?string $adresse = null;

    #[ORM\Column]
    #[Groups(['group1','group2'])]
    private int $montantLocation = 0;

    /** Nombre de pièces */
    #[ORM\Column(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?int $nombrePiece = null;

    /** DISPONIBLE | OCCUPEE | MAINTENANCE */
    #[ORM\Column(length: 50, options: ['default' => 'DISPONIBLE'])]
    #[Groups(['group1','group2'])]
    private string $etat = 'DISPONIBLE';

    /** L'agence loue cette résidence chez un particulier et la sous-loue */
    #[ORM\Column(options: ['default' => false])]
    #[Groups(['group1','group2'])]
    private bool $chargeLoyer = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?int $montantLoyer = null;

    /** MENSUEL | SEMESTRIEL | ANNUEL */
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['group1','group2'])]
    private ?string $periodiciteLoyer = null;

    // ─── Infos propriétaire (si chargeLoyer = true) ───────────────────────────
    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['group1','group2'])]
    private ?string $nomProprietaire = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['group1','group2'])]
    private ?string $telephoneProprietaire = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['group1','group2'])]
    private ?string $emailProprietaire = null;

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(targetEntity: Fichier::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?Fichier $photo = null;

    #[ORM\OneToMany(mappedBy: 'residence', targetEntity: ReservationResidence::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['group1','group2'])]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'residence', targetEntity: LoyerResidence::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['group1','group2'])]
    private Collection $loyerPaiements;

    #[ORM\OneToMany(mappedBy: 'residence', targetEntity: DepenseResidence::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['group1','group2'])]
    private Collection $depenses;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1','group2'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    public function __construct()
    {
        $this->reservations   = new ArrayCollection();
        $this->loyerPaiements = new ArrayCollection();
        $this->depenses       = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getLibelle(): ?string { return $this->libelle; }
    public function setLibelle(string $v): static { $this->libelle = $v; return $this; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $v): static { $this->adresse = $v; return $this; }
    public function getMontantLocation(): int { return $this->montantLocation; }
    public function setMontantLocation(int $v): static { $this->montantLocation = $v; return $this; }
    public function getNombrePiece(): ?int { return $this->nombrePiece; }
    public function setNombrePiece(?int $v): static { $this->nombrePiece = $v; return $this; }
    public function getEtat(): string { return $this->etat; }
    public function setEtat(string $v): static { $this->etat = $v; return $this; }
    public function isChargeLoyer(): bool { return $this->chargeLoyer; }
    public function setChargeLoyer(bool $v): static { $this->chargeLoyer = $v; return $this; }
    public function getMontantLoyer(): ?int { return $this->montantLoyer; }
    public function setMontantLoyer(?int $v): static { $this->montantLoyer = $v; return $this; }
    public function getPeriodiciteLoyer(): ?string { return $this->periodiciteLoyer; }
    public function setPeriodiciteLoyer(?string $v): static { $this->periodiciteLoyer = $v; return $this; }
    public function getNomProprietaire(): ?string { return $this->nomProprietaire; }
    public function setNomProprietaire(?string $v): static { $this->nomProprietaire = $v; return $this; }
    public function getTelephoneProprietaire(): ?string { return $this->telephoneProprietaire; }
    public function setTelephoneProprietaire(?string $v): static { $this->telephoneProprietaire = $v; return $this; }
    public function getEmailProprietaire(): ?string { return $this->emailProprietaire; }
    public function setEmailProprietaire(?string $v): static { $this->emailProprietaire = $v; return $this; }
    public function getAgence(): ?Agence { return $this->agence; }
    public function setAgence(?Agence $v): static { $this->agence = $v; return $this; }
    public function getPhoto(): ?Fichier { return $this->photo; }
    public function setPhoto(?Fichier $v): static { $this->photo = $v; return $this; }
    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $v): static { $this->entreprise = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $v): static { $this->updatedAt = $v; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $v): static { $this->createdBy = $v; return $this; }
    public function getUpdatedBy(): ?User { return $this->updatedBy; }
    public function setUpdatedBy(?User $v): static { $this->updatedBy = $v; return $this; }

    public function getReservations(): Collection { return $this->reservations; }
    public function addReservation(ReservationResidence $r): static {
        if (!$this->reservations->contains($r)) { $this->reservations->add($r); $r->setResidence($this); }
        return $this;
    }
    public function removeReservation(ReservationResidence $r): static {
        if ($this->reservations->removeElement($r) && $r->getResidence() === $this) { $r->setResidence(null); }
        return $this;
    }

    public function getLoyerPaiements(): Collection { return $this->loyerPaiements; }
    public function addLoyerPaiement(LoyerResidence $l): static {
        if (!$this->loyerPaiements->contains($l)) { $this->loyerPaiements->add($l); $l->setResidence($this); }
        return $this;
    }
    public function removeLoyerPaiement(LoyerResidence $l): static {
        if ($this->loyerPaiements->removeElement($l) && $l->getResidence() === $this) { $l->setResidence(null); }
        return $this;
    }

    public function getDepenses(): Collection { return $this->depenses; }
    public function addDepense(DepenseResidence $d): static {
        if (!$this->depenses->contains($d)) { $this->depenses->add($d); $d->setResidence($this); }
        return $this;
    }
    public function removeDepense(DepenseResidence $d): static {
        if ($this->depenses->removeElement($d) && $d->getResidence() === $this) { $d->setResidence(null); }
        return $this;
    }
}
