<?php

namespace App\Entity;

use App\Repository\ModuleAbonnementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModuleAbonnementRepository::class)]
#[UniqueEntity(fields: 'code', message: 'Ce code est déjà associé à un autre module d\'abonnement.')]
class ModuleAbonnement
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $etat = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?string $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?string $montantReel = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?string $duree = null;

    /**
     * @var Collection<int, Abonnement>
     */
    #[ORM\OneToMany(targetEntity: Abonnement::class, mappedBy: 'moduleAbonnement')]
    private Collection $abonnements;

    #[ORM\Column(length: 255)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?string $code = null;

    #[ORM\Column(nullable: true, unique: true)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $numero = null;

    #[ORM\ManyToOne]
    private ?Pays $pays = null;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $maxBiens = 0;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasFacturationAuto = false;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasRelancesAuto = false;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasMobileMoney = false;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasRapportsAvances = false;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasGestionDepenses = false;

    #[ORM\Column(length: 50)]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?string $signatureElectronique = 'NONE'; // NONE, STANDARD, AVANCEE

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasMultiAgences = false;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?bool $hasApiIntegrations = false;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $maxAgences = 1;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $maxEmployes = 10;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $maxLocatairesMobileApp = 40;

    #[ORM\Column]
    #[Groups(["group1", "group_type", "group_abonnement", "group_auth"])]
    private ?int $maxResidences = 0;

    public function __construct()
    {
        $this->abonnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getMontantReel(): ?string
    {
        return $this->montantReel;
    }

    public function setMontantReel(?string $montantReel): static
    {
        $this->montantReel = $montantReel;

        return $this;
    }

    public function getDuree(): ?string
    {
        return $this->duree;
    }

    public function setDuree(string $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    /**
     * @return Collection<int, Abonnement>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): static
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements->add($abonnement);
            $abonnement->setModuleAbonnement($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): static
    {
        if ($this->abonnements->removeElement($abonnement)) {
            // set the owning side to null (unless already changed)
            if ($abonnement->getModuleAbonnement() === $this) {
                $abonnement->setModuleAbonnement(null);
            }
        }

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(?int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getMaxBiens(): ?int
    {
        return $this->maxBiens;
    }

    public function setMaxBiens(int $maxBiens): static
    {
        $this->maxBiens = $maxBiens;

        return $this;
    }

    public function isHasFacturationAuto(): ?bool
    {
        return $this->hasFacturationAuto;
    }

    public function setHasFacturationAuto(bool $hasFacturationAuto): static
    {
        $this->hasFacturationAuto = $hasFacturationAuto;

        return $this;
    }

    public function isHasRelancesAuto(): ?bool
    {
        return $this->hasRelancesAuto;
    }

    public function setHasRelancesAuto(bool $hasRelancesAuto): static
    {
        $this->hasRelancesAuto = $hasRelancesAuto;

        return $this;
    }

    public function isHasMobileMoney(): ?bool
    {
        return $this->hasMobileMoney;
    }

    public function setHasMobileMoney(bool $hasMobileMoney): static
    {
        $this->hasMobileMoney = $hasMobileMoney;

        return $this;
    }

    public function isHasRapportsAvances(): ?bool
    {
        return $this->hasRapportsAvances;
    }

    public function setHasRapportsAvances(bool $hasRapportsAvances): static
    {
        $this->hasRapportsAvances = $hasRapportsAvances;

        return $this;
    }

    public function isHasGestionDepenses(): ?bool
    {
        return $this->hasGestionDepenses;
    }

    public function setHasGestionDepenses(bool $hasGestionDepenses): static
    {
        $this->hasGestionDepenses = $hasGestionDepenses;

        return $this;
    }

    public function getSignatureElectronique(): ?string
    {
        return $this->signatureElectronique;
    }

    public function setSignatureElectronique(string $signatureElectronique): static
    {
        $this->signatureElectronique = $signatureElectronique;

        return $this;
    }

    public function isHasMultiAgences(): ?bool
    {
        return $this->hasMultiAgences;
    }

    public function setHasMultiAgences(bool $hasMultiAgences): static
    {
        $this->hasMultiAgences = $hasMultiAgences;

        return $this;
    }

    public function isHasApiIntegrations(): ?bool
    {
        return $this->hasApiIntegrations;
    }

    public function setHasApiIntegrations(bool $hasApiIntegrations): static
    {
        $this->hasApiIntegrations = $hasApiIntegrations;

        return $this;
    }

    public function getMaxAgences(): ?int
    {
        return $this->maxAgences;
    }

    public function setMaxAgences(int $maxAgences): static
    {
        $this->maxAgences = $maxAgences;

        return $this;
    }

    public function getMaxEmployes(): ?int
    {
        return $this->maxEmployes;
    }

    public function setMaxEmployes(int $maxEmployes): static
    {
        $this->maxEmployes = $maxEmployes;

        return $this;
    }

    public function getMaxLocatairesMobileApp(): ?int
    {
        return $this->maxLocatairesMobileApp;
    }

    public function setMaxLocatairesMobileApp(int $maxLocatairesMobileApp): static
    {
        $this->maxLocatairesMobileApp = $maxLocatairesMobileApp;

        return $this;
    }

    public function getMaxResidences(): ?int
    {
        return $this->maxResidences;
    }

    public function setMaxResidences(int $maxResidences): static
    {
        $this->maxResidences = $maxResidences;

        return $this;
    }
}
