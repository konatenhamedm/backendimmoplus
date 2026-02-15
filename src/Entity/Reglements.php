<?php

namespace App\Entity;

use App\Repository\ReglementsRepository;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ReglementsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Reglements
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reglements')]
    private ?FactureLocation $numFact = null;

    #[ORM\ManyToOne(inversedBy: 'reglements')]
    #[Groups(['group1'])]
    private ?Fournisseurs $fournisseur = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $MontantVerse = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $date = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $numchq = null;

    #[ORM\ManyToOne(inversedBy: 'reglements')]
    #[Groups(['group1'])]
    private ?TypeVersements $typeversement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumFact(): ?FactureLocation
    {
        return $this->numFact;
    }

    public function setNumFact(?FactureLocation $numFact): static
    {
        $this->numFact = $numFact;

        return $this;
    }

    public function getFournisseur(): ?Fournisseurs
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseurs $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    public function getMontantVerse(): ?int
    {
        return $this->MontantVerse;
    }

    public function setMontantVerse(int $MontantVerse): static
    {
        $this->MontantVerse = $MontantVerse;

        return $this;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getNumchq(): ?string
    {
        return $this->numchq;
    }

    public function setNumchq(string $numchq): static
    {
        $this->numchq = $numchq;

        return $this;
    }

    public function getTypeversement(): ?TypeVersements
    {
        return $this->typeversement;
    }

    public function setTypeversement(?TypeVersements $typeversement): static
    {
        $this->typeversement = $typeversement;

        return $this;
    }
}
