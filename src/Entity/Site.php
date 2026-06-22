<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Site
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $localisation = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["group1"])]
    private string $etat = 'en_attente';


    // #[ORM\Column(length: 255)]
    // private ?string $justification = null;

    #[ORM\OneToMany(mappedBy: 'site', targetEntity: Terrain::class)]
    #[Groups(["group1"])]
    private Collection $terrain;

    

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["group1"])]
    private ?string $description = null;

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
    private ?string $superficieTotale = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $situationGeographique = null;

    #[ORM\ManyToOne(targetEntity: Fichier::class, cascade: ['persist'])]
    #[Groups(["group1"])]
    private ?Fichier $planLotissement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $latitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $longitude = null;

    #[ORM\ManyToOne(targetEntity: Pays::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["group1"])]
    private ?Pays $pays = null;

    #[ORM\ManyToOne(targetEntity: Ville::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["group1"])]
    private ?Ville $ville = null;

    public function __construct()
    {
        $this->terrain = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

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

    // public function getJustification(): ?string
    // {
    //     return $this->justification;
    // }

    // public function setJustification(string $justification): static
    // {
    //     $this->justification = $justification;

    //     return $this;
    // }

    /**
     * @return Collection<int, Terrain>
     */
    public function getTerrain(): Collection
    {
        return $this->terrain;
    }

    public function addTerrain(Terrain $terrain): static
    {
        if (!$this->terrain->contains($terrain)) {
            $this->terrain->add($terrain);
            $terrain->setSite($this);
        }

        return $this;
    }

    public function removeTerrain(Terrain $terrain): static
    {
        if ($this->terrain->removeElement($terrain)) {
            // set the owning side to null (unless already changed)
            if ($terrain->getSite() === $this) {
                $terrain->setSite(null);
            }
        }

        return $this;
    }

 

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getSuperficieTotale(): ?string
    {
        return $this->superficieTotale;
    }

    public function setSuperficieTotale(?string $superficieTotale): static
    {
        $this->superficieTotale = $superficieTotale;
        return $this;
    }

    public function getSituationGeographique(): ?string
    {
        return $this->situationGeographique;
    }

    public function setSituationGeographique(?string $situationGeographique): static
    {
        $this->situationGeographique = $situationGeographique;
        return $this;
    }

    public function getPlanLotissement(): ?Fichier
    {
        return $this->planLotissement;
    }

    public function setPlanLotissement(?Fichier $planLotissement): static
    {
        $this->planLotissement = $planLotissement;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getVille(): ?Ville
    {
        return $this->ville;
    }

    public function setVille(?Ville $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function updateEtatAutomatique(): void
    {
        $terrains = $this->getTerrain();
        if ($terrains->count() === 0) {
            return;
        }

        $allVendu = true;
        foreach ($terrains as $t) {
            if ($t->getEtat() !== 'vendu') {
                $allVendu = false;
                break;
            }
        }

        if ($allVendu) {
            $this->setEtat('cloture');
        } elseif ($this->getEtat() === 'cloture') {
            $this->setEtat('disponible');
        }
    }
}
