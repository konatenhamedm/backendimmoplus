<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'frais_vente_terrain')]
#[ORM\HasLifecycleCallbacks]
class FraisVenteTerrain
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: VenteTerrain::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['group1'])]
    private ?VenteTerrain $venteTerrain = null;

    #[ORM\ManyToOne(targetEntity: TypeFraisTerrain::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?TypeFraisTerrain $typeFrais = null;

    #[ORM\ManyToOne(targetEntity: EtapeDemarche::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['group1'])]
    private ?EtapeDemarche $etapeDemarche = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    #[Groups(['group1'])]
    private ?string $montant = '0';

    #[ORM\Column(length: 50)]
    #[Groups(['group1'])]
    private string $statutPaiement = 'non_paye'; // 'non_paye', 'partiellement_paye', 'paye', 'annule'

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    public function getId(): ?int { return $this->id; }

    public function getVenteTerrain(): ?VenteTerrain { return $this->venteTerrain; }
    public function setVenteTerrain(?VenteTerrain $venteTerrain): static { $this->venteTerrain = $venteTerrain; return $this; }

    public function getTypeFrais(): ?TypeFraisTerrain { return $this->typeFrais; }
    public function setTypeFrais(?TypeFraisTerrain $typeFrais): static { $this->typeFrais = $typeFrais; return $this; }

    public function getEtapeDemarche(): ?EtapeDemarche { return $this->etapeDemarche; }
    public function setEtapeDemarche(?EtapeDemarche $etapeDemarche): static { $this->etapeDemarche = $etapeDemarche; return $this; }

    public function getMontant(): ?string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }

    public function getStatutPaiement(): string { return $this->statutPaiement; }
    public function setStatutPaiement(string $statutPaiement): static { $this->statutPaiement = $statutPaiement; return $this; }

    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }
}
