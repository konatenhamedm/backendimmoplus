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
    private ?string $LibDepense = null;

    #[ORM\Column]
    private ?int $MontantTTC = null;

    #[ORM\Column(length: 255)]
    private ?string $Date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $Details = null;

    #[ORM\Column(length: 255)]
    private ?string $Scan = null;

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
        return $this->LibDepense;
    }

    public function setLibDepense(string $LibDepense): static
    {
        $this->LibDepense = $LibDepense;

        return $this;
    }

    public function getMontantTTC(): ?int
    {
        return $this->MontantTTC;
    }

    public function setMontantTTC(int $MontantTTC): static
    {
        $this->MontantTTC = $MontantTTC;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->Date;
    }

    public function setDate(string $Date): static
    {
        $this->Date = $Date;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->Details;
    }

    public function setDetails(string $Details): static
    {
        $this->Details = $Details;

        return $this;
    }

    public function getScan(): ?string
    {
        return $this->Scan;
    }

    public function setScan(string $Scan): static
    {
        $this->Scan = $Scan;

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
