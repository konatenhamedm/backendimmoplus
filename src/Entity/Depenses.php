<?php

namespace App\Entity;

use App\Repository\DepensesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: DepensesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Depenses
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libDepense = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['group1'])]
    private ?int $montantTTC = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['group1'])]
    private ?string $details = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?Fichier $scan = null;

    #[ORM\OneToMany(mappedBy: 'depenses', targetEntity: LigneDepense::class)]
    private Collection $ligneDepenses;

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(targetEntity: TypeDepense::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?TypeDepense $typeDepense = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

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

    public function setMontantTTC(?int $montantTTC): static
    {
        $this->montantTTC = $montantTTC;
        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getScan(): ?Fichier
    {
        return $this->scan;
    }

    public function setScan(?Fichier $scan): static
    {
        $this->scan = $scan;
        return $this;
    }

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
            if ($ligneDepense->getDepenses() === $this) {
                $ligneDepense->setDepenses(null);
            }
        }
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

    public function getTypeDepense(): ?TypeDepense
    {
        return $this->typeDepense;
    }

    public function setTypeDepense(?TypeDepense $typeDepense): static
    {
        $this->typeDepense = $typeDepense;
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
}
