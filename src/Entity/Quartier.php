<?php

namespace App\Entity;

use App\Repository\QuartierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuartierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Quartier
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $LibQuartier = null;

    #[ORM\OneToMany(mappedBy: 'quartier', targetEntity: Maison::class)]
    private Collection $maisons;

    #[ORM\ManyToOne(inversedBy: 'quartiers')]
    #[Groups(['group1'])]
    private ?Ville $ville = null;

    #[ORM\OneToMany(mappedBy: 'quartier', targetEntity: Maison::class)]
    private Collection $quartierMaisons;

    #[ORM\ManyToOne(inversedBy: 'quartiers')]
    private ?Entreprise $entreprise = null;

    public function __construct()
    {
        $this->maisons = new ArrayCollection();
        $this->quartierMaisons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibQuartier(): ?string
    {
        return $this->LibQuartier;
    }

    public function setLibQuartier(string $LibQuartier): static
    {
        $this->LibQuartier = $LibQuartier;

        return $this;
    }

    /**
     * @return Collection<int, Maison>
     */
    public function getMaisons(): Collection
    {
        return $this->maisons;
    }

    public function addMaison(Maison $maison): static
    {
        if (!$this->maisons->contains($maison)) {
            $this->maisons->add($maison);
            $maison->setQuartier($this);
        }

        return $this;
    }

    public function removeMaison(Maison $maison): static
    {
        if ($this->maisons->removeElement($maison)) {
            // set the owning side to null (unless already changed)
            if ($maison->getQuartier() === $this) {
                $maison->setQuartier(null);
            }
        }

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

    /**
     * @return Collection<int, Maison>
     */
    public function getQuartierMaisons(): Collection
    {
        return $this->quartierMaisons;
    }

    public function addQuartierMaison(Maison $quartierMaison): static
    {
        if (!$this->quartierMaisons->contains($quartierMaison)) {
            $this->quartierMaisons->add($quartierMaison);
            $quartierMaison->setQuartier($this);
        }

        return $this;
    }

    public function removeQuartierMaison(Maison $quartierMaison): static
    {
        if ($this->quartierMaisons->removeElement($quartierMaison)) {
            // set the owning side to null (unless already changed)
            if ($quartierMaison->getQuartier() === $this) {
                $quartierMaison->setQuartier(null);
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
}
