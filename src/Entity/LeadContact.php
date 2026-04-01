<?php

namespace App\Entity;

use App\Repository\LeadContactRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeadContactRepository::class)]
class LeadContact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lead:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $company = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $planName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['lead:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 20)]
    #[Groups(['lead:read', 'lead:write'])]
    private ?string $status = 'NEW';

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'NEW';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getPlanName(): ?string
    {
        return $this->planName;
    }

    public function setPlanName(?string $planName): static
    {
        $this->planName = $planName;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
