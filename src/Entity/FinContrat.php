<?php

namespace App\Entity;

use App\Repository\FincontratRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FincontratRepository::class)]
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
    private ?int $DateFin = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $Details = null;

    #[ORM\Column(length: 255)]
    private ?string $Fichier = null;

    #[ORM\Column]
    private ?int $CautionRemise = null;

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
        return $this->DateFin;
    }

    public function setDateFin(int $DateFin): static
    {
        $this->DateFin = $DateFin;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->Details;
    }

    public function setDetails(string $Details): static
    {
        $this->Details = $Details;

        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->Fichier;
    }

    public function setFichier(string $Fichier): static
    {
        $this->Fichier = $Fichier;

        return $this;
    }

    public function getCautionRemise(): ?int
    {
        return $this->CautionRemise;
    }

    public function setCautionRemise(int $CautionRemise): static
    {
        $this->CautionRemise = $CautionRemise;

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
