<?php

namespace App\Entity;

use App\Repository\EmployeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;


#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: '_admin_employe')]
#[ORM\HasLifecycleCallbacks]
class Employe
{
    use TraitEntity;

    const DEFAULT_CHOICE_LABEL = 'nomComplet';
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    #[Groups(['group1'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Groups(['group1'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $fonction = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Civilite $civilite = null;

    #[ORM\Column(length: 50)]
    #[Groups(['group1'])]
    private ?string $contact = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $adresseMail = null;

  
     #[ORM\OneToOne(cascade: ['persist', 'remove'], mappedBy: "employe")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'employes')]
    #[Groups(['group1'])]
    private ?Service $service = null;

    // ... (rest of the fields)
    
    #[ORM\ManyToOne(targetEntity: Agence::class)] // reused same mappedBy vaguely or no inversedBy
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Agence $agence = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        // set the owning side of the relation if necessary
        if ($user->getEmploye() !== $this) {
            $user->setEmploye($this);
        }

        return $this;
    }


    #[ORM\Column(length: 12)]
    #[Groups(['group1'])]
    private ?string $matricule = null;



    #[ORM\ManyToOne(inversedBy: 'employes')]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $numPiece = null;

    #[ORM\Column(length: 255,nullable: true)]
    #[Groups(['group1'])]
    private ?string $contacts = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $residence = null;

       #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $piece = null;


    public function __construct()
    {
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(?string $fonction): self
    {
        $this->fonction = $fonction;

        return $this;
    }

    public function getCivilite(): ?Civilite
    {
        return $this->civilite;
    }

    public function setCivilite(?Civilite $civilite): self
    {
        $this->civilite = $civilite;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getAdresseMail(): ?string
    {
        return $this->adresseMail;
    }

    public function setAdresseMail(string $adresseMail): self
    {
        $this->adresseMail = $adresseMail;

        return $this;
    }



    public function getNomComplet(): ?string
    {
        return $this->getNom() . ' ' . $this->getPrenom();
    }



    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): self
    {
        $this->matricule = $matricule;

        return $this;
    }


    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getNumPiece(): ?string
    {
        return $this->numPiece;
    }

    public function setNumPiece(string $numPiece): static
    {
        $this->numPiece = $numPiece;

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

    public function getResidence(): ?string
    {
        return $this->residence;
    }

    public function setResidence(string $residence): static
    {
        $this->residence = $residence;

        return $this;
    }

    public function getPiece(): ?Fichier
    {
        return $this->piece;
    }

    public function setPiece(Fichier $piece): static
    {
        $this->piece = $piece;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

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
