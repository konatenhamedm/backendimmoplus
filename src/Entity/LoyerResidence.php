<?php

namespace App\Entity;

use App\Repository\LoyerResidenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LoyerResidenceRepository::class)]
#[ORM\Table(name: '_loyer_residence')]
class LoyerResidence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Residence::class, inversedBy: 'loyerPaiements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Residence $residence = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private int $montant = 0;

    #[ORM\Column(type: 'date')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $datePaiement = null;

    /** Ex: "Janvier 2025", "S1 2025", "Année 2025" */
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['group1'])]
    private ?string $periodeLabel = null;

    /** MENSUEL | SEMESTRIEL | ANNUEL */
    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['group1'])]
    private ?string $periodicite = null;

    /** PAYE | EN_ATTENTE | EN_RETARD */
    #[ORM\Column(length: 20, options: ['default' => 'EN_ATTENTE'])]
    #[Groups(['group1'])]
    private string $etat = 'EN_ATTENTE';

    #[ORM\ManyToOne(targetEntity: Fichier::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $scan = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['group1'])]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    public function getId(): ?int { return $this->id; }
    public function getResidence(): ?Residence { return $this->residence; }
    public function setResidence(?Residence $v): static { $this->residence = $v; return $this; }
    public function getMontant(): int { return $this->montant; }
    public function setMontant(int $v): static { $this->montant = $v; return $this; }
    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(\DateTimeInterface $v): static { $this->datePaiement = $v; return $this; }
    public function getPeriodeLabel(): ?string { return $this->periodeLabel; }
    public function setPeriodeLabel(?string $v): static { $this->periodeLabel = $v; return $this; }
    public function getPeriodicite(): ?string { return $this->periodicite; }
    public function setPeriodicite(?string $v): static { $this->periodicite = $v; return $this; }
    public function getEtat(): string { return $this->etat; }
    public function setEtat(string $v): static { $this->etat = $v; return $this; }
    public function getScan(): ?Fichier { return $this->scan; }
    public function setScan(?Fichier $v): static { $this->scan = $v; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $v): static { $this->updatedAt = $v; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $v): static { $this->createdBy = $v; return $this; }
    public function getUpdatedBy(): ?User { return $this->updatedBy; }
    public function setUpdatedBy(?User $v): static { $this->updatedBy = $v; return $this; }
}
