<?php

namespace App\Entity;

use App\Repository\EntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: '_admin_param_entreprise')]
#[ORM\HasLifecycleCallbacks]
class Entreprise
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $denomination = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Employe::class)]
    private Collection $employes;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $code = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Proprio::class)]
    private Collection $proprios;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $sigle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $agrements = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $situation_geo = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $contacts = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $mobile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $fax = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $email = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["group1"])]
    private ?Fichier $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $site_web = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $directeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $ville = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["group1"])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'entreprises')]
    #[Groups(["group1"])]
    private ?Pays $pays = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Fonction::class)]
    private Collection $fonctions;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Locataire::class)]
    private Collection $locataires;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: ContratLocation::class)]
    private Collection $contratlocs;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Campagne::class)]
    private Collection $campagnes;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Quartier::class)]
    private Collection $quartiers;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: JoursMoisEntreprise::class)]
    private Collection $joursMoisEntreprises;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: User::class)]
    private Collection $users;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $numero = null;

    public function __construct()
    {
        $this->employes = new ArrayCollection();
        $this->proprios = new ArrayCollection();
        $this->fonctions = new ArrayCollection();
        $this->locataires = new ArrayCollection();
        $this->contratlocs = new ArrayCollection();
        $this->campagnes = new ArrayCollection();
        $this->quartiers = new ArrayCollection();
        $this->joursMoisEntreprises = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function setDenomination(string $denomination): self
    {
        $this->denomination = $denomination;
        return $this;
    }

    public function getEmployes(): Collection
    {
        return $this->employes;
    }

    public function addEmploye(Employe $employe): self
    {
        if (!$this->employes->contains($employe)) {
            $this->employes->add($employe);
            $employe->setEntreprise($this);
        }
        return $this;
    }

    public function removeEmploye(Employe $employe): self
    {
        if ($this->employes->removeElement($employe)) {
            if ($employe->getEntreprise() === $this) {
                $employe->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getProprios(): Collection
    {
        return $this->proprios;
    }

    public function addProprio(Proprio $proprio): static
    {
        if (!$this->proprios->contains($proprio)) {
            $this->proprios->add($proprio);
            $proprio->setEntreprise($this);
        }
        return $this;
    }

    public function removeProprio(Proprio $proprio): static
    {
        if ($this->proprios->removeElement($proprio)) {
            if ($proprio->getEntreprise() === $this) {
                $proprio->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(string $sigle): static
    {
        $this->sigle = $sigle;
        return $this;
    }

    public function getAgrements(): ?string
    {
        return $this->agrements;
    }

    public function setAgrements(string $agrements): static
    {
        $this->agrements = $agrements;
        return $this;
    }

    public function getSituationGeo(): ?string
    {
        return $this->situation_geo;
    }

    public function setSituationGeo(string $situation_geo): static
    {
        $this->situation_geo = $situation_geo;
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

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): static
    {
        $this->mobile = $mobile;
        return $this;
    }

    public function getFax(): ?string
    {
        return $this->fax;
    }

    public function setFax(?string $fax): static
    {
        $this->fax = $fax;
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

    public function getLogo(): ?Fichier
    {
        return $this->logo;
    }

    public function setLogo(?Fichier $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->site_web;
    }

    public function setSiteWeb(string $site_web): static
    {
        $this->site_web = $site_web;
        return $this;
    }

    public function getDirecteur(): ?string
    {
        return $this->directeur;
    }

    public function setDirecteur(string $directeur): static
    {
        $this->directeur = $directeur;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
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

    public function getFonctions(): Collection
    {
        return $this->fonctions;
    }

    public function addFonction(Fonction $fonction): static
    {
        if (!$this->fonctions->contains($fonction)) {
            $this->fonctions->add($fonction);
            $fonction->setEntreprise($this);
        }
        return $this;
    }

    public function removeFonction(Fonction $fonction): static
    {
        if ($this->fonctions->removeElement($fonction)) {
            if ($fonction->getEntreprise() === $this) {
                $fonction->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getLocataires(): Collection
    {
        return $this->locataires;
    }

    public function addLocataire(Locataire $locataire): static
    {
        if (!$this->locataires->contains($locataire)) {
            $this->locataires->add($locataire);
            $locataire->setEntreprise($this);
        }
        return $this;
    }

    public function removeLocataire(Locataire $locataire): static
    {
        if ($this->locataires->removeElement($locataire)) {
            if ($locataire->getEntreprise() === $this) {
                $locataire->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getContratlocs(): Collection
    {
        return $this->contratlocs;
    }

    public function addContratloc(ContratLocation $contratloc): static
    {
        if (!$this->contratlocs->contains($contratloc)) {
            $this->contratlocs->add($contratloc);
            $contratloc->setEntreprise($this);
        }
        return $this;
    }

    public function removeContratloc(ContratLocation $contratloc): static
    {
        if ($this->contratlocs->removeElement($contratloc)) {
            if ($contratloc->getEntreprise() === $this) {
                $contratloc->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getCampagnes(): Collection
    {
        return $this->campagnes;
    }

    public function addCampagne(Campagne $campagne): static
    {
        if (!$this->campagnes->contains($campagne)) {
            $this->campagnes->add($campagne);
            $campagne->setEntreprise($this);
        }
        return $this;
    }

    public function removeCampagne(Campagne $campagne): static
    {
        if ($this->campagnes->removeElement($campagne)) {
            if ($campagne->getEntreprise() === $this) {
                $campagne->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getQuartiers(): Collection
    {
        return $this->quartiers;
    }

    public function addQuartier(Quartier $quartier): static
    {
        if (!$this->quartiers->contains($quartier)) {
            $this->quartiers->add($quartier);
            $quartier->setEntreprise($this);
        }
        return $this;
    }

    public function removeQuartier(Quartier $quartier): static
    {
        if ($this->quartiers->removeElement($quartier)) {
            if ($quartier->getEntreprise() === $this) {
                $quartier->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getJoursMoisEntreprises(): Collection
    {
        return $this->joursMoisEntreprises;
    }

    public function addJoursMoisEntreprise(JoursMoisEntreprise $joursMoisEntreprise): static
    {
        if (!$this->joursMoisEntreprises->contains($joursMoisEntreprise)) {
            $this->joursMoisEntreprises->add($joursMoisEntreprise);
            $joursMoisEntreprise->setEntreprise($this);
        }
        return $this;
    }

    public function removeJoursMoisEntreprise(JoursMoisEntreprise $joursMoisEntreprise): static
    {
        if ($this->joursMoisEntreprises->removeElement($joursMoisEntreprise)) {
            if ($joursMoisEntreprise->getEntreprise() === $this) {
                $joursMoisEntreprise->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setEntreprise($this);
        }
        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getEntreprise() === $this) {
                $user->setEntreprise(null);
            }
        }
        return $this;
    }


    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }
}
