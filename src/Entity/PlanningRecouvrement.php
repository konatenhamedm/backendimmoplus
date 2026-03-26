<?php

namespace App\Entity;

use App\Repository\PlanningRecouvrementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PlanningRecouvrementRepository::class)]
class PlanningRecouvrement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['group1'])]
    private ?string $details = null;

    #[ORM\ManyToOne(targetEntity: Locataire::class)]
    #[Groups(['group1'])]
    private ?Locataire $locataire = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['group1'])]
    private ?User $agent = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 50)]
    #[Groups(['group1'])]
    private ?string $status = 'À FAIRE'; // 'À FAIRE', 'TERMINE', 'REPORTE', 'ANNULE'

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $location = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getLocataire(): ?Locataire
    {
        return $this->locataire;
    }

    public function setLocataire(?Locataire $locataire): self
    {
        $this->locataire = $locataire;
        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }
}
