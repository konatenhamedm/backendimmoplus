<?php

namespace App\Entity;

use App\Repository\TypeDepenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TypeDepenseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TypeDepense
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libelle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['group1'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'typeDepense', targetEntity: Depenses::class)]
    private Collection $depenses;

    public function __construct()
    {
        $this->depenses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getDepenses(): Collection
    {
        return $this->depenses;
    }
}
