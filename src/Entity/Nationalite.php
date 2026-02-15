<?php

namespace App\Entity;

use App\Repository\NationaliteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NationaliteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Nationalite
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
    private ?string $Observations = null;

    #[ORM\Column(length: 255)]
    private ?string $Abrege = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->Observations;
    }

    public function setObservations(string $Observations): static
    {
        $this->Observations = $Observations;

        return $this;
    }

    public function getAbrege(): ?string
    {
        return $this->Abrege;
    }

    public function setAbrege(string $Abrege): static
    {
        $this->Abrege = $Abrege;

        return $this;
    }
}
