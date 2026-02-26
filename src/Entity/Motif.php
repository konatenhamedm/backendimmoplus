<?php

namespace App\Entity;

use App\Repository\MotifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MotifRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Motif
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libMotif = null;

    #[ORM\OneToMany(mappedBy: 'motif', targetEntity: Fincontrat::class)]
    private Collection $fincontrats;

    #[ORM\OneToMany(mappedBy: 'motif', targetEntity: ContratLocation::class)]
    private Collection $contratlocs;

    public function __construct()
    {
        $this->fincontrats = new ArrayCollection();
        $this->contratlocs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibMotif(): ?string
    {
        return $this->libMotif;
    }

    public function setLibMotif(string $libMotif): static
    {
        $this->libMotif = $libMotif;

        return $this;
    }

    /**
     * @return Collection<int, Fincontrat>
     */
    public function getFincontrats(): Collection
    {
        return $this->fincontrats;
    }

    public function addFincontrat(Fincontrat $fincontrat): static
    {
        if (!$this->fincontrats->contains($fincontrat)) {
            $this->fincontrats->add($fincontrat);
            $fincontrat->setMotif($this);
        }

        return $this;
    }

    public function removeFincontrat(Fincontrat $fincontrat): static
    {
        if ($this->fincontrats->removeElement($fincontrat)) {
            // set the owning side to null (unless already changed)
            if ($fincontrat->getMotif() === $this) {
                $fincontrat->setMotif(null);
            }
        }

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
            $contratloc->setMotif($this);
        }

        return $this;
    }

    public function removeContratLocation(ContratLocation $contratloc): static
    {
        if ($this->contratlocs->removeElement($contratloc)) {
            // set the owning side to null (unless already changed)
            if ($contratloc->getMotif() === $this) {
                $contratloc->setMotif(null);
            }
        }

        return $this;
    }
}
