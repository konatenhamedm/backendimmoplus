<?php

namespace App\Entity;

use App\Repository\NatureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NatureRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Nature
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libNature = null;

    #[ORM\OneToMany(mappedBy: 'nature', targetEntity: ContratLocation::class)]
    private Collection $contratLocations;

    public function __construct()
    {
        $this->contratLocations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibNature(): ?string
    {
        return $this->libNature;
    }

    public function setLibNature(string $libNature): static
    {
        $this->libNature = $libNature;

        return $this;
    }

    /**
     * @return Collection<int, ContratLocation>
     */
    public function getContratLocations(): Collection
    {
        return $this->contratLocations;
    }

    public function addContratLocation(ContratLocation $ContratLocation): static
    {
        if (!$this->contratLocations->contains($ContratLocation)) {
            $this->contratLocations->add($ContratLocation);
            $ContratLocation->setNature($this);
        }

        return $this;
    }

    public function removeContratLocation(ContratLocation $ContratLocation): static
    {
        if ($this->contratLocations->removeElement($ContratLocation)) {
            // set the owning side to null (unless already changed)
            if ($ContratLocation->getNature() === $this) {
                $ContratLocation->setNature(null);
            }
        }

        return $this;
    }
}
