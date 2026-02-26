<?php

namespace App\Entity;

use App\Repository\RubriquesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RubriquesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Rubriques
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $numCompte = null;

    #[ORM\Column(length: 255)]
    private ?string $libRubrique = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumCompte(): ?string
    {
        return $this->numCompte;
    }

    public function setNumCompte(string $numCompte): static
    {
        $this->numCompte = $numCompte;

        return $this;
    }

    public function getLibRubrique(): ?string
    {
        return $this->libRubrique;
    }

    public function setLibRubrique(string $libRubrique): static
    {
        $this->libRubrique = $libRubrique;

        return $this;
    }
}
