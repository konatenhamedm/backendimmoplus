<?php

namespace App\Entity;

use App\Repository\FacturesFournisseursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacturesFournisseursRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FactureFournisseur
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $date = null;

    #[ORM\Column]
    private ?int $montant = null;

    #[ORM\Column]
    private ?int $etat = null;

    #[ORM\Column]
    private ?int $objet = null;

    #[ORM\Column]
    private ?int $mtPaye = null;

    #[ORM\Column]
    private ?int $solde = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $details = null;

    #[ORM\ManyToOne(inversedBy: 'facturesFournisseurs')]
    private ?Fournisseurs $fournisseur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getEtat(): ?int
    {
        return $this->etat;
    }

    public function setEtat(int $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getObjet(): ?int
    {
        return $this->objet;
    }

    public function setObjet(int $objet): static
    {
        $this->objet = $objet;

        return $this;
    }

    public function getMtPaye(): ?int
    {
        return $this->mtPaye;
    }

    public function setMtPaye(int $mtPaye): static
    {
        $this->mtPaye = $mtPaye;

        return $this;
    }

    public function getSolde(): ?int
    {
        return $this->solde;
    }

    public function setSolde(int $solde): static
    {
        $this->solde = $solde;

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

    public function getFournisseur(): ?Fournisseurs
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseurs $fournisseur): static
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }
}
