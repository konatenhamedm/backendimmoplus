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
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, name: 'libMaison')]
    #[Assert\NotNull(message: "Le champs libelle est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $libMaison = null;

    #[ORM\Column(length: 255, nullable: true, name: 'localisation')]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, name: 'lot')]
    #[Assert\NotNull(message: "Le champs Lot est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $lot = null;

    #[ORM\Column(length: 255, name: 'ilot')]
    #[Assert\NotNull(message: "Le champs Ilot est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $ilot = null;

    #[ORM\Column(length: 255, nullable: true, name: 'tFoncier')]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $tFoncier = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', nullable: false, name: 'mntCom')]
    #[Assert\NotNull(message: "Le champs commission est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $mntCom = null;

    #[ORM\OneToMany(mappedBy: 'maisson', targetEntity: Appartement::class, orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['group1'])]
    private Collection $appartements;

    #[ORM\ManyToOne(inversedBy: 'quartierMaisons')]
    #[Assert\NotNull(message: "Le champs quartier est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?Quartier $quartier = null;

    #[ORM\ManyToOne(inversedBy: 'proprioMaisons')]
    #[Assert\NotNull(message: "Le champs proprio est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?Proprio $proprio = null;

    #[ORM\ManyToOne(inversedBy: 'typeMaisonMaisons')]
    #[Assert\NotNull(message: "Le champs type maison est requis")]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?TypeMaison $typeMaison = null;

    #[ORM\ManyToOne(inversedBy: 'maisons')]
   #[ORM\JoinColumn(nullable: true)]
     /* #[Gedmo\Blameable(on: 'create')]*/
    #[Groups(['group1', 'appartement-groupe'])]
    private ?User $idAgent = null;

    #[ORM\OneToMany(mappedBy: 'maison', targetEntity: VersmtProprio::class)]
    private Collection $versmtProprios;

    /* #[ORM\ManyToOne(inversedBy: 'maisons')]
    private ?Quartier $quartier = null;

    #[ORM\ManyToOne(inversedBy: 'maisons2')]
    private ?Proprio $proprio = null;

    #[ORM\ManyToOne(inversedBy: 'maisons3')]
    private ?TypeMaison $typeMaison = null; */

    
    #[ORM\ManyToOne(targetEntity: Agence::class)] // reused same mappedBy vaguely or no inversedBy
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?Agence $agence = null;

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
        return $this->libMaison;
    }

    public function setLibMaison(string $libMaison): static
    {
        $this->libMaison = $libMaison;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getLot(): ?string
    {
        return $this->lot;
    }

    public function setLot(string $lot): static
    {
        $this->lot = $lot;

        return $this;
    }

    public function getIlot(): ?string
    {
        return $this->ilot;
    }

    public function setIlot(string $ilot): static
    {
        $this->ilot = $ilot;

        return $this;
    }

    public function getTFoncier(): ?string
    {
        return $this->tFoncier;
    }

    public function setTFoncier(string $tFoncier): static
    {
        $this->tFoncier = $tFoncier;

        return $this;
    }

    public function getMntCom(): ?string
    {
        return $this->mntCom;
    }

    public function setMntCom(string $mntCom): static
    {
        $this->mntCom = $mntCom;

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
        return $this->idAgent;
    }

    public function setIdAgent(?User $idAgent): static
    {
        $this->idAgent = $idAgent;

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


    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;

        return $this;
    }

}
