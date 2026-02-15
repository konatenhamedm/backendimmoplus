<?php

namespace App\Entity;

use App\Repository\EditableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EditableRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Editable
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $registered = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $html = null;

    #[ORM\Column]
    private ?int $deletable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistered(): ?int
    {
        return $this->registered;
    }

    public function setRegistered(int $registered): static
    {
        $this->registered = $registered;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function getDeletable(): ?int
    {
        return $this->deletable;
    }

    public function setDeletable(int $deletable): static
    {
        $this->deletable = $deletable;

        return $this;
    }
}
