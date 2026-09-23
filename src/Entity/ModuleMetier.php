<?php

namespace App\Entity;

use App\Repository\ModuleMetierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Grand module métier vendu dans les abonnements (Loyers, Résidences, Terrains…).
 * Regroupe des sections du menu et des routes API : une entreprise n'y a accès que si sa formule l'inclut.
 */
#[ORM\Entity(repositoryClass: ModuleMetierRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ModuleMetier
{
    use TraitEntity;

    public const LOYERS = 'LOYERS';
    public const RESIDENCES = 'RESIDENCES';
    public const TERRAINS = 'TERRAINS';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'group_type', 'group_abonnement', 'group_auth'])]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Groups(['group1', 'group_type', 'group_abonnement', 'group_auth'])]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    #[Groups(['group1', 'group_type', 'group_abonnement', 'group_auth'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Nom d'icône Lucide (ex : Home, Hotel, Map). */
    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['group1', 'group_type', 'group_abonnement', 'group_auth'])]
    private ?string $icone = null;

    #[ORM\Column]
    private int $ordre = 0;

    /** Préfixes des routes API réservées à ce module (ex : /api/residence). */
    #[ORM\Column(type: Types::JSON)]
    private array $prefixesApi = [];

    /** @var Collection<int, Module> sections du menu rattachées à ce module */
    #[ORM\OneToMany(mappedBy: 'moduleMetier', targetEntity: Module::class)]
    private Collection $sections;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getPrefixesApi(): array
    {
        return $this->prefixesApi;
    }

    public function setPrefixesApi(array $prefixes): static
    {
        $prefixes = array_map(fn ($p) => '/' . trim((string) $p, " /"), $prefixes);
        $this->prefixesApi = array_values(array_unique(array_filter($prefixes, fn ($p) => str_starts_with($p, '/api/'))));

        return $this;
    }

    /** @return Collection<int, Module> */
    public function getSections(): Collection
    {
        return $this->sections;
    }
}
