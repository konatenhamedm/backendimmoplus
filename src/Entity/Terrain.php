<?php

namespace App\Entity;

use App\Repository\TerrainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations




#[ORM\Entity(repositoryClass: TerrainRepository::class)]
#[UniqueEntity(['num'], message: 'Ce numéro est déjà utilisé')]
#[ORM\HasLifecycleCallbacks]
class Terrain
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $num = null;

    #[ORM\Column(length: 255)]
    private ?string $superfice = null;



    #[ORM\Column(length: 255)]
    private ?string $prix = null;

    // #[ORM\Column(length: 255)]
    // private ?string $justification = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomcl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telcl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisationClient = null;



    #[ORM\OneToMany(mappedBy: 'terrain', targetEntity: CompteCltT::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $compteCltTs;

    #[ORM\ManyToOne(inversedBy: 'terrain', cascade: ['persist'])]
    private ?Site $site = null;


    #[ORM\Column(type: 'string', length: 255)]
    private string $etat = 'disponible';

    #[ORM\OneToMany(mappedBy: 'terrain', targetEntity: Echancier::class, cascade: ['persist', 'remove'])]
    private Collection $echancier;

    public function __construct()
    {
        $this->compteCltTs = new ArrayCollection();
        $this->echancier = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNum(): ?string
    {
        return $this->num;
    }

    public function setNum(string $num): static
    {
        $this->num = $num;

        return $this;
    }
    public function getSuperfice(): ?string
    {
        return $this->superfice;
    }

    public function setSuperfice(string $superfice): static
    {
        $this->superfice = $superfice;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    // public function getJustification(): ?string
    // {
    //     return $this->justification;
    // }

    // public function setJustification(string $justification): static
    // {
    //     $this->justification = $justification;

    //     return $this;
    // }

    public function getnomcl(): ?string
    {
        return $this->nomcl;
    }

    public function setnomcl(string $nomcl): static
    {
        $this->nomcl = $nomcl;

        return $this;
    }

    public function gettelcl(): ?string
    {
        return $this->telcl;
    }

    public function settelcl(string $telcl): static
    {
        $this->telcl = $telcl;

        return $this;
    }

    public function getLocalisationClient(): ?string
    {
        return $this->localisationClient;
    }

    public function setLocalisationClient(string $localisationClient): static
    {
        $this->localisationClient = $localisationClient;

        return $this;
    }

    /**
     * @return Collection<int, CompteCltT>
     */
    public function getCompteCltTs(): Collection
    {
        return $this->compteCltTs;
    }

    public function addCompteCltT(CompteCltT $compteCltT): static
    {
        if (!$this->compteCltTs->contains($compteCltT)) {
            $this->compteCltTs->add($compteCltT);
            $compteCltT->setTerrain($this);
        }

        return $this;
    }

    public function removeCompteCltT(CompteCltT $compteCltT): static
    {
        if ($this->compteCltTs->removeElement($compteCltT)) {
            // set the owning side to null (unless already changed)
            if ($compteCltT->getTerrain() === $this) {
                $compteCltT->setTerrain(null);
            }
        }

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }


    /**
     * @return Collection<int, Echancier>
     */
    public function getEchancier(): Collection
    {
        return $this->echancier;
    }

    public function addEchancier(Echancier $echancier): static
    {
        if (!$this->echancier->contains($echancier)) {
            $this->echancier->add($echancier);
            $echancier->setTerrain($this);
        }

        return $this;
    }

    public function removeEchancier(Echancier $echancier): static
    {
        if ($this->echancier->removeElement($echancier)) {
            // set the owning side to null (unless already changed)
            if ($echancier->getTerrain() === $this) {
                $echancier->setTerrain(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->num;
    }
}
