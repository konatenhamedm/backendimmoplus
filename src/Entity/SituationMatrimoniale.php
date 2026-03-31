<?php

namespace App\Entity;

use App\Repository\SituationMatrimonialeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SituationMatrimonialeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SituationMatrimoniale
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $libSituation = null;

    #[ORM\OneToMany(mappedBy: 'situationMatri', targetEntity: Locataire::class)]
    private Collection $locataires;

    public function __construct()
    {
        $this->locataires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibSituation(): ?string
    {
        return $this->libSituation;
    }

    public function setLibSituation(string $libSituation): static
    {
        $this->libSituation = $libSituation;

        return $this;
    }

    /**
     * @return Collection<int, Locataire>
     */
    public function getLocataires(): Collection
    {
        return $this->locataires;
    }

    public function addLocataire(Locataire $locataire): static
    {
        if (!$this->locataires->contains($locataire)) {
            $this->locataires->add($locataire);
            $locataire->setSituationMatri($this);
        }

        return $this;
    }

    public function removeLocataire(Locataire $locataire): static
    {
        if ($this->locataires->removeElement($locataire)) {
            // set the owning side to null (unless already changed)
            if ($locataire->getSituationMatri() === $this) {
                $locataire->setSituationMatri(null);
            }
        }

        return $this;
    }
}
