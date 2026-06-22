<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class EchancierTerrain
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["group1"])]
    private ?\DateTimeInterface $datePrevue = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $etat = 'en_attente'; // en_attente, paye

    #[ORM\ManyToOne(targetEntity: VenteTerrain::class, inversedBy: 'echanciers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?VenteTerrain $venteTerrain = null;

    public function getId(): ?int { return $this->id; }

    public function getDatePrevue(): ?\DateTimeInterface { return $this->datePrevue; }
    public function setDatePrevue(\DateTimeInterface $datePrevue): static { $this->datePrevue = $datePrevue; return $this; }

    public function getMontant(): ?string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }

    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(string $etat): static { $this->etat = $etat; return $this; }

    public function getVenteTerrain(): ?VenteTerrain { return $this->venteTerrain; }
    public function setVenteTerrain(?VenteTerrain $venteTerrain): static { $this->venteTerrain = $venteTerrain; return $this; }
}
