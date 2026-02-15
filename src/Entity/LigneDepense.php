<?php

namespace App\Entity;

use App\Repository\LigneDepenseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneDepenseRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LigneDepense
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $Montant = null;

    #[ORM\ManyToOne(inversedBy: 'ligneDepenses')]
    private ?depenses $depenses = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?int
    {
        return $this->Montant;
    }

    public function setMontant(int $Montant): static
    {
        $this->Montant = $Montant;

        return $this;
    }

    public function getDepenses(): ?depenses
    {
        return $this->depenses;
    }

    public function setDepenses(?depenses $depenses): static
    {
        $this->depenses = $depenses;

        return $this;
    }
}
