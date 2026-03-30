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
#[UniqueEntity(fields: ['libAppart', 'maisson_id'],  message: 'Cette campagne existe deja.')]
#[ORM\HasLifecycleCallbacks]
class Appartement
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, name: 'libAppart')]
    #[Assert\NotBlank(message: 'Veuillez renseigner le libellé de la colonne', groups: ['colonne-groupe'])]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?string $libAppart = null;



    #[ORM\Column(type: Types::DECIMAL, precision: 9, scale: '0', name: 'nbrePieces')]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?string $nbrePieces = null;

    #[ORM\Column(name: 'numEtage')]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?int $numEtage = null;


    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: '0', name: 'loyer')]
    #[Assert\Positive(message: 'Le loyer payé doit être > à 0')]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?string $loyer = null;

    #[ORM\Column(nullable: true, name: 'caution')]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?int $caution = null;

    #[ORM\Column(length: 255, name: 'details')]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?string $details = null;

    #[ORM\Column(nullable: true, name: 'oqp')]
    #[Groups(['group1', 'appartement-groupe'])]
    private ?int $oqp = null;

    #[ORM\ManyToOne(inversedBy: 'appartements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1', 'appartement-groupe'])]
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
        $this->oqp = 0;
    }

    public function getNomComplet()
    {
        return $this->maisson->getProprio()->getNom()." ".$this->maisson->getProprio()->getPrenoms() . " - " . $this->maisson->getLibMaison() . " - " . $this->libAppart . " - " . $this->loyer;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibAppart(): ?string
    {
        return $this->libAppart;
    }

    public function setLibAppart(string $libAppart): static
    {
        $this->libAppart = $libAppart;

        return $this;
    }


    public function getNbrePieces(): ?string
    {
        return $this->nbrePieces;
    }

    public function setNbrePieces(string $nbrePieces): static
    {
        $this->nbrePieces = $nbrePieces;

        return $this;
    }

    public function getNumEtage(): ?int
    {
        return $this->numEtage;
    }

    public function setNumEtage(int $numEtage): static
    {
        $this->numEtage = $numEtage;

        return $this;
    }

    public function getLoyer(): ?string
    {
        return $this->loyer;
    }

    public function setLoyer(string $loyer): static
    {
        $this->loyer = $loyer;

        return $this;
    }

    public function getCaution(): ?int
    {
        return $this->caution;
    }

    public function setCaution(int $caution): static
    {
        $this->caution = $caution;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(string $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getOqp(): ?int
    {
        return $this->oqp;
    }

    public function setOqp(int $oqp): static
    {
        $this->oqp = $oqp;

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
        return !$this->hasHistory() && $this->oqp === 0;
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
