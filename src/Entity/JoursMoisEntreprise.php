<?php

namespace App\Entity;

use App\Repository\JoursMoisEntrepriseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JoursMoisEntrepriseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JoursMoisEntreprise
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $active = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\ManyToOne(inversedBy: 'joursMoisEntreprises')]
    private ?entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'joursMoisEntreprises')]
    private ?JoursMois $joursMois = null;

   

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getEntreprise(): ?entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getJoursMois(): ?JoursMois
    {
        return $this->joursMois;
    }

    public function setJoursMois(?JoursMois $joursMois): static
    {
        $this->joursMois = $joursMois;

        return $this;
    }

  
}
