<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class VenteTerrain
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Terrain::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?Terrain $terrain = null;

    #[ORM\ManyToOne(targetEntity: ClientTerrain::class, inversedBy: 'ventes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?ClientTerrain $client = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $prixVente = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $apportInitial = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $resteAPayer = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(["group1"])]
    private ?\DateTimeInterface $dateVente = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $etat = 'en_cours'; // en_cours, solde, annule

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $typeVente = 'avec_papier'; // avec_papier, sans_papier_gestion_agence

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[Groups(["group1"])]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[Groups(["group1"])]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'venteTerrain', targetEntity: DocumentVenteTerrain::class, cascade: ['persist', 'remove'])]
    #[Groups(["group1"])]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'venteTerrain', targetEntity: EchancierTerrain::class, cascade: ['persist', 'remove'])]
    #[Groups(["group1"])]
    private Collection $echanciers;

    #[ORM\OneToMany(mappedBy: 'venteTerrain', targetEntity: VersementTerrain::class, cascade: ['persist', 'remove'])]
    #[Groups(["group1"])]
    private Collection $versements;

    #[ORM\OneToOne(mappedBy: 'venteTerrain', targetEntity: DemarcheAdministrative::class, cascade: ['persist', 'remove'])]
    #[Groups(["group1"])]
    private ?DemarcheAdministrative $demarcheAdministrative = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->echanciers = new ArrayCollection();
        $this->versements = new ArrayCollection();
        $this->dateVente = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getTerrain(): ?Terrain { return $this->terrain; }
    public function setTerrain(?Terrain $terrain): static { $this->terrain = $terrain; return $this; }

    public function getClient(): ?ClientTerrain { return $this->client; }
    public function setClient(?ClientTerrain $client): static { $this->client = $client; return $this; }

    public function getPrixVente(): ?string { return $this->prixVente; }
    public function setPrixVente(string $prixVente): static { $this->prixVente = $prixVente; return $this; }

    public function getApportInitial(): ?string { return $this->apportInitial; }
    public function setApportInitial(string $apportInitial): static { $this->apportInitial = $apportInitial; return $this; }

    public function getResteAPayer(): ?string { return $this->resteAPayer; }
    public function setResteAPayer(string $resteAPayer): static { $this->resteAPayer = $resteAPayer; return $this; }

    public function getDateVente(): ?\DateTimeInterface { return $this->dateVente; }
    public function setDateVente(\DateTimeInterface $dateVente): static { $this->dateVente = $dateVente; return $this; }

    public function getEtat(): ?string { return $this->etat; }
    public function setEtat(string $etat): static { $this->etat = $etat; return $this; }

    public function getTypeVente(): ?string { return $this->typeVente; }
    public function setTypeVente(string $typeVente): static { $this->typeVente = $typeVente; return $this; }

    public function getAgence(): ?Agence { return $this->agence; }
    public function setAgence(?Agence $agence): static { $this->agence = $agence; return $this; }

    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): static { $this->entreprise = $entreprise; return $this; }

    public function getDocuments(): Collection { return $this->documents; }
    public function addDocument(DocumentVenteTerrain $document): static {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setVenteTerrain($this);
        }
        return $this;
    }
    public function removeDocument(DocumentVenteTerrain $document): static {
        if ($this->documents->removeElement($document)) {
            if ($document->getVenteTerrain() === $this) {
                $document->setVenteTerrain(null);
            }
        }
        return $this;
    }

    public function getEchanciers(): Collection { return $this->echanciers; }
    public function addEchancier(EchancierTerrain $echancier): static {
        if (!$this->echanciers->contains($echancier)) {
            $this->echanciers->add($echancier);
            $echancier->setVenteTerrain($this);
        }
        return $this;
    }
    public function removeEchancier(EchancierTerrain $echancier): static {
        if ($this->echanciers->removeElement($echancier)) {
            if ($echancier->getVenteTerrain() === $this) {
                $echancier->setVenteTerrain(null);
            }
        }
        return $this;
    }

    public function getVersements(): Collection { return $this->versements; }
    public function addVersement(VersementTerrain $versement): static {
        if (!$this->versements->contains($versement)) {
            $this->versements->add($versement);
            $versement->setVenteTerrain($this);
        }
        return $this;
    }
    public function removeVersement(VersementTerrain $versement): static {
        if ($this->versements->removeElement($versement)) {
            if ($versement->getVenteTerrain() === $this) {
                $versement->setVenteTerrain(null);
            }
        }
        return $this;
    }

    public function getDemarcheAdministrative(): ?DemarcheAdministrative { return $this->demarcheAdministrative; }
    public function setDemarcheAdministrative(?DemarcheAdministrative $demarcheAdministrative): static {
        $this->demarcheAdministrative = $demarcheAdministrative;
        if ($demarcheAdministrative !== null && $demarcheAdministrative->getVenteTerrain() !== $this) {
            $demarcheAdministrative->setVenteTerrain($this);
        }
        return $this;
    }
}
