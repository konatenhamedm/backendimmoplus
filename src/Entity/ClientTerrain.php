<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ClientTerrain
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $prenoms = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["group1"])]
    private ?string $pieceIdentite = null;

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[Groups(["group1"])]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[Groups(["group1"])]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: VenteTerrain::class)]
    #[Groups(["group1"])]
    private Collection $ventes;

    public function __construct()
    {
        $this->ventes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenoms(): ?string { return $this->prenoms; }
    public function setPrenoms(?string $prenoms): static { $this->prenoms = $prenoms; return $this; }

    public function getContact(): ?string { return $this->contact; }
    public function setContact(string $contact): static { $this->contact = $contact; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getPieceIdentite(): ?string { return $this->pieceIdentite; }
    public function setPieceIdentite(?string $pieceIdentite): static { $this->pieceIdentite = $pieceIdentite; return $this; }

    public function getAgence(): ?Agence { return $this->agence; }
    public function setAgence(?Agence $agence): static { $this->agence = $agence; return $this; }

    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }

    public function getVentes(): Collection { return $this->ventes; }
    public function addVente(VenteTerrain $vente): static {
        if (!$this->ventes->contains($vente)) {
            $this->ventes->add($vente);
            $vente->setClient($this);
        }
        return $this;
    }
    public function removeVente(VenteTerrain $vente): static {
        if ($this->ventes->removeElement($vente)) {
            if ($vente->getClient() === $this) {
                $vente->setClient(null);
            }
        }
        return $this;
    }
}
