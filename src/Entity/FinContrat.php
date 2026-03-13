<?php

namespace App\Entity;

use App\Repository\FincontratRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinContratRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FinContrat
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fincontrats')]
    private ?ContratLocation $contrat = null;

    #[ORM\Column]
    private ?int $dateFin = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $details = null;

    #[ORM\Column(length: 255)]
    private ?string $fichier = null;

    #[ORM\Column]
    private ?int $cautionRemise = null;

    #[ORM\ManyToOne(inversedBy: 'fincontrats')]
    private ?Motif $motif = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?ContratLocation
    {
        return $this->contrat;
    }

    public function setContrat(?ContratLocation $contrat): static
    {
        $this->contrat = $contrat;

        return $this;
    }

    public function getDateFin(): ?int
    {
        return $this->dateFin;
    }

    public function setDateFin(int $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getCautionRemise(): ?int
    {
        return $this->cautionRemise;
    }

    public function setCautionRemise(int $cautionRemise): static
    {
        $this->cautionRemise = $cautionRemise;

        return $this;
    }

    public function getMotif(): ?Motif
    {
        return $this->motif;
    }

    public function setMotif(?Motif $motif): static
    {
        $this->motif = $motif;

        return $this;
    }
}
