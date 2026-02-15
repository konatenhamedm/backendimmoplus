<?php

namespace App\Entity;

use App\Repository\FournisseursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FournisseursRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Fournisseurs
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $raisoc = null;

    #[ORM\Column(length: 255)]
    private ?string $contacts = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\OneToMany(mappedBy: 'fournisseur', targetEntity: FactureFournisseur::class)]
    private Collection $facturesFournisseurs;

    #[ORM\OneToMany(mappedBy: 'fournisseur', targetEntity: Reglements::class)]
    private Collection $reglements;

    public function __construct()
    {
        $this->facturesFournisseurs = new ArrayCollection();
        $this->reglements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRaisoc(): ?string
    {
        return $this->raisoc;
    }

    public function setRaisoc(string $raisoc): static
    {
        $this->raisoc = $raisoc;

        return $this;
    }

    public function getContacts(): ?string
    {
        return $this->contacts;
    }

    public function setContacts(string $contacts): static
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection<int, FactureFournisseur>
     */
    public function getFactureFournisseur(): Collection
    {
        return $this->facturesFournisseurs;
    }

    public function addFacturesFournisseur(FactureFournisseur $facturesFournisseur): static
    {
        if (!$this->facturesFournisseurs->contains($facturesFournisseur)) {
            $this->facturesFournisseurs->add($facturesFournisseur);
            $facturesFournisseur->setFournisseur($this);
        }

        return $this;
    }

    public function removeFacturesFournisseur(FactureFournisseur $facturesFournisseur): static
    {
        if ($this->facturesFournisseurs->removeElement($facturesFournisseur)) {
            // set the owning side to null (unless already changed)
            if ($facturesFournisseur->getFournisseur() === $this) {
                $facturesFournisseur->setFournisseur(null);
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
            $reglement->setFournisseur($this);
        }

        return $this;
    }

    public function removeReglement(Reglements $reglement): static
    {
        if ($this->reglements->removeElement($reglement)) {
            // set the owning side to null (unless already changed)
            if ($reglement->getFournisseur() === $this) {
                $reglement->setFournisseur(null);
            }
        }

        return $this;
    }
}
