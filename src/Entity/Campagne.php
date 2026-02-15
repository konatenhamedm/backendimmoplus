<?php

namespace App\Entity;

use App\Repository\CampagneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CampagneRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Campagne
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    #[Groups(['group1'])]
    private ?string $LibCampagne = null;

    #[ORM\Column]
    private ?int $NbreProprio = null;

    #[ORM\Column]
    private ?int $NbreLocataire = null;

    #[ORM\Column]
    private ?int $MntTotal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $MntPaye = null;

    #[ORM\ManyToOne(inversedBy: 'campagnes')]
    private ?Annee $annee = null;

    #[ORM\ManyToOne(inversedBy: 'campagnes')]
    private ?TabMois $mois = null;

    #[ORM\OneToMany(mappedBy: 'compagne', targetEntity: FactureLocation::class)]
    private Collection $facturelocs;

    #[ORM\ManyToOne(inversedBy: 'campagnes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'campagne', targetEntity: ContratLocation::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $contratlocs;

    #[ORM\OneToMany(mappedBy: 'campagne', targetEntity: CampagneContrat::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $campagneContrats;

    public function __construct()
    {
        $this->facturelocs = new ArrayCollection();
        $this->contratlocs = new ArrayCollection();
        $this->campagneContrats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibCampagne(): ?string
    {
        return $this->LibCampagne;
    }

    public function setLibCampagne(string $LibCampagne): static
    {
        $this->LibCampagne = $LibCampagne;

        return $this;
    }

    public function getNbreProprio(): ?int
    {
        return $this->NbreProprio;
    }

    public function setNbreProprio(int $NbreProprio): static
    {
        $this->NbreProprio = $NbreProprio;

        return $this;
    }

    public function getNbreLocataire(): ?int
    {
        return $this->NbreLocataire;
    }

    public function setNbreLocataire(int $NbreLocataire): static
    {
        $this->NbreLocataire = $NbreLocataire;

        return $this;
    }

    public function getMntTotal(): ?int
    {
        return $this->MntTotal;
    }

    public function setMntTotal(int $MntTotal): static
    {
        $this->MntTotal = $MntTotal;

        return $this;
    }

    public function getMntPaye(): ?string
    {
        return $this->MntPaye;
    }

    public function setMntPaye(string $MntPaye): static
    {
        $this->MntPaye = $MntPaye;

        return $this;
    }

    public function getAnnee(): ?Annee
    {
        return $this->annee;
    }

    public function setAnnee(?Annee $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getMois(): ?TabMois
    {
        return $this->mois;
    }

    public function setMois(?TabMois $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    /**
     * @return Collection<int, FactureLocation>
     */
    public function getFactureLocations(): Collection
    {
        return $this->facturelocs;
    }

    public function addFactureLocation(FactureLocation $factureloc): static
    {
        if (!$this->facturelocs->contains($factureloc)) {
            $this->facturelocs->add($factureloc);
            $factureloc->setCompagne($this);
        }

        return $this;
    }

    public function removeFactureLocation(FactureLocation $factureloc): static
    {
        if ($this->facturelocs->removeElement($factureloc)) {
            // set the owning side to null (unless already changed)
            if ($factureloc->getCompagne() === $this) {
                $factureloc->setCompagne(null);
            }
        }

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

    /**
     * @return Collection<int, ContratLocation>
     */
    public function getContratLocations(): Collection
    {
        return $this->contratlocs;
    }

    public function addContratLocation(ContratLocation $contratloc): static
    {
        if (!$this->contratlocs->contains($contratloc)) {
            $this->contratlocs->add($contratloc);
            $contratloc->setCampagne($this);
        }

        return $this;
    }

    public function removeContratLocation(ContratLocation $contratloc): static
    {
        if ($this->contratlocs->removeElement($contratloc)) {
            // set the owning side to null (unless already changed)
            if ($contratloc->getCampagne() === $this) {
                $contratloc->setCampagne(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CampagneContrat>
     */
    public function getCampagneContrats(): Collection
    {
        return $this->campagneContrats;
    }

    public function addCampagneContrat(CampagneContrat $campagneContrat): static
    {
        if (!$this->campagneContrats->contains($campagneContrat)) {
            $this->campagneContrats->add($campagneContrat);
            $campagneContrat->setCampagne($this);
        }

        return $this;
    }

    public function removeCampagneContrat(CampagneContrat $campagneContrat): static
    {
        if ($this->campagneContrats->removeElement($campagneContrat)) {
            // set the owning side to null (unless already changed)
            if ($campagneContrat->getCampagne() === $this) {
                $campagneContrat->setCampagne(null);
            }
        }

        return $this;
    }

    public function getMontantRestant()
    {
        $somme = 0;

        foreach ($this->facturelocs as $factureloc) {
            $somme += $factureloc->getSoldeFactLoc();
        }

        return $somme;
    }
}
