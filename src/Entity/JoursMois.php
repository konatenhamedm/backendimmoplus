<?php

namespace App\Entity;

use App\Repository\JoursMoisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JoursMoisRepository::class)]
#[ORM\HasLifecycleCallbacks]
class JoursMois
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $libelle = null;

    #[ORM\OneToMany(mappedBy: 'joursMois', targetEntity: JoursMoisEntreprise::class)]
    private Collection $joursMoisEntreprises;

    public function __construct()
    {
        $this->joursMoisEntreprises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?int
    {
        return $this->libelle;
    }

    public function setLibelle(int $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, JoursMoisEntreprise>
     */
    public function getJoursMoisEntreprises(): Collection
    {
        return $this->joursMoisEntreprises;
    }

    public function addJoursMoisEntreprise(JoursMoisEntreprise $joursMoisEntreprise): static
    {
        if (!$this->joursMoisEntreprises->contains($joursMoisEntreprise)) {
            $this->joursMoisEntreprises->add($joursMoisEntreprise);
            $joursMoisEntreprise->setJoursMois($this);
        }

        return $this;
    }

    public function removeJoursMoisEntreprise(JoursMoisEntreprise $joursMoisEntreprise): static
    {
        if ($this->joursMoisEntreprises->removeElement($joursMoisEntreprise)) {
            // set the owning side to null (unless already changed)
            if ($joursMoisEntreprise->getJoursMois() === $this) {
                $joursMoisEntreprise->setJoursMois(null);
            }
        }

        return $this;
    }
}
