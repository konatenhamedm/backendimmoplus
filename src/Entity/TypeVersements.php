<?php

namespace App\Entity;

use App\Repository\TypeVersementsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TypeVersementsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TypeVersements
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $libType = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $codTyp = null;

    #[ORM\OneToMany(mappedBy: 'type_versement', targetEntity: VersementProprio::class)]
    private Collection $versmtProprios;

    #[ORM\OneToMany(mappedBy: 'typeversement', targetEntity: Reglements::class)]
    private Collection $reglements;

    public function __construct()
    {
        $this->versmtProprios = new ArrayCollection();
        $this->reglements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibType(): ?string
    {
        return $this->libType;
    }

    public function setLibType(string $libType): static
    {
        $this->libType = $libType;

        return $this;
    }

    public function getCodTyp(): ?string
    {
        return $this->codTyp;
    }

    public function setCodTyp(string $codTyp): static
    {
        $this->codTyp = $codTyp;

        return $this;
    }

    /**
     * @return Collection<int, VersementProprio>
     */
    public function getVersmtProprios(): Collection
    {
        return $this->versmtProprios;
    }

    public function addVersmtProprio(VersementProprio $versmtProprio): static
    {
        if (!$this->versmtProprios->contains($versmtProprio)) {
            $this->versmtProprios->add($versmtProprio);
            $versmtProprio->setTypeVersement($this);
        }

        return $this;
    }

    public function removeVersmtProprio(VersementProprio $versmtProprio): static
    {
        if ($this->versmtProprios->removeElement($versmtProprio)) {
            // set the owning side to null (unless already changed)
            if ($versmtProprio->getTypeVersement() === $this) {
                $versmtProprio->setTypeVersement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reglements>
     */
    public function getReglements(): Collection
    {
        return $this->reglements;
    }

    public function addReglement(Reglements $reglement): static
    {
        if (!$this->reglements->contains($reglement)) {
            $this->reglements->add($reglement);
            $reglement->setTypeversement($this);
        }

        return $this;
    }

    public function removeReglement(Reglements $reglement): static
    {
        if ($this->reglements->removeElement($reglement)) {
            // set the owning side to null (unless already changed)
            if ($reglement->getTypeversement() === $this) {
                $reglement->setTypeversement(null);
            }
        }

        return $this;
    }
}
