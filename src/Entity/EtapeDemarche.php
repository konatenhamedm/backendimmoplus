<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class EtapeDemarche
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $nomEtape = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1"])]
    private ?string $statut = 'attente'; // attente, en_cours, termine

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["group1"])]
    private ?\DateTimeInterface $dateValidation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["group1"])]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(targetEntity: DemarcheAdministrative::class, inversedBy: 'etapes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["group1"])]
    private ?DemarcheAdministrative $demarche = null;

    /** Lien vers le type de démarche paramétré par l'entreprise */
    #[ORM\ManyToOne(targetEntity: TypeEtapeDemarche::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["group1"])]
    private ?TypeEtapeDemarche $typeEtape = null;

    public function getId(): ?int { return $this->id; }

    /**
     * Retourne le nom de l'étape :
     * - depuis le TypeEtapeDemarche si lié (paramétré)
     * - sinon depuis nomEtape (fallback / rétrocompatibilité)
     */
    public function getNomEtape(): ?string
    {
        return $this->typeEtape?->getNom() ?? $this->nomEtape;
    }
    public function setNomEtape(string $nomEtape): static { $this->nomEtape = $nomEtape; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDateValidation(): ?\DateTimeInterface { return $this->dateValidation; }
    public function setDateValidation(?\DateTimeInterface $dateValidation): static { $this->dateValidation = $dateValidation; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getDemarche(): ?DemarcheAdministrative { return $this->demarche; }
    public function setDemarche(?DemarcheAdministrative $demarche): static { $this->demarche = $demarche; return $this; }

    public function getTypeEtape(): ?TypeEtapeDemarche { return $this->typeEtape; }
    public function setTypeEtape(?TypeEtapeDemarche $typeEtape): static { $this->typeEtape = $typeEtape; return $this; }
}
