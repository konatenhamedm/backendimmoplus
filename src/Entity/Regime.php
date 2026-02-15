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
    private ?string $LibRegime = null;

    #[ORM\OneToMany(mappedBy: 'Regime', targetEntity: ContratLocation::class)]
    private Collection $Contratlocs;

    public function __construct()
    {
        $this->Contratlocs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibRegime(): ?string
    {
        return $this->LibRegime;
    }

    public function setLibRegime(string $LibRegime): static
    {
        $this->LibRegime = $LibRegime;

        return $this;
    }

    /**
     * @return Collection<int, ContratLocation>
     */
    public function getContratlocs(): Collection
    {
        return $this->Contratlocs;
    }

    public function addContratloc(ContratLocation $Contratloc): static
    {
        if (!$this->Contratlocs->contains($Contratloc)) {
            $this->Contratlocs->add($Contratloc);
            $Contratloc->setRegime($this);
        }

        return $this;
    }

    public function removeContratloc(ContratLocation $Contratloc): static
    {
        if ($this->Contratlocs->removeElement($Contratloc)) {
            // set the owning side to null (unless already changed)
            if ($Contratloc->getRegime() === $this) {
                $Contratloc->setRegime(null);
            }
        }

        return $this;
    }
}
