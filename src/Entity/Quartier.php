<?php

namespace App\Entity;

use App\Repository\QuartierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuartierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Quartier
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $libQuartier = null;


    #[ORM\ManyToOne(inversedBy: 'quartiers')]
    #[Groups(['group1'])]
    private ?Ville $ville = null;

    #[ORM\OneToMany(mappedBy: 'quartier', targetEntity: Maison::class)]
    private Collection $quartierMaisons;

    #[ORM\ManyToOne(inversedBy: 'quartiers')]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Agence $agence = null;

    public function __construct()
    {
        $this->quartierMaisons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibQuartier(): ?string
    {
        return $this->libQuartier;
    }

    public function setLibQuartier(string $libQuartier): static
    {
        $this->libQuartier = $libQuartier;

        return $this;
    }


    public function getVille(): ?Ville
    {
        return $this->ville;
    }

    public function setVille(?Ville $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    /**
     * @return Collection<int, Maison>
     */
    public function getQuartierMaisons(): Collection
    {
        return $this->quartierMaisons;
    }

    public function addQuartierMaison(Maison $quartierMaison): static
    {
        if (!$this->quartierMaisons->contains($quartierMaison)) {
            $this->quartierMaisons->add($quartierMaison);
            $quartierMaison->setQuartier($this);
        }

        return $this;
    }

    public function removeQuartierMaison(Maison $quartierMaison): static
    {
        if ($this->quartierMaisons->removeElement($quartierMaison)) {
            // set the owning side to null (unless already changed)
            if ($quartierMaison->getQuartier() === $this) {
                $quartierMaison->setQuartier(null);
            }
        }

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;

        return $this;
    }
}
