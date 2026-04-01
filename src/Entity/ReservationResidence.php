<?php

namespace App\Entity;

use App\Repository\ReservationResidenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReservationResidenceRepository::class)]
#[ORM\Table(name: '_reservation_residence')]
class ReservationResidence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Residence::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Residence $residence = null;

    #[ORM\Column(length: 100)]
    #[Groups(['group1'])]
    private ?string $nomLocataire = null;

    #[ORM\Column(length: 100)]
    #[Groups(['group1'])]
    private ?string $prenomLocataire = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(['group1'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['group1'])]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Fichier::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $carteIdentite = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private int $montant = 0;

    /** EN_ATTENTE | CONFIRMEE | TERMINEE | ANNULEE */
    #[ORM\Column(length: 20, options: ['default' => 'EN_ATTENTE'])]
    #[Groups(['group1'])]
    private string $etat = 'EN_ATTENTE';

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
    public function getNomLocataire(): ?string { return $this->nomLocataire; }
    public function setNomLocataire(string $v): static { $this->nomLocataire = $v; return $this; }
    public function getPrenomLocataire(): ?string { return $this->prenomLocataire; }
    public function setPrenomLocataire(string $v): static { $this->prenomLocataire = $v; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $v): static { $this->telephone = $v; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): static { $this->email = $v; return $this; }
    public function getCarteIdentite(): ?Fichier { return $this->carteIdentite; }
    public function setCarteIdentite(?Fichier $v): static { $this->carteIdentite = $v; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $v): static { $this->dateDebut = $v; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $v): static { $this->dateFin = $v; return $this; }
    public function getMontant(): int { return $this->montant; }
    public function setMontant(int $v): static { $this->montant = $v; return $this; }
    public function getEtat(): string { return $this->etat; }
    public function setEtat(string $v): static { $this->etat = $v; return $this; }
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
