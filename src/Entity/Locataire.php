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
    private ?DateTimeInterface $dateNaiss = null;

    #[ORM\Column(length: 255, name: 'lieuNaiss')]
    #[Assert\NotNull(message: "Le champs  lieu de naissance est requis")]
    #[Groups(['group1'])]
    private ?string $lieuNaiss = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $infoPiece = null;


    #[ORM\Column(length: 255, name: 'profession')]
    #[Assert\NotNull(message: "Le champs profession est requis")]
    #[Groups(['group1'])]
    private ?string $profession = null;

    #[ORM\Column(length: 255, nullable: true, name: 'ethnie')]
    #[Groups(['group1'])]
    private ?string $ethnie = null;

    #[ORM\Column(length: 255, nullable: true, name: 'nbEnfts')]
    #[Groups(['group1'])]
    private ?string $nbEnfts = null;

    #[ORM\Column(length: 255, nullable: true, name: 'nbPersChge')]
    #[Groups(['group1'])]
    private ?string $nbPersChge = null;

    #[ORM\Column(length: 255, nullable: true, name: 'pere')]
    #[Groups(['group1'])]
    private ?string $pere = null;

    #[ORM\Column(length: 255, nullable: true, name: 'mere')]
    #[Groups(['group1'])]
    private ?string $mere = null;

    #[ORM\Column(length: 255, name: 'contacts')]
    #[Assert\NotNull(message: "Le champs contact est requis")]
    #[Groups(['group1'])]
    private ?string $contacts = null;

    #[ORM\Column(length: 255, nullable: true, name: 'email')]
    #[Groups(['group1'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true, name: 'nPConjointe')]
    #[Groups(['group1'])]
    private ?string $nPConjointe = null;

    #[ORM\Column(length: 255, nullable: true, name: 'profConj')]
    #[Groups(['group1'])]
    private ?string $profConj = null;

    #[ORM\Column(length: 255, nullable: true, name: 'ethnieConj')]
    #[Groups(['group1'])]
    private ?string $ethnieConj = null;

    #[ORM\Column(length: 255, nullable: true, name: 'contactConj')]
    #[Groups(['group1'])]
    private ?string $contactConj = null;

    #[ORM\Column(length: 255, name: 'genre')]
    #[Assert\NotNull(message: "Le champs genre est requis")]
    #[Groups(['group1'])]
    private ?string $genre = null;

    #[ORM\Column(length: 255, nullable: true, name: 'vivezAvec')]
    #[Groups(['group1'])]
    private ?string $vivezAvec = null;

    #[ORM\ManyToOne(inversedBy: 'locataires')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?SituationMatrimoniale $situationMatri = null;

    #[ORM\OneToMany(mappedBy: 'locataire', targetEntity: ContratLocation::class)]
    #[Ignore]
    private Collection $contratLocations;


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
        $this->contratLocations = new ArrayCollection();
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
        return $this->dateNaiss;
    }

    public function setDateNaiss(\DateTimeInterface $dateNaiss): static
    {
        $this->dateNaiss = $dateNaiss;

        return $this;
    }

    public function getLieuNaiss(): ?string
    {
        return $this->lieuNaiss;
    }

    public function setLieuNaiss(string $lieuNaiss): static
    {
        $this->lieuNaiss = $lieuNaiss;

        return $this;
    }

    public function getInfoPiece(): ?Fichier
    {
        return $this->infoPiece;
    }

    public function setInfoPiece(Fichier $infoPiece): static
    {
        $this->infoPiece = $infoPiece;

        return $this;
    }



    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getEthnie(): ?string
    {
        return $this->ethnie;
    }

    public function setEthnie(string $ethnie): static
    {
        $this->ethnie = $ethnie;

        return $this;
    }

    public function getNbEnfts(): ?string
    {
        return $this->nbEnfts;
    }

    public function setNbEnfts(string $nbEnfts): static
    {
        $this->nbEnfts = $nbEnfts;

        return $this;
    }

    public function getNbPersChge(): ?string
    {
        return $this->nbPersChge;
    }

    public function setNbPersChge(string $nbPersChge): static
    {
        $this->nbPersChge = $nbPersChge;

        return $this;
    }

    public function getPere(): ?string
    {
        return $this->pere;
    }

    public function setPere(string $pere): static
    {
        $this->pere = $pere;

        return $this;
    }

    public function getMere(): ?string
    {
        return $this->mere;
    }

    public function setMere(string $mere): static
    {
        $this->mere = $mere;

        return $this;
    }

    public function getContacts(): ?string
    {
        return $this->contacts;
    }

    public function setContacts(string $contacts): static
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getNPConjointe(): ?string
    {
        return $this->nPConjointe;
    }

    public function setNPConjointe(string $nPConjointe): static
    {
        $this->nPConjointe = $nPConjointe;

        return $this;
    }

    public function getProfConj(): ?string
    {
        return $this->profConj;
    }

    public function setProfConj(string $profConj): static
    {
        $this->profConj = $profConj;

        return $this;
    }

    public function getEthnieConj(): ?string
    {
        return $this->ethnieConj;
    }

    public function setEthnieConj(string $ethnieConj): static
    {
        $this->ethnieConj = $ethnieConj;

        return $this;
    }

    public function getContactConj(): ?string
    {
        return $this->contactConj;
    }

    public function setContactConj(string $contactConj): static
    {
        $this->contactConj = $contactConj;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getVivezAvec(): ?string
    {
        return $this->vivezAvec;
    }

    public function setVivezAvec(string $vivezAvec): static
    {
        $this->vivezAvec = $vivezAvec;

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
        return $this->contratLocations;
    }

    public function addContratLocation(ContratLocation $ContratLocation): static
    {
        if (!$this->contratLocations->contains($ContratLocation)) {
            $this->contratLocations->add($ContratLocation);
            $ContratLocation->setLocataire($this);
        }

        return $this;
    }

    public function removeContratLocation(ContratLocation $ContratLocation): static
    {
        if ($this->contratLocations->removeElement($ContratLocation)) {
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
