<?php

namespace App\Entity;

use App\Repository\FonctionRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Attribute\Source;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FonctionRepository::class)]
#[ORM\Table(name:'_admin_param_fonction')]
#[Source]
#[ORM\HasLifecycleCallbacks]
class Fonction
{
    use TraitEntity;

    const DEFAULT_CHOICE_LABEL = 'libelle';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['group1'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 10, unique: true)]
    #[Groups(['group1'])]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'fonctions')]
    private ?Entreprise $entreprise = null;

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

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }
}
