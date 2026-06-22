<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'type_etape_demarche')]
class TypeEtapeDemarche
{
    use TraitEntity;

    // ────────────────────────────────────────────────────────────────
    // Valeurs par défaut (étapes standard pour une démarche foncière)
    // ────────────────────────────────────────────────────────────────
    public const DEFAULTS = [
        ['nom' => 'Lettre d\'Attribution',         'description' => 'Lettre officielle d\'attribution du terrain', 'ordre' => 1],
        ['nom' => 'Compromis de Vente',             'description' => 'Accord préalable entre le vendeur et l\'acheteur', 'ordre' => 2],
        ['nom' => 'Approbation Coutumière',         'description' => 'Validation par les autorités coutumières locales', 'ordre' => 3],
        ['nom' => 'Géomètre / Bornage',             'description' => 'Intervention d\'un géomètre agréé pour délimiter le terrain', 'ordre' => 4],
        ['nom' => 'Dépôt au Ministère',             'description' => 'Constitution et dépôt du dossier au Ministère de la Construction', 'ordre' => 5],
        ['nom' => 'ACD',                            'description' => 'Arrêté de Concession Définitive — Document officiel du Ministère', 'ordre' => 6],
        ['nom' => 'Notaire / Acte de Propriété',   'description' => 'Signature de l\'acte définitif chez le notaire', 'ordre' => 7],
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

    #[ORM\Column(type: 'integer')]
    #[Groups(['group1'])]
    private int $ordre = 0;

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

    public function getOrdre(): int { return $this->ordre; }
    public function setOrdre(int $ordre): static { $this->ordre = $ordre; return $this; }

    public function isActif(): bool { return $this->isActif; }
    public function setIsActif(bool $isActif): static { $this->isActif = $isActif; return $this; }

    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }
}
