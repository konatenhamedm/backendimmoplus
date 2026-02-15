<?php

namespace App\Entity;

use App\Repository\CompteCltTRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


#[ORM\Entity(repositoryClass: CompteCltTRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CompteCltT
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $solde = null;



    #[ORM\ManyToOne(inversedBy: 'compteCltTs', cascade: ['persist'])]
    private ?Terrain $terrain = null;


    #[ORM\OneToMany(mappedBy: 'comptecltT', targetEntity: Ligneversementfrais::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $ligneversementfrais;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $datecreation = null;

    public function __construct()
    {
        $this->ligneversementfrais = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getSolde(): ?string
    {
        return $this->solde;
    }

    public function setSolde(string $solde): static
    {
        $this->solde = $solde;

        return $this;
    }



    public function getTerrain(): ?Terrain
    {
        return $this->terrain;
    }

    public function setTerrain(?Terrain $terrain): static
    {
        $this->terrain = $terrain;

        return $this;
    }

    /**
     * @return Collection<int, Ligneversementfrais>
     */
    public function getLigneversementfrais(): Collection
    {
        return $this->ligneversementfrais;
    }

    public function addLigneversementfrai(Ligneversementfrais $ligneversementfrai): static
    {
        if (!$this->ligneversementfrais->contains($ligneversementfrai)) {
            $this->ligneversementfrais->add($ligneversementfrai);
            $ligneversementfrai->setComptecltT($this);
        }

        return $this;
    }

    public function removeLigneversementfrai(Ligneversementfrais $ligneversementfrai): static
    {
        if ($this->ligneversementfrais->removeElement($ligneversementfrai)) {
            // set the owning side to null (unless already changed)
            if ($ligneversementfrai->getComptecltT() === $this) {
                $ligneversementfrai->setComptecltT(null);
            }
        }

        return $this;
    }


    public function getDatecreation(): ?\DateTimeInterface
    {
        return $this->datecreation;
    }

    public function setDatecreation(?\DateTimeInterface $datecreation): static
    {
        $this->datecreation = $datecreation;

        return $this;
    }
}
