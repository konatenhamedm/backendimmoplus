<?php

namespace App\Entity;

use App\Repository\FactureLocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FactureLocationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FactureLocation
{
    use TraitEntity;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?Entreprise $entreprise = null;

    const ETATS = [
        'oui' => 'oui',
        'non' => 'non',
    ];
    const ETATS_STATUT = [
        'payer' => 'payer',
        'impayer' => 'impayer',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?Campagne $compagne = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?TabMois $mois = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?ContratLocation $contrat = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?Locataire $locataire = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?Appartement $appartement = null;

    #[ORM\Column(length: 255, name: 'libFacture')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $libFacture = null;

    #[ORM\Column(name: 'mntFact')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?int $mntFact = null;

    #[ORM\Column(name: 'soldeFactLoc')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?int $soldeFactLoc = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'dateEmission')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?\DateTimeInterface $dateEmission = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'dateLimite')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?\DateTimeInterface $dateLimite = null;

    #[ORM\OneToMany(mappedBy: 'numFact', targetEntity: Reglements::class)]
    #[Groups(['group1', 'group1_facture_location'])]
    private Collection $reglements;

    #[ORM\Column(length: 255, name: 'statut')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $statut = null;

    #[ORM\Column(length: 255, name: 'encaisse')]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $encaisse = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $fneUid = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $fneQrCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?string $fneStatus = null;

    #[ORM\ManyToOne(targetEntity: Agence::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1', 'group1_facture_location'])]
    private ?Agence $agence = null;

    #[ORM\OneToMany(mappedBy: 'facture', targetEntity: Relance::class, orphanRemoval: true)]
    #[Groups(['group1_facture_location'])]
    private Collection $relances;

    #[ORM\OneToMany(mappedBy: 'factureLocation', targetEntity: Transaction::class)]
    #[Groups(['group1', 'group1_facture_location'])]
    private Collection $transactions;

    public function __construct()
    {
        $this->reglements = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->relances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompagne(): ?Campagne
    {
        return $this->compagne;
    }

    public function setCompagne(?Campagne $compagne): static
    {
        $this->compagne = $compagne;
        return $this;
    }

    public function getMois(): ?TabMois
    {
        return $this->mois;
    }

    public function setMois(?TabMois $mois): static
    {
        $this->mois = $mois;
        return $this;
    }

    public function getContrat(): ?ContratLocation
    {
        return $this->contrat;
    }

    public function setContrat(?ContratLocation $contrat): static
    {
        $this->contrat = $contrat;
        return $this;
    }

    public function getLocataire(): ?Locataire
    {
        return $this->locataire;
    }

    public function setLocataire(?Locataire $locataire): static
    {
        $this->locataire = $locataire;
        return $this;
    }

    public function getAppartement(): ?Appartement
    {
        return $this->appartement;
    }

    public function setAppartement(?Appartement $appartement): static
    {
        $this->appartement = $appartement;
        return $this;
    }

    public function getLibFacture(): ?string
    {
        return $this->libFacture;
    }

    public function setLibFacture(string $libFacture): static
    {
        $this->libFacture = $libFacture;
        return $this;
    }

    public function getMntFact(): ?int
    {
        return $this->mntFact;
    }

    public function setMntFact(int $mntFact): static
    {
        $this->mntFact = $mntFact;
        return $this;
    }

    public function getSoldeFactLoc(): ?int
    {
        return $this->soldeFactLoc;
    }

    public function setSoldeFactLoc(int $soldeFactLoc): static
    {
        $this->soldeFactLoc = $soldeFactLoc;
        return $this;
    }

    public function getDateEmission(): ?\DateTimeInterface
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTimeInterface $dateEmission): static
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    public function getDateLimite(): ?\DateTimeInterface
    {
        return $this->dateLimite;
    }

    public function setDateLimite(\DateTimeInterface $dateLimite): static
    {
        $this->dateLimite = $dateLimite;
        return $this;
    }

    public function getReglements(): Collection
    {
        return $this->reglements;
    }

    public function addReglement(Reglements $reglement): static
    {
        if (!$this->reglements->contains($reglement)) {
            $this->reglements->add($reglement);
            $reglement->setNumFact($this);
        }
        return $this;
    }

    public function removeReglement(Reglements $reglement): static
    {
        if ($this->reglements->removeElement($reglement)) {
            if ($reglement->getNumFact() === $this) {
                $reglement->setNumFact(null);
            }
        }
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getEncaisse(): ?string
    {
        return $this->encaisse;
    }

    public function setEncaisse(string $encaisse): static
    {
        $this->encaisse = $encaisse;
        return $this;
    }

    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setFactureLocation($this);
        }
        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getFactureLocation() === $this) {
                $transaction->setFactureLocation(null);
            }
        }
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

    public function getFneUid(): ?string
    {
        return $this->fneUid;
    }

    public function setFneUid(?string $fneUid): static
    {
        $this->fneUid = $fneUid;
        return $this;
    }

    public function getFneQrCode(): ?string
    {
        return $this->fneQrCode;
    }

    public function setFneQrCode(?string $fneQrCode): static
    {
        $this->fneQrCode = $fneQrCode;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getFneStatus(): ?string
    {
        return $this->fneStatus;
    }

    public function setFneStatus(?string $fneStatus): static
    {
        $this->fneStatus = $fneStatus;
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

    public function getRelances(): Collection
    {
        return $this->relances;
    }

    public function addRelance(Relance $relance): static
    {
        if (!$this->relances->contains($relance)) {
            $this->relances->add($relance);
            $relance->setFacture($this);
        }
        return $this;
    }

    public function removeRelance(Relance $relance): static
    {
        if ($this->relances->removeElement($relance)) {
            if ($relance->getFacture() === $this) {
                $relance->setFacture(null);
            }
        }
        return $this;
    }
}
