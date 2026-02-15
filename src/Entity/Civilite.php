<?php

namespace App\Entity;

use App\Repository\CiviliteRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Source;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CiviliteRepository::class)]
#[UniqueEntity(['code'], message: 'Ce code est déjà utilisé')]
#[ORM\Table(name: '_admin_param_civilite')]
#[Source]
#[ORM\HasLifecycleCallbacks]
class Civilite
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 15)]
    #[Groups(['group1'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 5)]
    #[Groups(['group1'])]
    private ?string $code = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }
}
