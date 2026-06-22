<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class VersementTerrain
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["group1"])]
    private ?\DateTimeInterface $dateVersement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $modePaiement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: VenteTerrain::class, inversedBy: 'versements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?VenteTerrain $venteTerrain = null;

    public function __construct()
    {
        $this->dateVersement = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getMontant(): ?string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }

    public function getDateVersement(): ?\DateTimeInterface { return $this->dateVersement; }
    public function setDateVersement(\DateTimeInterface $dateVersement): static { $this->dateVersement = $dateVersement; return $this; }

    public function getModePaiement(): ?string { return $this->modePaiement; }
    public function setModePaiement(?string $modePaiement): static { $this->modePaiement = $modePaiement; return $this; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): static { $this->reference = $reference; return $this; }

    public function getVenteTerrain(): ?VenteTerrain { return $this->venteTerrain; }
    public function setVenteTerrain(?VenteTerrain $venteTerrain): static { $this->venteTerrain = $venteTerrain; return $this; }
}
