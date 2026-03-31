<?php

namespace App\Entity;

use App\Repository\AgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AgenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Agence
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1', 'appartement-groupe', 'group1_facture_location'])]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'agences')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'agence', targetEntity: User::class)]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'agence', targetEntity: Locataire::class)]
    private Collection $locataires;

    #[ORM\Column]
    private ?bool $isActive = true;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->locataires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
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

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
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

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setAgence($this);
        }
        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getAgence() === $this) {
                $user->setAgence(null);
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
            $locataire->setAgence($this);
        }
        return $this;
    }

    public function removeLocataire(Locataire $locataire): static
    {
        if ($this->locataires->removeElement($locataire)) {
            if ($locataire->getAgence() === $this) {
                $locataire->setAgence(null);
            }
        }
        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
}
