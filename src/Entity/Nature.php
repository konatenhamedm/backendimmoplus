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
    private ?string $LibNature = null;

    #[ORM\OneToMany(mappedBy: 'Nature', targetEntity: ContratLocation::class)]
    private Collection $ContratLocations;

    public function __construct()
    {
        $this->ContratLocations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibNature(): ?string
    {
        return $this->LibNature;
    }

    public function setLibNature(string $LibNature): static
    {
        $this->LibNature = $LibNature;

        return $this;
    }

    /**
     * @return Collection<int, ContratLocation>
     */
    public function getContratLocations(): Collection
    {
        return $this->ContratLocations;
    }

    public function addContratLocation(ContratLocation $ContratLocation): static
    {
        if (!$this->ContratLocations->contains($ContratLocation)) {
            $this->ContratLocations->add($ContratLocation);
            $ContratLocation->setNature($this);
        }

        return $this;
    }

    public function removeContratLocation(ContratLocation $ContratLocation): static
    {
        if ($this->ContratLocations->removeElement($ContratLocation)) {
            // set the owning side to null (unless already changed)
            if ($ContratLocation->getNature() === $this) {
                $ContratLocation->setNature(null);
            }
        }

        return $this;
    }
}
