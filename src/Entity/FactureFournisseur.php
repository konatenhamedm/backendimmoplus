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
    private ?int $Montant = null;

    #[ORM\Column]
    private ?int $etat = null;

    #[ORM\Column]
    private ?int $Objet = null;

    #[ORM\Column]
    private ?int $MtPaye = null;

    #[ORM\Column]
    private ?int $Solde = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $Details = null;

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
        return $this->Montant;
    }

    public function setMontant(int $Montant): static
    {
        $this->Montant = $Montant;

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
        return $this->Objet;
    }

    public function setObjet(int $Objet): static
    {
        $this->Objet = $Objet;

        return $this;
    }

    public function getMtPaye(): ?int
    {
        return $this->MtPaye;
    }

    public function setMtPaye(int $MtPaye): static
    {
        $this->MtPaye = $MtPaye;

        return $this;
    }

    public function getSolde(): ?int
    {
        return $this->Solde;
    }

    public function setSolde(int $Solde): static
    {
        $this->Solde = $Solde;

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
