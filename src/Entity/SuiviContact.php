<?php

namespace App\Entity;

use App\Repository\SuiviContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SuiviContactRepository::class)]
class SuiviContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Locataire::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?Locataire $locataire = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['group1'])]
    private ?User $agent = null;

    #[ORM\Column(length: 50)]
    #[Groups(['group1'])]
    private ?string $typeContact = 'APPEL'; // 'APPEL', 'WHATSAPP', 'MAIL', 'TERRAIN'

    #[ORM\Column(length: 100)]
    #[Groups(['group1'])]
    private ?string $statusAction = 'RELANCÉ'; // 'Promesse de paiement', 'Injoignable', 'Rendez-vous', etc.

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['group1'])]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $dateContact = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['group1'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    public function __construct()
    {
        $this->dateContact = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocataire(): ?Locataire
    {
        return $this->locataire;
    }

    public function setLocataire(?Locataire $locataire): self
    {
        $this->locataire = $locataire;
        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;
        return $this;
    }

    public function getTypeContact(): ?string
    {
        return $this->typeContact;
    }

    public function setTypeContact(string $typeContact): self
    {
        $this->typeContact = $typeContact;
        return $this;
    }

    public function getStatusAction(): ?string
    {
        return $this->statusAction;
    }

    public function setStatusAction(string $statusAction): self
    {
        $this->statusAction = $statusAction;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDateContact(): ?\DateTimeInterface
    {
        return $this->dateContact;
    }

    public function setDateContact(\DateTimeInterface $dateContact): self
    {
        $this->dateContact = $dateContact;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }
}
