<?php

namespace App\Entity;

use App\Repository\LocataireRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Nullable;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: LocataireRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Locataire
{
    use TraitEntity;
    #[ORM\OneToOne(mappedBy: 'locataire', cascade: ['persist', 'remove'])]
    #[Ignore]
    private ?User $user = null;

    // ... (rest of the fields)

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $prenoms = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'dateNaiss')]
    #[Assert\NotNull(message: "Le champs date de naissance est requis")]
    #[Groups(['group1'])]
    private ?DateTimeInterface $DateNaiss = null;

    #[ORM\Column(length: 255, name: 'lieuNaiss')]
    #[Assert\NotNull(message: "Le champs  lieu de naissance est requis")]
    #[Groups(['group1'])]
    private ?string $LieuNaiss = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $InfoPiece = null;


    #[ORM\Column(length: 255, name: 'profession')]
    #[Assert\NotNull(message: "Le champs profession est requis")]
    #[Groups(['group1'])]
    private ?string $Profession = null;

    #[ORM\Column(length: 255, nullable: true, name: 'ethnie')]
    #[Groups(['group1'])]
    private ?string $Ethnie = null;

    #[ORM\Column(length: 255, nullable: true, name: 'nbEnfts')]
    #[Groups(['group1'])]
    private ?string $NbEnfts = null;

    #[ORM\Column(length: 255, nullable: true, name: 'nbPersChge')]
    #[Groups(['group1'])]
    private ?string $NbPersChge = null;

    #[ORM\Column(length: 255, nullable: true, name: 'pere')]
    #[Groups(['group1'])]
    private ?string $Pere = null;

    #[ORM\Column(length: 255, nullable: true, name: 'mere')]
    #[Groups(['group1'])]
    private ?string $Mere = null;

    #[ORM\Column(length: 255, name: 'contacts')]
    #[Assert\NotNull(message: "Le champs contact est requis")]
    #[Groups(['group1'])]
    private ?string $Contacts = null;

    #[ORM\Column(length: 255, nullable: true, name: 'email')]
    #[Groups(['group1'])]
    private ?string $Email = null;

    #[ORM\Column(length: 255, nullable: true, name: 'nPConjointe')]
    #[Groups(['group1'])]
    private ?string $NPConjointe = null;

    #[ORM\Column(length: 255, nullable: true, name: 'profConj')]
    #[Groups(['group1'])]
    private ?string $ProfConj = null;

    #[ORM\Column(length: 255, nullable: true, name: 'ethnieConj')]
    #[Groups(['group1'])]
    private ?string $EthnieConj = null;

    #[ORM\Column(length: 255, nullable: true, name: 'contactConj')]
    #[Groups(['group1'])]
    private ?string $ContactConj = null;

    #[ORM\Column(length: 255, name: 'genre')]
    #[Assert\NotNull(message: "Le champs genre est requis")]
    #[Groups(['group1'])]
    private ?string $Genre = null;

    #[ORM\Column(length: 255, nullable: true, name: 'vivezAvec')]
    #[Groups(['group1'])]
    private ?string $VivezAvec = null;

    #[ORM\ManyToOne(inversedBy: 'locataires')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?SituationMatrimoniale $situationMatri = null;

    #[ORM\OneToMany(mappedBy: 'locataire', targetEntity: ContratLocation::class)]
    #[Ignore]
    private Collection $ContratLocations;


    #[ORM\OneToMany(mappedBy: 'locataire', targetEntity: FactureLocation::class)]
    #[Ignore]
    private Collection $facturelocs;

    #[ORM\ManyToOne(inversedBy: 'locataires')]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255, name: 'numpiece')]
    #[Assert\NotNull(message: "Le champs numéro de pièce est requis")]
    #[Groups(['group1'])]
    private ?string $numpiece = null;




    #[ORM\OneToMany(mappedBy: 'locataire', targetEntity: VersmtProprio::class)]
    private Collection $versmtProprios;
    public function __construct()
    {
        $this->ContratLocations = new ArrayCollection();
        $this->facturelocs = new ArrayCollection();
        $this->versmtProprios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNPrenoms(): ?string
    {
        return $this->nom . ' ' . $this->prenoms;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
 
        return $this;
    }

    public function getPrenoms(): ?string
    {
        return $this->prenoms;
    }

    public function setPrenoms(?string $prenoms): static
    {
        $this->prenoms = $prenoms;
     
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenoms;
    }



    public function getDateNaiss(): ?\DateTimeInterface
    {
        return $this->DateNaiss;
    }

    public function setDateNaiss(\DateTimeInterface $DateNaiss): static
    {
        $this->DateNaiss = $DateNaiss;

        return $this;
    }

    public function getLieuNaiss(): ?string
    {
        return $this->LieuNaiss;
    }

    public function setLieuNaiss(string $LieuNaiss): static
    {
        $this->LieuNaiss = $LieuNaiss;

        return $this;
    }

    public function getInfoPiece(): ?Fichier
    {
        return $this->InfoPiece;
    }

    public function setInfoPiece(Fichier $InfoPiece): static
    {
        $this->InfoPiece = $InfoPiece;

        return $this;
    }



    public function getProfession(): ?string
    {
        return $this->Profession;
    }

    public function setProfession(string $Profession): static
    {
        $this->Profession = $Profession;

        return $this;
    }

    public function getEthnie(): ?string
    {
        return $this->Ethnie;
    }

    public function setEthnie(string $Ethnie): static
    {
        $this->Ethnie = $Ethnie;

        return $this;
    }

    public function getNbEnfts(): ?string
    {
        return $this->NbEnfts;
    }

    public function setNbEnfts(string $NbEnfts): static
    {
        $this->NbEnfts = $NbEnfts;

        return $this;
    }

    public function getNbPersChge(): ?string
    {
        return $this->NbPersChge;
    }

    public function setNbPersChge(string $NbPersChge): static
    {
        $this->NbPersChge = $NbPersChge;

        return $this;
    }

    public function getPere(): ?string
    {
        return $this->Pere;
    }

    public function setPere(string $Pere): static
    {
        $this->Pere = $Pere;

        return $this;
    }

    public function getMere(): ?string
    {
        return $this->Mere;
    }

    public function setMere(string $Mere): static
    {
        $this->Mere = $Mere;

        return $this;
    }

    public function getContacts(): ?string
    {
        return $this->Contacts;
    }

    public function setContacts(string $Contacts): static
    {
        $this->Contacts = $Contacts;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(string $Email): static
    {
        $this->Email = $Email;

        return $this;
    }

    public function getNPConjointe(): ?string
    {
        return $this->NPConjointe;
    }

    public function setNPConjointe(string $NPConjointe): static
    {
        $this->NPConjointe = $NPConjointe;

        return $this;
    }

    public function getProfConj(): ?string
    {
        return $this->ProfConj;
    }

    public function setProfConj(string $ProfConj): static
    {
        $this->ProfConj = $ProfConj;

        return $this;
    }

    public function getEthnieConj(): ?string
    {
        return $this->EthnieConj;
    }

    public function setEthnieConj(string $EthnieConj): static
    {
        $this->EthnieConj = $EthnieConj;

        return $this;
    }

    public function getContactConj(): ?string
    {
        return $this->ContactConj;
    }

    public function setContactConj(string $ContactConj): static
    {
        $this->ContactConj = $ContactConj;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->Genre;
    }

    public function setGenre(string $Genre): static
    {
        $this->Genre = $Genre;

        return $this;
    }

    public function getVivezAvec(): ?string
    {
        return $this->VivezAvec;
    }

    public function setVivezAvec(string $VivezAvec): static
    {
        $this->VivezAvec = $VivezAvec;

        return $this;
    }

    public function getSituationMatri(): ?SituationMatrimoniale
    {
        return $this->situationMatri;
    }

    public function setSituationMatri(?SituationMatrimoniale $situationMatri): static
    {
        $this->situationMatri = $situationMatri;

        return $this;
    }

    /**
     * @return Collection<int, ContratLocation>
     */
    public function getContratLocations(): Collection
    {
        return $this->ContratLocations;
    }

    public function addContratLocation(ContratLocation $ContratLocation): static
    {
        if (!$this->ContratLocations->contains($ContratLocation)) {
            $this->ContratLocations->add($ContratLocation);
            $ContratLocation->setLocataire($this);
        }

        return $this;
    }

    public function removeContratLocation(ContratLocation $ContratLocation): static
    {
        if ($this->ContratLocations->removeElement($ContratLocation)) {
            // set the owning side to null (unless already changed)
            if ($ContratLocation->getLocataire() === $this) {
                $ContratLocation->setLocataire(null);
            }
        }

        return $this;
    }

    /**
     */
    /**
     * @return Collection<int, FactureLocation>
     */
    public function getFactureLocations(): Collection
    {
        return $this->facturelocs;
    }

    public function getFacturelocs(): Collection
    {
        return $this->facturelocs;
    }

    public function setFacturelocs(Collection $facturelocs): static
    {
        $this->facturelocs = $facturelocs;

        return $this;
    }

    public function addFactureLocation(FactureLocation $factureloc): static
    {
        if (!$this->facturelocs->contains($factureloc)) {
            $this->facturelocs->add($factureloc);
            $factureloc->setLocataire($this);
        }

        return $this;
    }

    public function removeFactureLocation(FactureLocation $factureloc): static
    {
        if ($this->facturelocs->removeElement($factureloc)) {
            // set the owning side to null (unless already changed)
            if ($factureloc->getLocataire() === $this) {
                $factureloc->setLocataire(null);
            }
        }

        return $this;
    }


    public function getNumpiece(): ?string
    {
        return $this->numpiece;
    }

    public function setNumpiece(string $numpiece): static
    {
        $this->numpiece = $numpiece;

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
            $versmtProprio->setLocataire($this);
        }

        return $this;
    }

    public function removeVersmtProprio(VersmtProprio $versmtProprio): static
    {
        if ($this->versmtProprios->removeElement($versmtProprio)) {
            // set the owning side to null (unless already changed)
            if ($versmtProprio->getLocataire() === $this) {
                $versmtProprio->setLocataire(null);
            }
        }

        return $this;
    }
}
