<?php

namespace App\Entity;

use App\Repository\RegimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegimeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Regime
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libRegime = null;

    #[ORM\OneToMany(mappedBy: 'regime', targetEntity: ContratLocation::class)]
    private Collection $contratlocs;

    public function __construct()
    {
        $this->contratlocs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibRegime(): ?string
    {
        return $this->libRegime;
    }

    public function setLibRegime(string $libRegime): static
    {
        $this->libRegime = $libRegime;

        return $this;
    }

    /**
     * @return Collection<int, ContratLocation>
     */
    public function getContratlocs(): Collection
    {
        return $this->contratlocs;
    }

    public function addContratloc(ContratLocation $Contratloc): static
    {
        if (!$this->contratlocs->contains($Contratloc)) {
            $this->contratlocs->add($Contratloc);
            $Contratloc->setRegime($this);
        }

        return $this;
    }

    public function removeContratloc(ContratLocation $Contratloc): static
    {
        if ($this->contratlocs->removeElement($Contratloc)) {
            // set the owning side to null (unless already changed)
            if ($Contratloc->getRegime() === $this) {
                $Contratloc->setRegime(null);
            }
        }

        return $this;
    }
}
