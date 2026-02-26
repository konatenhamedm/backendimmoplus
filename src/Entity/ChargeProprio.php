<?php

namespace App\Entity;

use App\Repository\ChargeProprioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChargeProprioRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChargeProprio
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'chargeProprios')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Proprio $proprio = null;

    #[ORM\ManyToOne]
    #[Groups(['group1'])]
    private ?Maison $maison = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Groups(['group1'])]
    private ?string $montant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateCharge = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['group1'])]
    private ?string $details = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['group1'])]
    private bool $isValidated = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $scan = null;

    #[ORM\ManyToOne]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'chargeProprio', targetEntity: ChargeAppartement::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['group1'])]
    private Collection $chargeAppartements;

    public function __construct()
    {
        $this->chargeAppartements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProprio(): ?Proprio
    {
        return $this->proprio;
    }

    public function setProprio(?Proprio $proprio): static
    {
        $this->proprio = $proprio;

        return $this;
    }

    public function getMaison(): ?Maison
    {
        return $this->maison;
    }

    public function setMaison(?Maison $maison): static
    {
        $this->maison = $maison;

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

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDateCharge(): ?\DateTimeInterface
    {
        return $this->dateCharge;
    }

    public function setDateCharge(\DateTimeInterface $dateCharge): static
    {
        $this->dateCharge = $dateCharge;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getScan(): ?Fichier
    {
        return $this->scan;
    }

    public function setScan(?Fichier $scan): static
    {
        $this->scan = $scan;

        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    /**
     * @return Collection<int, ChargeAppartement>
     */
    public function getChargeAppartements(): Collection
    {
        return $this->chargeAppartements;
    }

    public function addChargeAppartement(ChargeAppartement $chargeAppartement): static
    {
        if (!$this->chargeAppartements->contains($chargeAppartement)) {
            $this->chargeAppartements->add($chargeAppartement);
            $chargeAppartement->setChargeProprio($this);
        }

        return $this;
    }

    public function removeChargeAppartement(ChargeAppartement $chargeAppartement): static
    {
        if ($this->chargeAppartements->removeElement($chargeAppartement)) {
            // set the owning side to null (unless already changed)
            if ($chargeAppartement->getChargeProprio() === $this) {
                $chargeAppartement->setChargeProprio(null);
            }
        }

        return $this;
    }

    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): static
    {
        $this->isValidated = $isValidated;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }
}
