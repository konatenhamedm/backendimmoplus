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
    private ?string $NumCompte = null;

    #[ORM\Column(length: 255)]
    private ?string $LibRubrique = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumCompte(): ?string
    {
        return $this->NumCompte;
    }

    public function setNumCompte(string $NumCompte): static
    {
        $this->NumCompte = $NumCompte;

        return $this;
    }

    public function getLibRubrique(): ?string
    {
        return $this->LibRubrique;
    }

    public function setLibRubrique(string $LibRubrique): static
    {
        $this->LibRubrique = $LibRubrique;

        return $this;
    }
}
