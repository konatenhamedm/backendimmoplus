<?php

namespace App\Entity;

use App\Repository\ProprioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProprioRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Proprio
{
    use TraitEntity;
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
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $contacts = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $addresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $numCni = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $cni = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $nomPere = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $nomMere = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $whatsApp = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateNaiss = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $lieuNaiss = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $prefession = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateCni = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $nomPrenomsR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $contactsR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $emailR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $adresseR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $nomPrereR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $nomMereR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $whatsAppR = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateNaissR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $lieuNaissR = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $professionR = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateCniR = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Fichier $lien = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commission = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalDu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalPaye = null;

    #[ORM\ManyToOne(inversedBy: 'proprios')]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\OneToMany(mappedBy: 'proprio', targetEntity: Maison::class)]
    private Collection $proprioMaisons;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['group1'])]
    private ?string $numCniR = null;

    #[ORM\OneToMany(mappedBy: 'proprio', targetEntity: VersmtProprio::class)]
    private Collection $versmtProprios;

    #[ORM\OneToMany(mappedBy: 'proprio', targetEntity: ChargeProprio::class)]
    #[Groups(['group1'])]
    private Collection $chargeProprios;

    public function __construct()
    {
        $this->proprioMaisons = new ArrayCollection();
        $this->versmtProprios = new ArrayCollection();
        $this->chargeProprios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAddresse(): ?string
    {
        return $this->addresse;
    }

    public function setAddresse(string $addresse): static
    {
        $this->addresse = $addresse;

        return $this;
    }

    public function getNumCni(): ?string
    {
        return $this->numCni;
    }

    public function setNumCni(string $numCni): static
    {
        $this->numCni = $numCni;

        return $this;
    }

    public function getCni(): ?Fichier
    {
        return $this->cni;
    }

    public function setCni(Fichier $cni): static
    {
        $this->cni = $cni;

        return $this;
    }

    public function getNomPere(): ?string
    {
        return $this->nomPere;
    }

    public function setNomPere(string $nomPere): static
    {
        $this->nomPere = $nomPere;

        return $this;
    }

    public function getNomMere(): ?string
    {
        return $this->nomMere;
    }

    public function setNomMere(string $nomMere): static
    {
        $this->nomMere = $nomMere;

        return $this;
    }

    public function getWhatsApp(): ?string
    {
        return $this->whatsApp;
    }

    public function setWhatsApp(string $whatsApp): static
    {
        $this->whatsApp = $whatsApp;

        return $this;
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

    public function getPrefession(): ?string
    {
        return $this->prefession;
    }

    public function setPrefession(string $prefession): static
    {
        $this->prefession = $prefession;

        return $this;
    }

    public function getDateCni(): ?\DateTimeInterface
    {
        return $this->dateCni;
    }

    public function setDateCni(\DateTimeInterface $dateCni): static
    {
        $this->dateCni = $dateCni;

        return $this;
    }

    public function getNomPrenomsR(): ?string
    {
        return $this->nomPrenomsR;
    }

    public function setNomPrenomsR(string $nomPrenomsR): static
    {
        $this->nomPrenomsR = $nomPrenomsR;

        return $this;
    }

    public function getContactsR(): ?string
    {
        return $this->contactsR;
    }

    public function setContactsR(string $contactsR): static
    {
        $this->contactsR = $contactsR;

        return $this;
    }

    public function getEmailR(): ?string
    {
        return $this->emailR;
    }

    public function setEmailR(string $emailR): static
    {
        $this->emailR = $emailR;

        return $this;
    }

    public function getAdresseR(): ?string
    {
        return $this->adresseR;
    }

    public function setAdresseR(string $adresseR): static
    {
        $this->adresseR = $adresseR;

        return $this;
    }

    public function getNomPrereR(): ?string
    {
        return $this->nomPrereR;
    }

    public function setNomPrereR(string $nomPrereR): static
    {
        $this->nomPrereR = $nomPrereR;

        return $this;
    }

    public function getNomMereR(): ?string
    {
        return $this->nomMereR;
    }

    public function setNomMereR(string $nomMereR): static
    {
        $this->nomMereR = $nomMereR;

        return $this;
    }

    public function getWhatsAppR(): ?string
    {
        return $this->whatsAppR;
    }

    public function setWhatsAppR(string $whatsAppR): static
    {
        $this->whatsAppR = $whatsAppR;

        return $this;
    }

    public function getDateNaissR(): ?\DateTimeInterface
    {
        return $this->dateNaissR;
    }

    public function setDateNaissR(\DateTimeInterface $dateNaissR): static
    {
        $this->dateNaissR = $dateNaissR;

        return $this;
    }

    public function getLieuNaissR(): ?string
    {
        return $this->lieuNaissR;
    }

    public function setLieuNaissR(string $lieuNaissR): static
    {
        $this->lieuNaissR = $lieuNaissR;

        return $this;
    }

    public function getProfessionR(): ?string
    {
        return $this->professionR;
    }

    public function setProfessionR(string $professionR): static
    {
        $this->professionR = $professionR;

        return $this;
    }

    public function getDateCniR(): ?\DateTimeInterface
    {
        return $this->dateCniR;
    }

    public function setDateCniR(\DateTimeInterface $dateCniR): static
    {
        $this->dateCniR = $dateCniR;

        return $this;
    }

    public function getLien(): ?Fichier
    {
        return $this->lien;
    }

    public function setLien(Fichier $lien): static
    {
        $this->lien = $lien;

        return $this;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(string $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getTotalDu(): ?string
    {
        return $this->totalDu;
    }

    public function setTotalDu(string $totalDu): static
    {
        $this->totalDu = $totalDu;

        return $this;
    }

    public function getTotalPaye(): ?string
    {
        return $this->totalPaye;
    }

    public function setTotalPaye(string $totalPaye): static
    {
        $this->totalPaye = $totalPaye;

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
     * @return Collection<int, Maison>
     */
    public function getProprioMaisons(): Collection
    {
        return $this->proprioMaisons;
    }

    public function addProprioMaison(Maison $proprioMaison): static
    {
        if (!$this->proprioMaisons->contains($proprioMaison)) {
            $this->proprioMaisons->add($proprioMaison);
            $proprioMaison->setProprio($this);
        }

        return $this;
    }

    public function removeProprioMaison(Maison $proprioMaison): static
    {
        if ($this->proprioMaisons->removeElement($proprioMaison)) {
            // set the owning side to null (unless already changed)
            if ($proprioMaison->getProprio() === $this) {
                $proprioMaison->setProprio(null);
            }
        }

        return $this;
    }

    public function getNumCniR(): ?string
    {
        return $this->numCniR;
    }

    public function setNumCniR(string $numCniR): static
    {
        $this->numCniR = $numCniR;

        return $this;
    }

    public function getSommeMaison()
    {
        $sommeT = 0;
        ///$sommeRes=0;
        ///$sommeSole=0;
        foreach ($this->getProprioMaisons() as $maison) {
            foreach ($maison->getAppartements() as $appartement) {
                foreach ($appartement->getFactureLocations() as $facture) {
                    $sommeT += $facture->getMntFact();
                    // $sommeSole += $facture->getSoldeFactLoc();
                }
            }
        }
        return $sommeT;
    }

    public function getSommeSoleRestant()
    {
        //$sommeT=0;
        //$sommeRes=0;
        $sommeSole = 0;
        foreach ($this->getProprioMaisons() as $maison) {
            foreach ($maison->getAppartements() as $appartement) {
                foreach ($appartement->getFactureLocations() as $facture) {
                    // $sommeT += $facture->getMntFact();
                    $sommeSole += $facture->getSoldeFactLoc();
                }
            }
        }
        return $sommeSole;
    }

    public function getSommeEncaisser()
    {
        $sommeT = 0;
        $sommeRes = 0;
        $sommeSole = 0;
        foreach ($this->getProprioMaisons() as $maison) {
            foreach ($maison->getAppartements() as $appartement) {
                foreach ($appartement->getFactureLocations() as $facture) {
                    $sommeT += $facture->getMntFact();
                    $sommeSole += $facture->getSoldeFactLoc();
                }
            }
        }

        return ($sommeT - $sommeSole);
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
            $versmtProprio->setProprio($this);
        }

        return $this;
    }

    public function removeVersmtProprio(VersmtProprio $versmtProprio): static
    {
        if ($this->versmtProprios->removeElement($versmtProprio)) {
            // set the owning side to null (unless already changed)
            if ($versmtProprio->getProprio() === $this) {
                $versmtProprio->setProprio(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ChargeProprio>
     */
    public function getChargeProprios(): Collection
    {
        return $this->chargeProprios;
    }

    public function getTotalCharges(): float
    {
        $total = 0;
        foreach ($this->chargeProprios as $charge) {
            $total += (float) $charge->getMontant();
        }
        return $total;
    }

    public function getSoldeAPayer(): float
    {
        $encaisser = $this->getSommeEncaisser();
        $charges = $this->getTotalCharges();
        
        $totalDejaPaye = 0;
        foreach ($this->versmtProprios as $v) {
            $totalDejaPaye += (float) $v->getMontant();
        }

        // Simplistic commission calculation (if stored as percentage string like "10")
        $commRate = (float) ($this->getCommission() ?? 0);
        $commAmount = $encaisser * ($commRate / 100);

        return $encaisser - $commAmount - $charges - $totalDejaPaye;
    }
}
