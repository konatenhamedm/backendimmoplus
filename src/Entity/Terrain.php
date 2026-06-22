<?php

namespace App\Entity;

use App\Repository\TerrainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

#[ORM\Entity(repositoryClass: TerrainRepository::class)]
#[UniqueEntity(['num'], message: 'Ce numéro est déjà utilisé')]
#[ORM\HasLifecycleCallbacks]
class Terrain
{
    use TraitEntity;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $num = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $superfice = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $prix = null;

    #[ORM\ManyToOne(inversedBy: 'terrain', cascade: ['persist'])]
    #[Groups(["group1"])]
    private ?Site $site = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["group1"])]
    private string $etat = 'disponible';

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["group1"])]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["group1"])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $dimensions = null;

    #[ORM\ManyToOne(targetEntity: Fichier::class, cascade: ['persist'])]
    #[Groups(["group1"])]
    private ?Fichier $planTopographique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $coordonneesPolygone = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNum(): ?string
    {
        return $this->num;
    }

    public function setNum(string $num): static
    {
        $this->num = $num;

        return $this;
    }
    
    public function getSuperfice(): ?string
    {
        return $this->superfice;
    }

    public function setSuperfice(string $superfice): static
    {
        $this->superfice = $superfice;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): static
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function getPlanTopographique(): ?Fichier
    {
        return $this->planTopographique;
    }

    public function setPlanTopographique(?Fichier $planTopographique): static
    {
        $this->planTopographique = $planTopographique;
        return $this;
    }

    public function getCoordonneesPolygone(): ?string
    {
        return $this->coordonneesPolygone;
    }

    public function setCoordonneesPolygone(?string $coordonneesPolygone): static
    {
        $this->coordonneesPolygone = $coordonneesPolygone;

        return $this;
    }

    public function __toString()
    {
        return $this->num ?? '';
    }
}
