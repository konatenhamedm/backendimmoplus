<?php

namespace App\Entity;

use App\Repository\AppartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: AppartementRepository::class)]
#[UniqueEntity(fields: ['LibAppart', 'maisson_id'],  message: 'Cette campagne existe deja.')]
#[ORM\HasLifecycleCallbacks]
class Appartement
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, name: 'libAppart')]
    #[Assert\NotBlank(message: 'Veuillez renseigner le libellé de la colonne', groups: ['colonne-groupe'])]
    #[Groups(['group1'])]
    private ?string $LibAppart = null;



    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: '0', name: 'nbrePieces')]
    #[Groups(['group1'])]
    private ?int $NbrePieces = null;

    #[ORM\Column(name: 'numEtage')]
    #[Groups(['group1'])]
    private ?int $NumEtage = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', name: 'loyer')]
    #[Assert\Positive(message: 'Le loyer payé doit être > à 0')]
    #[Groups(['group1'])]
    private ?int $Loyer = null;

    #[ORM\Column(nullable: true, name: 'caution')]
    #[Groups(['group1'])]
    private ?int $Caution = null;

    #[ORM\Column(length: 255, name: 'details')]
    #[Groups(['group1'])]
    private ?string $Details = null;

    #[ORM\Column(nullable: true, name: 'oqp')]
    #[Groups(['group1'])]
    private ?int $Oqp = null;

    #[ORM\ManyToOne(inversedBy: 'appartements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Maison $maisson = null;

    #[ORM\OneToMany(mappedBy: 'appart', targetEntity: ContratLocation::class)]
    private Collection $appartContratLocations;

    #[ORM\OneToMany(mappedBy: 'appartement', targetEntity: FactureLocation::class)]
    private Collection $facturelocs;

    /* #[ORM\OneToMany(mappedBy: 'appartement', targetEntity: ContratLocation::class)]
    private Collection $contratlocs; */

    public function __construct()
    {

        $this->facturelocs = new ArrayCollection();
        //$this->contratlocs = new ArrayCollection();
        /* $this->appartementContratLocations = new ArrayCollection();*/
        $this->appartContratLocations = new ArrayCollection();
        $this->Oqp = 0;
    }

    public function getNomComplet()
    {
        return $this->maisson->getProprio()->getNom()." ".$this->maisson->getProprio()->getPrenoms() . " - " . $this->maisson->getLibMaison() . " - " . $this->LibAppart . " - " . $this->Loyer;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibAppart(): ?string
    {
        return $this->LibAppart;
    }

    public function setLibAppart(string $LibAppart): static
    {
        $this->LibAppart = $LibAppart;

        return $this;
    }


    public function getNbrePieces(): ?int
    {
        return $this->NbrePieces;
    }

    public function setNbrePieces(int $NbrePieces): static
    {
        $this->NbrePieces = $NbrePieces;

        return $this;
    }

    public function getNumEtage(): ?int
    {
        return $this->NumEtage;
    }

    public function setNumEtage(int $NumEtage): static
    {
        $this->NumEtage = $NumEtage;

        return $this;
    }

    public function getLoyer(): ?int
    {
        return $this->Loyer;
    }

    public function setLoyer(int $Loyer): static
    {
        $this->Loyer = $Loyer;

        return $this;
    }

    public function getCaution(): ?int
    {
        return $this->Caution;
    }

    public function setCaution(int $Caution): static
    {
        $this->Caution = $Caution;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->Details;
    }

    public function setDetails(string $Details): static
    {
        $this->Details = $Details;

        return $this;
    }

    public function getOqp(): ?int
    {
        return $this->Oqp;
    }

    public function setOqp(int $Oqp): static
    {
        $this->Oqp = $Oqp;

        return $this;
    }

    public function getMaisson(): ?Maison
    {
        return $this->maisson;
    }

    public function setMaisson(?Maison $maisson): static
    {
        $this->maisson = $maisson;

        return $this;
    }


    /**
     * @return Collection<int, ContratLocation>
     */
    public function getAppartContratLocations(): Collection
    {
        return $this->appartContratLocations;
    }

    public function addAppartContratLocation(ContratLocation $appartContratLocation): static
    {
        if (!$this->appartContratLocations->contains($appartContratLocation)) {
            $this->appartContratLocations->add($appartContratLocation);
            $appartContratLocation->setAppart($this);
        }

        return $this;
    }

    public function removeAppartContratLocation(ContratLocation $appartContratLocation): static
    {
        if ($this->appartContratLocations->removeElement($appartContratLocation)) {
            // set the owning side to null (unless already changed)
            if ($appartContratLocation->getAppart() === $this) {
                $appartContratLocation->setAppart(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection<int, FactureLocation>
     */
    public function getFactureLocations(): Collection
    {
        return $this->facturelocs;
    }

    public function addFactureLocation(FactureLocation $factureloc): static
    {
        if (!$this->facturelocs->contains($factureloc)) {
            $this->facturelocs->add($factureloc);
            $factureloc->setAppartement($this);
        }

        return $this;
    }

    public function removeFactureLocation(FactureLocation $factureloc): static
    {
        if ($this->facturelocs->removeElement($factureloc)) {
            // set the owning side to null (unless already changed)
            if ($factureloc->getAppartement() === $this) {
                $factureloc->setAppartement(null);
            }
        }

        return $this;
    }



    /**
     * @return Collection<int, ContratLocation>
     */
    public function getAppartementContratLocations(): Collection
    {
        return $this->appartContratLocations;
    }

    #[Groups(['group1'])]
    #[SerializedName("maison_id")]
    public function getMaisonId(): ?int
    {
        return $this->maisson ? $this->maisson->getId() : null;
    }

    #[Groups(['group1'])]
    public function isDeletable(): bool
    {
        return !$this->hasHistory() && $this->Oqp === 0;
    }

    #[Groups(['group1'])]
    public function hasHistory(): bool
    {
        return count($this->appartContratLocations) > 0 || count($this->facturelocs) > 0;
    }

    #[Groups(['group1'])]
    public function getLocataire(): ?Locataire
    {
        // On retourne le locataire du dernier contrat (on suppose que c'est le plus récent/actif)
        if ($this->appartContratLocations->isEmpty()) {
            return null;
        }
        return $this->appartContratLocations->last()->getLocataire();
    }
}
