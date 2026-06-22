<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'type_frais_terrain')]
class TypeFraisTerrain
{
    use TraitEntity;

    // ────────────────────────────────────────────────────────────────
    // Valeurs par défaut (types de frais standard pour démarche foncière)
    // ────────────────────────────────────────────────────────────────
    public const DEFAULTS = [
        ['nom' => 'Frais de Géomètre',                  'description' => 'Honoraires du géomètre agréé pour le bornage du terrain', 'montantDefaut' => '0'],
        ['nom' => 'Frais de Notaire',                   'description' => 'Honoraires du notaire pour la rédaction de l\'acte de propriété', 'montantDefaut' => '0'],
        ['nom' => 'Approbation Coutumière',             'description' => 'Frais liés aux rites et accords coutumiers locaux', 'montantDefaut' => '0'],
        ['nom' => 'Frais de Dépôt Ministère',           'description' => 'Droits de timbre et frais administratifs au Ministère', 'montantDefaut' => '0'],
        ['nom' => 'Timbres et Droits d\'enregistrement','description' => 'Taxes et droits d\'enregistrement légaux', 'montantDefaut' => '0'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['group1'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $montantDefaut = '0';

    #[ORM\Column(type: 'boolean')]
    #[Groups(['group1'])]
    private bool $isActif = true;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getMontantDefaut(): ?string { return $this->montantDefaut; }
    public function setMontantDefaut(?string $montantDefaut): static { $this->montantDefaut = $montantDefaut; return $this; }

    public function isActif(): bool { return $this->isActif; }
    public function setIsActif(bool $isActif): static { $this->isActif = $isActif; return $this; }

    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }

    #[ORM\ManyToOne(targetEntity: TypeEtapeDemarche::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?TypeEtapeDemarche $typeEtapeDemarche = null;

    public function getTypeEtapeDemarche(): ?TypeEtapeDemarche { return $this->typeEtapeDemarche; }
    public function setTypeEtapeDemarche(?TypeEtapeDemarche $typeEtapeDemarche): static { $this->typeEtapeDemarche = $typeEtapeDemarche; return $this; }
}
