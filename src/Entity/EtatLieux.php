<?php

namespace App\Entity;

use App\Repository\EtatLieuxRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EtatLieuxRepository::class)]
class EtatLieux
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?ContratLocation $contratLocation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTime $dateEtatLieux = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1'])]
    private ?array $compteurs = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1'])]
    private ?array $cles = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1'])]
    private ?array $pieces = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['group1'])]
    private ?string $observations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContratLocation(): ?ContratLocation
    {
        return $this->contratLocation;
    }

    public function setContratLocation(?ContratLocation $contratLocation): static
    {
        $this->contratLocation = $contratLocation;

        return $this;
    }

    public function getDateEtatLieux(): ?\DateTime
    {
        return $this->dateEtatLieux;
    }

    public function setDateEtatLieux(?\DateTime $dateEtatLieux): static
    {
        $this->dateEtatLieux = $dateEtatLieux;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCompteurs(): ?array
    {
        return $this->compteurs;
    }

    public function setCompteurs(?array $compteurs): static
    {
        $this->compteurs = $compteurs;

        return $this;
    }

    public function getCles(): ?array
    {
        return $this->cles;
    }

    public function setCles(?array $cles): static
    {
        $this->cles = $cles;

        return $this;
    }

    public function getPieces(): ?array
    {
        return $this->pieces;
    }

    public function setPieces(?array $pieces): static
    {
        $this->pieces = $pieces;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): static
    {
        $this->observations = $observations;

        return $this;
    }
}
