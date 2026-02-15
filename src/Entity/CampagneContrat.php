<?php

namespace App\Entity;

use App\Repository\CampagneContratRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampagneContratRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CampagneContrat
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $proprietaire = null;

    #[ORM\Column(length: 255)]
    private ?string $locataire = null;

    #[ORM\Column(length: 255)]
    private ?string $maison = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0')]
    private ?string $loyer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateLimite = null;

    #[ORM\ManyToOne(inversedBy: 'campagneContrats')]
    private ?Campagne $campagne = null;

    #[ORM\Column(length: 10)]
    private ?string $numAppartement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProprietaire(): ?string
    {
        return $this->proprietaire;
    }

    public function setProprietaire(string $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getLocataire(): ?string
    {
        return $this->locataire;
    }

    public function setLocataire(string $locataire): static
    {
        $this->locataire = $locataire;

        return $this;
    }

    public function getMaison(): ?string
    {
        return $this->maison;
    }

    public function setMaison(string $maison): static
    {
        $this->maison = $maison;

        return $this;
    }

    public function getLoyer(): ?string
    {
        return $this->loyer;
    }

    public function setLoyer(string $loyer): static
    {
        $this->loyer = $loyer;

        return $this;
    }


    public function getDateLimite(): ?\DateTimeInterface
    {
        return $this->dateLimite;
    }

    public function setDateLimite(\DateTimeInterface $dateLimite): static
    {
        $this->dateLimite = $dateLimite;

        return $this;
    }

    public function getCampagne(): ?Campagne
    {
        return $this->campagne;
    }

    public function setCampagne(?Campagne $campagne): static
    {
        $this->campagne = $campagne;

        return $this;
    }

    public function getNumAppartement(): ?string
    {
        return $this->numAppartement;
    }

    public function setNumAppartement(string $numAppartement): static
    {
        $this->numAppartement = $numAppartement;

        return $this;
    }
}
