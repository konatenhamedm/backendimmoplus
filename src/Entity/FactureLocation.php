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

    #[ORM\ManyToOne(inversedBy: 'factureLocations')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
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
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1'])]
    private ?Campagne $compagne = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1'])]
    private ?TabMois $mois = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1'])]
    private ?ContratLocation $contrat = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1'])]
    private ?Locataire $locataire = null;

    #[ORM\ManyToOne(inversedBy: 'facturelocs')]
    #[Groups(['group1'])]
    private ?Appartement $appartement = null;

    #[ORM\Column(length: 255, name: 'libFacture')]
    #[Groups(['group1'])]
    private ?string $LibFacture = null;

    #[ORM\Column(name: 'mntFact')]
    #[Groups(['group1'])]
    private ?int $MntFact = null;

    #[ORM\Column(name: 'soldeFactLoc')]
    #[Groups(['group1'])]
    private ?int $SoldeFactLoc = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'dateEmission')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $DateEmission = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'dateLimite')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $DateLimite = null;

    #[ORM\OneToMany(mappedBy: 'numFact', targetEntity: Reglements::class)]
    #[Groups(['group1'])]
    private Collection $reglements;

    #[ORM\Column(length: 255, name: 'statut')]
    #[Groups(['group1'])]
    private ?string $statut = null;

    #[ORM\Column(length: 255, name: 'encaisse')]
    #[Groups(['group1'])]
    private ?string $encaisse = null;

    public function __construct()
    {
        $this->reglements = new ArrayCollection();
        $this->transactions = new ArrayCollection();
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

    public function getAppartement(): ?appartement
    {
        return $this->appartement;
    }

    public function setAppartement(?appartement $appartement): static
    {
        $this->appartement = $appartement;

        return $this;
    }

    public function getLibFacture(): ?string
    {
        return $this->LibFacture;
    }

    public function setLibFacture(string $LibFacture): static
    {
        $this->LibFacture = $LibFacture;

        return $this;
    }

    public function getMntFact(): ?int
    {
        return $this->MntFact;
    }

    public function setMntFact(int $MntFact): static
    {
        $this->MntFact = $MntFact;

        return $this;
    }

    public function getSoldeFactLoc(): ?int
    {
        return $this->SoldeFactLoc;
    }

    public function setSoldeFactLoc(int $SoldeFactLoc): static
    {
        $this->SoldeFactLoc = $SoldeFactLoc;

        return $this;
    }

    public function getDateEmission(): ?\DateTimeInterface
    {
        return $this->DateEmission;
    }

    public function setDateEmission(\DateTimeInterface $DateEmission): static
    {
        $this->DateEmission = $DateEmission;

        return $this;
    }

    public function getDateLimite(): ?\DateTimeInterface
    {
        return $this->DateLimite;
    }

    public function setDateLimite(\DateTimeInterface $DateLimite): static
    {
        $this->DateLimite = $DateLimite;

        return $this;
    }

    /**
     * @return Collection<int, Reglements>
     */
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
            // set the owning side to null (unless already changed)
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
    #[ORM\OneToMany(mappedBy: 'factureLocation', targetEntity: Transaction::class)]
    #[Groups(['group1'])]
    private Collection $transactions;

    /**
     * @return Collection<int, Transaction>
     */
    #[Groups(['group1'])]
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
            // set the owning side to null (unless already changed)
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
}
