<?php

namespace App\Entity;

use App\Repository\MaisonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MaisonRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Maison
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, name: 'libMaison')]
    #[Assert\NotNull(message: "Le champs libelle est requis")]
    #[Groups(['group1'])]
    private ?string $LibMaison = null;

    #[ORM\Column(length: 255, nullable: true, name: 'localisation')]
    #[Groups(['group1'])]
    private ?string $Localisation = null;

    #[ORM\Column(length: 255, name: 'lot')]
    #[Assert\NotNull(message: "Le champs Lot est requis")]
    #[Groups(['group1'])]
    private ?string $Lot = null;

    #[ORM\Column(length: 255, name: 'ilot')]
    #[Assert\NotNull(message: "Le champs Ilot est requis")]
    #[Groups(['group1'])]
    private ?string $Ilot = null;

    #[ORM\Column(length: 255, nullable: true, name: 'tFoncier')]
    #[Groups(['group1'])]
    private ?string $TFoncier = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: false, name: 'mntCom')]
    #[Assert\NotNull(message: "Le champs commission est requis")]
    #[Groups(['group1'])]
    private ?int $MntCom = null;

    #[ORM\OneToMany(mappedBy: 'maisson', targetEntity: Appartement::class, orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['group1'])]
    private Collection $appartements;

    #[ORM\ManyToOne(inversedBy: 'quartierMaisons')]
    #[Assert\NotNull(message: "Le champs quartier est requis")]
    #[Groups(['group1'])]
    private ?Quartier $quartier = null;

    #[ORM\ManyToOne(inversedBy: 'proprioMaisons')]
    #[Assert\NotNull(message: "Le champs proprio est requis")]
    #[Groups(['group1'])]
    private ?Proprio $proprio = null;

    #[ORM\ManyToOne(inversedBy: 'typeMaisonMaisons')]
    #[Assert\NotNull(message: "Le champs type maison est requis")]
    #[Groups(['group1'])]
    private ?TypeMaison $typeMaison = null;

    #[ORM\ManyToOne(inversedBy: 'maisons')]
    /* #[ORM\JoinColumn(nullable: false)]
    #[Gedmo\Blameable(on: 'create')]*/
    #[Groups(['group1'])]
    private ?User $IdAgent = null;

    #[ORM\OneToMany(mappedBy: 'maison', targetEntity: VersmtProprio::class)]
    private Collection $versmtProprios;

    /* #[ORM\ManyToOne(inversedBy: 'maisons')]
    private ?Quartier $quartier = null;

    #[ORM\ManyToOne(inversedBy: 'maisons2')]
    private ?Proprio $Proprio = null;

    #[ORM\ManyToOne(inversedBy: 'maisons3')]
    private ?TypeMaison $TypeMaison = null; */

    public function __construct()
    {
        $this->appartements = new ArrayCollection();
        $this->versmtProprios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibMaison(): ?string
    {
        return $this->LibMaison;
    }

    public function setLibMaison(string $LibMaison): static
    {
        $this->LibMaison = $LibMaison;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->Localisation;
    }

    public function setLocalisation(string $Localisation): static
    {
        $this->Localisation = $Localisation;

        return $this;
    }

    public function getLot(): ?string
    {
        return $this->Lot;
    }

    public function setLot(string $Lot): static
    {
        $this->Lot = $Lot;

        return $this;
    }

    public function getIlot(): ?string
    {
        return $this->Ilot;
    }

    public function setIlot(string $Ilot): static
    {
        $this->Ilot = $Ilot;

        return $this;
    }

    public function getTFoncier(): ?string
    {
        return $this->TFoncier;
    }

    public function setTFoncier(string $TFoncier): static
    {
        $this->TFoncier = $TFoncier;

        return $this;
    }

    public function getMntCom(): ?int
    {
        return $this->MntCom;
    }

    public function setMntCom(int $MntCom): static
    {
        $this->MntCom = $MntCom;

        return $this;
    }

    /**
     * @return Collection<int, Appartement>
     */
    public function getAppartements(): Collection
    {
        return $this->appartements;
    }

    public function addAppartement(Appartement $appartement): static
    {
        if (!$this->appartements->contains($appartement)) {
            $this->appartements->add($appartement);
            $appartement->setMaisson($this);
        }

        return $this;
    }

    public function removeAppartement(Appartement $appartement): static
    {
        if ($this->appartements->removeElement($appartement)) {
            // set the owning side to null (unless already changed)
            if ($appartement->getMaisson() === $this) {
                $appartement->setMaisson(null);
            }
        }

        return $this;
    }


    /* public function getQuartier(): ?Quartier
    {
        return $this->quartier;
    }

    public function setQuartier(?Quartier $quartier): static
    {
        $this->quartier = $quartier;

        return $this;
    }

    public function getProprio(): ?Proprio
    {
        return $this->Proprio;
    }

    public function setProprio(?Proprio $Proprio): static
    {
        $this->Proprio = $Proprio;

        return $this;
    }

    public function getTypeMaison(): ?TypeMaison
    {
        return $this->TypeMaison;
    }

    public function setTypeMaison(?TypeMaison $TypeMaison): static
    {
        $this->TypeMaison = $TypeMaison;

        return $this;
    } */

    public function getQuartier(): ?Quartier
    {
        return $this->quartier;
    }

    public function setQuartier(?Quartier $quartier): static
    {
        $this->quartier = $quartier;

        return $this;
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

    public function getTypeMaison(): ?TypeMaison
    {
        return $this->typeMaison;
    }

    public function setTypeMaison(?TypeMaison $typeMaison): static
    {
        $this->typeMaison = $typeMaison;

        return $this;
    }

    public function getIdAgent(): ?User
    {
        return $this->IdAgent;
    }

    public function setIdAgent(?User $IdAgent): static
    {
        $this->IdAgent = $IdAgent;

        return $this;
    }

    /**
     * @return Collection<int, VersmtProprio>
     */
    public function getVersmtProprios(): Collection
    {
        return $this->versmtProprios;
    }

    public function addVersmtProprio(VersmtProprio $versmtProprio): static
    {
        if (!$this->versmtProprios->contains($versmtProprio)) {
            $this->versmtProprios->add($versmtProprio);
            $versmtProprio->setMaison($this);
        }

        return $this;
    }

    public function removeVersmtProprio(VersmtProprio $versmtProprio): static
    {
        if ($this->versmtProprios->removeElement($versmtProprio)) {
            // set the owning side to null (unless already changed)
            if ($versmtProprio->getMaison() === $this) {
                $versmtProprio->setMaison(null);
            }
        }

        return $this;
    }
}
