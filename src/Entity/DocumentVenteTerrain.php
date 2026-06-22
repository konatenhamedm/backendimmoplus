<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class DocumentVenteTerrain
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

    #[ORM\ManyToOne(targetEntity: Fichier::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?Fichier $fichier = null;

    #[ORM\ManyToOne(targetEntity: VenteTerrain::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?VenteTerrain $venteTerrain = null;

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getFichier(): ?Fichier { return $this->fichier; }
    public function setFichier(?Fichier $fichier): static { $this->fichier = $fichier; return $this; }

    public function getVenteTerrain(): ?VenteTerrain { return $this->venteTerrain; }
    public function setVenteTerrain(?VenteTerrain $venteTerrain): static { $this->venteTerrain = $venteTerrain; return $this; }
}
