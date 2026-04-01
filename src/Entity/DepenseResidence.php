<?php

namespace App\Entity;

use App\Repository\DepenseResidenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DepenseResidenceRepository::class)]
#[ORM\Table(name: '_depense_residence')]
class DepenseResidence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Residence::class, inversedBy: 'depenses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1', 'group2'])]
    private ?Residence $residence = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'group2'])]
    private ?string $libelle = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private int $montant = 0;

    #[ORM\Column(type: 'date')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateDepense = null;

    /** Relationship to specific expense types */
    #[ORM\ManyToOne(targetEntity: TypeDepense::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?TypeDepense $typeDepense = null;

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Agence $agence = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['group1'])]
    private ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: Fichier::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $justificatif = null;

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
    public function getLibelle(): ?string { return $this->libelle; }
    public function setLibelle(string $v): static { $this->libelle = $v; return $this; }
    public function getMontant(): int { return $this->montant; }
    public function setMontant(int $v): static { $this->montant = $v; return $this; }
    public function getDateDepense(): ?\DateTimeInterface { return $this->dateDepense; }
    public function setDateDepense(\DateTimeInterface $v): static { $this->dateDepense = $v; return $this; }
    public function getTypeDepense(): ?TypeDepense { return $this->typeDepense; }
    public function setTypeDepense(?TypeDepense $v): static { $this->typeDepense = $v; return $this; }
    public function getAgence(): ?Agence { return $this->agence; }
    public function setAgence(?Agence $v): static { $this->agence = $v; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }
    public function getJustificatif(): ?Fichier { return $this->justificatif; }
    public function setJustificatif(?Fichier $v): static { $this->justificatif = $v; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $v): static { $this->updatedAt = $v; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $v): static { $this->createdBy = $v; return $this; }
    public function getUpdatedBy(): ?User { return $this->updatedBy; }
    public function setUpdatedBy(?User $v): static { $this->updatedBy = $v; return $this; }
}
