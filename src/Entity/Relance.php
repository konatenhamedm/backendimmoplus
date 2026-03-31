<?php

namespace App\Entity;

use App\Repository\RelanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RelanceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Relance
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'group1_relance'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1', 'group1_relance'])]
    private ?string $type = null; // WHATSAPP, SMS, MAIL, CALL, RDV, MISE_EN_DEMEURE

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['group1', 'group1_relance'])]
    private ?string $observation = null;

    #[ORM\ManyToOne(inversedBy: 'relances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1_relance'])]
    private ?FactureLocation $facture = null;

    #[ORM\ManyToOne]
    #[Groups(['group1', 'group1_relance'])]
    private ?User $agent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['group1', 'group1_relance'])]
    private ?\DateTimeInterface $dateEffective = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1_relance'])]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1_relance'])]
    private ?Agence $agence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;

        return $this;
    }

    public function getFacture(): ?FactureLocation
    {
        return $this->facture;
    }

    public function setFacture(?FactureLocation $facture): static
    {
        $this->facture = $facture;

        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getDateEffective(): ?\DateTimeInterface
    {
        return $this->dateEffective;
    }

    public function setDateEffective(\DateTimeInterface $dateEffective): static
    {
        $this->dateEffective = $dateEffective;

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

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;

        return $this;
    }
}
