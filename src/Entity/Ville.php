<?php

namespace App\Entity;

use App\Repository\VilleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: VilleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ville
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libVille = null;

    #[ORM\Column(length: 255)]
      #[Groups(['group1'])]
    private ?string $abrege_ville = null;

    #[ORM\OneToMany(mappedBy: 'ville', targetEntity: Quartier::class)]
    private Collection $quartiers;

    #[ORM\ManyToOne(inversedBy: 'villes')]
      #[Groups(['group1'])]
    private ?Pays $pays = null;

    public function __construct()
    {
        $this->quartiers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibVille(): ?string
    {
        return $this->libVille;
    }

    public function setLibVille(string $libVille): static
    {
        $this->libVille = $libVille;

        return $this;
    }

    public function getAbregeVille(): ?string
    {
        return $this->abrege_ville;
    }

    public function setAbregeVille(string $abrege_ville): static
    {
        $this->abrege_ville = $abrege_ville;

        return $this;
    }

    /**
     * @return Collection<int, Quartier>
     */
    public function getQuartiers(): Collection
    {
        return $this->quartiers;
    }

    public function addQuartier(Quartier $quartier): static
    {
        if (!$this->quartiers->contains($quartier)) {
            $this->quartiers->add($quartier);
            $quartier->setVille($this);
        }

        return $this;
    }

    public function removeQuartier(Quartier $quartier): static
    {
        if ($this->quartiers->removeElement($quartier)) {
            // set the owning side to null (unless already changed)
            if ($quartier->getVille() === $this) {
                $quartier->setVille(null);
            }
        }

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
}
