<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class DemarcheAdministrative
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $statutGlobal = 'en_cours'; // en_cours, termine, annule

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $fraisEstimes = null;

    #[ORM\OneToOne(targetEntity: VenteTerrain::class, inversedBy: 'demarcheAdministrative')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?VenteTerrain $venteTerrain = null;

    #[ORM\OneToMany(mappedBy: 'demarche', targetEntity: EtapeDemarche::class, cascade: ['persist', 'remove'])]
    #[Groups(["group1"])]
    private Collection $etapes;

    public function __construct()
    {
        $this->etapes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getStatutGlobal(): ?string { return $this->statutGlobal; }
    public function setStatutGlobal(string $statutGlobal): static { $this->statutGlobal = $statutGlobal; return $this; }

    public function getFraisEstimes(): ?string { return $this->fraisEstimes; }
    public function setFraisEstimes(?string $fraisEstimes): static { $this->fraisEstimes = $fraisEstimes; return $this; }

    public function getVenteTerrain(): ?VenteTerrain { return $this->venteTerrain; }
    public function setVenteTerrain(?VenteTerrain $venteTerrain): static { $this->venteTerrain = $venteTerrain; return $this; }

    public function getEtapes(): Collection { return $this->etapes; }
    public function addEtape(EtapeDemarche $etape): static {
        if (!$this->etapes->contains($etape)) {
            $this->etapes->add($etape);
            $etape->setDemarche($this);
        }
        return $this;
    }
    public function removeEtape(EtapeDemarche $etape): static {
        if ($this->etapes->removeElement($etape)) {
            if ($etape->getDemarche() === $this) {
                $etape->setDemarche(null);
            }
        }
        return $this;
    }
}
