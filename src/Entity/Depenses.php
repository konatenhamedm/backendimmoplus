<?php

namespace App\Entity;

use App\Repository\DepensesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepensesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Depenses
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libDepense = null;

    #[ORM\Column]
    private ?int $montantTTC = null;

    #[ORM\Column(length: 255)]
    private ?string $date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $details = null;

    #[ORM\Column(length: 255)]
    private ?string $scan = null;

    #[ORM\OneToMany(mappedBy: 'depenses', targetEntity: LigneDepense::class)]
    private Collection $ligneDepenses;

    public function __construct()
    {
        $this->ligneDepenses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibDepense(): ?string
    {
        return $this->libDepense;
    }

    public function setLibDepense(string $libDepense): static
    {
        $this->libDepense = $libDepense;

        return $this;
    }

    public function getMontantTTC(): ?int
    {
        return $this->montantTTC;
    }

    public function setMontantTTC(int $montantTTC): static
    {
        $this->montantTTC = $montantTTC;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getScan(): ?string
    {
        return $this->scan;
    }

    public function setScan(string $scan): static
    {
        $this->scan = $scan;

        return $this;
    }

    /**
     * @return Collection<int, LigneDepense>
     */
    public function getLigneDepenses(): Collection
    {
        return $this->ligneDepenses;
    }

    public function addLigneDepense(LigneDepense $ligneDepense): static
    {
        if (!$this->ligneDepenses->contains($ligneDepense)) {
            $this->ligneDepenses->add($ligneDepense);
            $ligneDepense->setDepenses($this);
        }

        return $this;
    }

    public function removeLigneDepense(LigneDepense $ligneDepense): static
    {
        if ($this->ligneDepenses->removeElement($ligneDepense)) {
            // set the owning side to null (unless already changed)
            if ($ligneDepense->getDepenses() === $this) {
                $ligneDepense->setDepenses(null);
            }
        }

        return $this;
    }
}
