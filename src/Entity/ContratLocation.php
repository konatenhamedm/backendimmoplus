<?php

namespace App\Entity;

use App\Repository\ContratLocationRepository;
use DateTimeInterface;
use App\Entity\TypeMaison;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContratLocationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContratLocation
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ContratLocations')]
    #[Groups(['group1'])]
    private ?Locataire $locataire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateDebut')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $DateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateFin')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $DateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 1, name: 'nbMoisCaution')]
    #[Groups(['group1'])]
    private ?string $NbMoisCaution = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntCaution')]
    #[Assert\PositiveOrZero(message: 'Le montant de la caution doit être > 0')]
    #[Groups(['group1'])]
    private ?string $MntCaution = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 1, nullable: true, name: 'nbMoisAvance')]
    #[Groups(['group1'])]
    private ?string $NbMoisAvance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntAvance')]
    #[Assert\PositiveOrZero(message: 'Le montant avance doit être > 0')]
    #[Groups(['group1'])]
    private ?string $MntAvance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyer')]
    #[Groups(['group1'])]
    private ?string $MntLoyer = null;

    #[ORM\Column(length: 255, nullable: true, name: 'autreInfos')]
    #[Groups(['group1'])]
    private ?string $AutreInfos = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $ScanContrat = null;

    #[ORM\ManyToOne(targetEntity: Regime::class, inversedBy: 'Contratlocs')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Regime $Regime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateEntree')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $DateEntree = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateProchVers')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $DateProchVers = null;

    #[ORM\ManyToOne(targetEntity: Nature::class, inversedBy: 'ContratLocations')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Nature $Nature = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyerPrec')]
    #[Groups(['group1'])]
    private ?string $MntLoyerPrec = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyerIni')]
    #[Groups(['group1'])]
    private ?string $MntLoyerIni = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyerActu')]
    #[Groups(['group1'])]
    private ?string $MntLoyerActu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntArriere')]
    #[Groups(['group1'])]
    private ?string $MntArriere = null;

    #[ORM\Column(length: 255, nullable: true, name: 'dejaLocataire')]
    #[Groups(['group1'])]
    private ?string $DejaLocataire = null;

    #[ORM\Column(length: 255, nullable: true, name: 'statutLoc')]
    #[Groups(['group1'])]
    private ?string $StatutLoc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, name: 'fraisAnex')]
    #[Groups(['group1'])]
    private ?string $Fraisanex = null;

    #[ORM\Column(nullable: true, name: 'etat')]
    #[Groups(['group1'])]
    private ?int $Etat = null;

    #[ORM\Column(nullable: true, name: 'totVerse')]
    #[Groups(['group1'])]
    private ?string $TotVerse = null;

    #[ORM\OneToMany(mappedBy: 'contrat', targetEntity: FactureLocation::class)]
    private Collection $facturelocs;

    #[ORM\OneToMany(mappedBy: 'contrat', targetEntity: FinContrat::class)]
    private Collection $fincontrats;

    #[ORM\ManyToOne(inversedBy: 'contratlocs')]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'contratlocs')]
    #[Groups(['group1'])]
    private ?Campagne $campagne = null;

    #[ORM\ManyToOne(inversedBy: 'appartContratlocs')]
    #[Groups(['group1'])]
    private ?Appartement $appart = null;

    #[ORM\ManyToOne(inversedBy: 'contratlocs')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Motif $motif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'cautionRemise')]
    private ?string $CautionRemise = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[Groups(["group1"])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fichier $FichierResiliation = null;

    #[ORM\Column(nullable: true, name: 'jourGenerationFacture')]
    #[Groups(['group1'])]
    private ?int $JourGenerationFacture = null;

    public function __construct()
    {
        $this->facturelocs = new ArrayCollection();
        $this->fincontrats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->DateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $DateDebut): static
    {
        $this->DateDebut = $DateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->DateFin;
    }

    public function setDateFin(?\DateTimeInterface $DateFin): static
    {
        $this->DateFin = $DateFin;
        return $this;
    }

    public function getNbMoisCaution(): ?string
    {
        return $this->NbMoisCaution;
    }

    public function setNbMoisCaution(?string $NbMoisCaution): static
    {
        $this->NbMoisCaution = $NbMoisCaution;
        return $this;
    }

    public function getMntCaution(): ?string
    {
        return $this->MntCaution;
    }

    public function setMntCaution(?string $MntCaution): static
    {
        $this->MntCaution = $MntCaution;
        return $this;
    }

    public function getNbMoisAvance(): ?string
    {
        return $this->NbMoisAvance;
    }

    public function setNbMoisAvance(?string $NbMoisAvance): static
    {
        $this->NbMoisAvance = $NbMoisAvance;
        return $this;
    }

    public function getMntAvance(): ?string
    {
        return $this->MntAvance;
    }

    public function setMntAvance(?string $MntAvance): static
    {
        $this->MntAvance = $MntAvance;
        return $this;
    }

    public function getMntLoyer(): ?string
    {
        return $this->MntLoyer;
    }

    public function setMntLoyer(?string $MntLoyer): static
    {
        $this->MntLoyer = $MntLoyer;
        return $this;
    }

    public function getAutreInfos(): ?string
    {
        return $this->AutreInfos;
    }

    public function setAutreInfos(?string $AutreInfos): static
    {
        $this->AutreInfos = $AutreInfos;
        return $this;
    }

    public function getScanContrat(): ?Fichier
    {
        return $this->ScanContrat;
    }

    public function setScanContrat(?Fichier $ScanContrat): static
    {
        $this->ScanContrat = $ScanContrat;
        return $this;
    }

    public function getRegime(): ?Regime
    {
        return $this->Regime;
    }

    public function setRegime(?Regime $Regime): static
    {
        $this->Regime = $Regime;
        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->DateEntree;
    }

    public function setDateEntree(?\DateTimeInterface $DateEntree): static
    {
        $this->DateEntree = $DateEntree;
        return $this;
    }

    public function getDateProchVers(): ?\DateTimeInterface
    {
        return $this->DateProchVers;
    }

    public function setDateProchVers(?\DateTimeInterface $DateProchVers): static
    {
        $this->DateProchVers = $DateProchVers;
        return $this;
    }

    public function getNature(): ?Nature
    {
        return $this->Nature;
    }

    public function setNature(?Nature $Nature): static
    {
        $this->Nature = $Nature;
        return $this;
    }

    public function getMntLoyerPrec(): ?string
    {
        return $this->MntLoyerPrec;
    }

    public function setMntLoyerPrec(?string $MntLoyerPrec): static
    {
        $this->MntLoyerPrec = $MntLoyerPrec;
        return $this;
    }

    public function getMntLoyerIni(): ?string
    {
        return $this->MntLoyerIni;
    }

    public function setMntLoyerIni(?string $MntLoyerIni): static
    {
        $this->MntLoyerIni = $MntLoyerIni;
        return $this;
    }

    public function getMntLoyerActu(): ?string
    {
        return $this->MntLoyerActu;
    }

    public function setMntLoyerActu(?string $MntLoyerActu): static
    {
        $this->MntLoyerActu = $MntLoyerActu;
        return $this;
    }

    public function getMntArriere(): ?string
    {
        return $this->MntArriere;
    }

    public function setMntArriere(?string $MntArriere): static
    {
        $this->MntArriere = $MntArriere;
        return $this;
    }

    public function getDejaLocataire(): ?string
    {
        return $this->DejaLocataire;
    }

    public function setDejaLocataire(?string $DejaLocataire): static
    {
        $this->DejaLocataire = $DejaLocataire;
        return $this;
    }

    public function getStatutLoc(): ?string
    {
        return $this->StatutLoc;
    }

    public function setStatutLoc(?string $StatutLoc): static
    {
        $this->StatutLoc = $StatutLoc;
        return $this;
    }

    public function getFraisanex(): ?string
    {
        return $this->Fraisanex;
    }

    public function setFraisanex(?string $Fraisanex): static
    {
        $this->Fraisanex = $Fraisanex;
        return $this;
    }

    public function getEtat(): ?int
    {
        return $this->Etat;
    }

    public function setEtat(?int $Etat): static
    {
        $this->Etat = $Etat;
        return $this;
    }

    public function getTotVerse(): ?string
    {
        return $this->TotVerse;
    }

    public function setTotVerse(?string $TotVerse): static
    {
        $this->TotVerse = $TotVerse;
        return $this;
    }

    public function getFacturelocs(): Collection
    {
        return $this->facturelocs;
    }

    public function addFactureloc(FactureLocation $factureloc): static
    {
        if (!$this->facturelocs->contains($factureloc)) {
            $this->facturelocs->add($factureloc);
            $factureloc->setContrat($this);
        }
        return $this;
    }

    public function removeFactureloc(FactureLocation $factureloc): static
    {
        if ($this->facturelocs->removeElement($factureloc)) {
            if ($factureloc->getContrat() === $this) {
                $factureloc->setContrat(null);
            }
        }
        return $this;
    }

    public function getFincontrats(): Collection
    {
        return $this->fincontrats;
    }

    public function addFincontrat(FinContrat $fincontrat): static
    {
        if (!$this->fincontrats->contains($fincontrat)) {
            $this->fincontrats->add($fincontrat);
            $fincontrat->setContrat($this);
        }
        return $this;
    }

    public function removeFincontrat(FinContrat $fincontrat): static
    {
        if ($this->fincontrats->removeElement($fincontrat)) {
            if ($fincontrat->getContrat() === $this) {
                $fincontrat->setContrat(null);
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

    public function getCampagne(): ?Campagne
    {
        return $this->campagne;
    }

    public function setCampagne(?Campagne $campagne): static
    {
        $this->campagne = $campagne;
        return $this;
    }

    public function getAppart(): ?Appartement
    {
        return $this->appart;
    }

    public function setAppart(?Appartement $appart): static
    {
        $this->appart = $appart;
        return $this;
    }

    public function getMotif(): ?Motif
    {
        return $this->motif;
    }

    public function setMotif(?Motif $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getCautionRemise(): ?string
    {
        return $this->CautionRemise;
    }

    public function setCautionRemise(?string $CautionRemise): static
    {
        $this->CautionRemise = $CautionRemise;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
        return $this;
    }

    public function getFichierResiliation(): ?Fichier
    {
        return $this->FichierResiliation;
    }

    public function setFichierResiliation(?Fichier $FichierResiliation): static
    {
        $this->FichierResiliation = $FichierResiliation;
        return $this;
    }
    public function getJourGenerationFacture(): ?int
    {
        return $this->JourGenerationFacture;
    }

    public function setJourGenerationFacture(?int $JourGenerationFacture): static
    {
        $this->JourGenerationFacture = $JourGenerationFacture;
        return $this;
    }
}
