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

    #[ORM\ManyToOne(inversedBy: 'contratLocations')]
    #[Groups(['group1'])]
    private ?Locataire $locataire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateDebut')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateFin')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 1, name: 'nbMoisCaution')]
    #[Groups(['group1'])]
    private ?string $nbMoisCaution = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntCaution')]
    #[Assert\PositiveOrZero(message: 'Le montant de la caution doit être > 0')]
    #[Groups(['group1'])]
    private ?string $mntCaution = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 1, nullable: true, name: 'nbMoisAvance')]
    #[Groups(['group1'])]
    private ?string $nbMoisAvance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntAvance')]
    #[Assert\PositiveOrZero(message: 'Le montant avance doit être > 0')]
    #[Groups(['group1'])]
    private ?string $mntAvance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyer')]
    #[Groups(['group1'])]
    private ?string $mntLoyer = null;

    #[ORM\Column(length: 255, nullable: true, name: 'autreInfos')]
    #[Groups(['group1'])]
    private ?string $autreInfos = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $scanContrat = null;

    #[ORM\ManyToOne(targetEntity: Regime::class, inversedBy: 'contratlocs')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Regime $regime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateEntree')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $dateEntree = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, name: 'dateProchVers')]
    #[Groups(['group1'])]
    private ?DateTimeInterface $dateProchVers = null;

    #[ORM\ManyToOne(targetEntity: Nature::class, inversedBy: 'contratLocations')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Nature $nature = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyerPrec')]
    #[Groups(['group1'])]
    private ?string $mntLoyerPrec = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyerIni')]
    #[Groups(['group1'])]
    private ?string $mntLoyerIni = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntLoyerActu')]
    #[Groups(['group1'])]
    private ?string $mntLoyerActu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'mntArriere')]
    #[Groups(['group1'])]
    private ?string $mntArriere = null;

    #[ORM\Column(length: 255, nullable: true, name: 'dejaLocataire')]
    #[Groups(['group1'])]
    private ?string $dejaLocataire = null;

    #[ORM\Column(length: 255, nullable: true, name: 'statutLoc')]
    #[Groups(['group1'])]
    private ?string $statutLoc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, name: 'fraisAnex')]
    #[Groups(['group1'])]
    private ?string $fraisanex = null;

    #[ORM\Column(nullable: true, name: 'etat')]
    #[Groups(['group1'])]
    private ?int $etat = null;

    #[ORM\Column(nullable: true, name: 'totVerse')]
    #[Groups(['group1'])]
    private ?string $totVerse = null;

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

    #[ORM\ManyToOne(inversedBy: 'appartContratLocations')]
    #[Groups(['group1'])]
    private ?Appartement $appart = null;

    #[ORM\ManyToOne(inversedBy: 'contratlocs')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Motif $motif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: 0, nullable: true, name: 'cautionRemise')]
    private ?string $cautionRemise = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $reglement = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    #[Groups(["group1"])]
    private ?bool $isEcheance = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(["group1"])]
    private ?int $nbEcheance = null;



    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[Groups(["group1"])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fichier $fichierResiliation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $signatureLocataire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $signatureBailleur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["group1"])]
    private ?\DateTimeInterface $dateSignature = null;

    #[ORM\Column(nullable: true, name: 'jourGenerationFacture')]
    #[Groups(['group1'])]
    private ?int $jourGenerationFacture = null;

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

    public function getNbMoisCaution(): ?string
    {
        return $this->nbMoisCaution;
    }

    public function setNbMoisCaution(?string $nbMoisCaution): static
    {
        $this->nbMoisCaution = $nbMoisCaution;
        return $this;
    }

    public function getMntCaution(): ?string
    {
        return $this->mntCaution;
    }

    public function setMntCaution(?string $mntCaution): static
    {
        $this->mntCaution = $mntCaution;
        return $this;
    }

    public function getNbMoisAvance(): ?string
    {
        return $this->nbMoisAvance;
    }

    public function setNbMoisAvance(?string $nbMoisAvance): static
    {
        $this->nbMoisAvance = $nbMoisAvance;
        return $this;
    }

    public function getMntAvance(): ?string
    {
        return $this->mntAvance;
    }

    public function setMntAvance(?string $mntAvance): static
    {
        $this->mntAvance = $mntAvance;
        return $this;
    }

    public function getMntLoyer(): ?string
    {
        return $this->mntLoyer;
    }

    public function setMntLoyer(?string $mntLoyer): static
    {
        $this->mntLoyer = $mntLoyer;
        return $this;
    }

    public function getAutreInfos(): ?string
    {
        return $this->autreInfos;
    }

    public function setAutreInfos(?string $autreInfos): static
    {
        $this->autreInfos = $autreInfos;
        return $this;
    }

    public function getScanContrat(): ?Fichier
    {
        return $this->scanContrat;
    }

    public function setScanContrat(?Fichier $scanContrat): static
    {
        $this->scanContrat = $scanContrat;
        return $this;
    }

    public function getRegime(): ?Regime
    {
        return $this->regime;
    }

    public function setRegime(?Regime $regime): static
    {
        $this->regime = $regime;
        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(?\DateTimeInterface $dateEntree): static
    {
        $this->dateEntree = $dateEntree;
        return $this;
    }

    public function getDateProchVers(): ?\DateTimeInterface
    {
        return $this->dateProchVers;
    }

    public function setDateProchVers(?\DateTimeInterface $dateProchVers): static
    {
        $this->dateProchVers = $dateProchVers;
        return $this;
    }

    public function getNature(): ?Nature
    {
        return $this->nature;
    }

    public function setNature(?Nature $nature): static
    {
        $this->nature = $nature;
        return $this;
    }

    public function getMntLoyerPrec(): ?string
    {
        return $this->mntLoyerPrec;
    }

    public function setMntLoyerPrec(?string $mntLoyerPrec): static
    {
        $this->mntLoyerPrec = $mntLoyerPrec;
        return $this;
    }

    public function getMntLoyerIni(): ?string
    {
        return $this->mntLoyerIni;
    }

    public function setMntLoyerIni(?string $mntLoyerIni): static
    {
        $this->mntLoyerIni = $mntLoyerIni;
        return $this;
    }

    public function getMntLoyerActu(): ?string
    {
        return $this->mntLoyerActu;
    }

    public function setMntLoyerActu(?string $mntLoyerActu): static
    {
        $this->mntLoyerActu = $mntLoyerActu;
        return $this;
    }

    public function getMntArriere(): ?string
    {
        return $this->mntArriere;
    }

    public function setMntArriere(?string $mntArriere): static
    {
        $this->mntArriere = $mntArriere;
        return $this;
    }

    public function getDejaLocataire(): ?string
    {
        return $this->dejaLocataire;
    }

    public function setDejaLocataire(?string $dejaLocataire): static
    {
        $this->dejaLocataire = $dejaLocataire;
        return $this;
    }

    public function getStatutLoc(): ?string
    {
        return $this->statutLoc;
    }

    public function setStatutLoc(?string $statutLoc): static
    {
        $this->statutLoc = $statutLoc;
        return $this;
    }

    public function getFraisanex(): ?string
    {
        return $this->fraisanex;
    }

    public function setFraisanex(?string $fraisanex): static
    {
        $this->fraisanex = $fraisanex;
        return $this;
    }

    public function getEtat(): ?int
    {
        return $this->etat;
    }

    public function setEtat(?int $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    public function getTotVerse(): ?string
    {
        return $this->totVerse;
    }

    public function setTotVerse(?string $totVerse): static
    {
        $this->totVerse = $totVerse;
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
        return $this->cautionRemise;
    }

    public function setCautionRemise(?string $cautionRemise): static
    {
        $this->cautionRemise = $cautionRemise;
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
        return $this->fichierResiliation;
    }

    public function setFichierResiliation(?Fichier $fichierResiliation): static
    {
        $this->fichierResiliation = $fichierResiliation;
        return $this;
    }
    public function getJourGenerationFacture(): ?int
    {
        return $this->jourGenerationFacture;
    }

    public function setJourGenerationFacture(?int $jourGenerationFacture): static
    {
        $this->jourGenerationFacture = $jourGenerationFacture;
        return $this;
    }
    public function getReglement(): ?string
    {
        return $this->reglement;
    }

    public function setReglement(?string $reglement): static
    {
        $this->reglement = $reglement;
        return $this;
    }

    public function isIsEcheance(): ?bool
    {
        return $this->isEcheance;
    }

    public function setIsEcheance(?bool $isEcheance): static
    {
        $this->isEcheance = $isEcheance;

        return $this;
    }

    public function getNbEcheance(): ?int
    {
        return $this->nbEcheance;
    }

    public function setNbEcheance(?int $nbEcheance): static
    {
        $this->nbEcheance = $nbEcheance;

        return $this;
    }

    public function getSignatureLocataire(): ?string
    {
        return $this->signatureLocataire;
    }

    public function setSignatureLocataire(?string $signatureLocataire): static
    {
        $this->signatureLocataire = $signatureLocataire;
        return $this;
    }

    public function getSignatureBailleur(): ?string
    {
        return $this->signatureBailleur;
    }

    public function setSignatureBailleur(?string $signatureBailleur): static
    {
        $this->signatureBailleur = $signatureBailleur;
        return $this;
    }

    public function getDateSignature(): ?\DateTimeInterface
    {
        return $this->dateSignature;
    }

    public function setDateSignature(?\DateTimeInterface $dateSignature): static
    {
        $this->dateSignature = $dateSignature;
        return $this;
    }
}
