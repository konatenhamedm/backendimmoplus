<?php

namespace App\Entity;

use App\Entity\Entreprise;
use App\Repository\ModuleGroupePermitionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModuleGroupePermitionRepository::class)]
#[ORM\Table(name: '_admin_param_module_groupe_permition')]
#[UniqueEntity(fields: ['module', 'groupeModule'], errorPath: 'module', message: 'Ce module est deja utilisé pour ce groupe.')]
#[ORM\HasLifecycleCallbacks]
class ModuleGroupePermition
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $ordre = null;

    #[ORM\ManyToOne(inversedBy: 'module')]
    #[Groups(['group1'])]
    private ?Permition $permition = null;

    #[ORM\ManyToOne(inversedBy: 'moduleGroupePermitions')]
    #[Groups(['group1'])]
    private ?Module $module = null;

    #[ORM\ManyToOne(inversedBy: 'moduleGroupePermitions')]
    #[Groups(['group1'])]
    private ?GroupeModule $groupeModule = null;

    #[ORM\ManyToOne(inversedBy: 'moduleGroupePermitions')]
    private ?Groupe $groupeUser = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['group1'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $ordreGroupe = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private ?bool $menuPrincipal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getPermition(): ?Permition
    {
        return $this->permition;
    }

    public function setPermition(?Permition $permition): self
    {
        $this->permition = $permition;

        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getGroupeModule(): ?GroupeModule
    {
        return $this->groupeModule;
    }

    public function setGroupeModule(?GroupeModule $groupeModule): self
    {
        $this->groupeModule = $groupeModule;

        return $this;
    }

    public function getGroupeUser(): ?Groupe
    {
        return $this->groupeUser;
    }

    public function setGroupeUser(?Groupe $groupeUser): self
    {
        $this->groupeUser = $groupeUser;

        return $this;
    }

    public function getOrdreGroupe(): ?int
    {
        return $this->ordreGroupe;
    }

    public function setOrdreGroupe(int $ordreGroupe): self
    {
        $this->ordreGroupe = $ordreGroupe;

        return $this;
    }

    public function isMenuPrincipal(): ?bool
    {
        return $this->menuPrincipal;
    }

    public function setMenuPrincipal(bool $menuPrincipal): self
    {
        $this->menuPrincipal = $menuPrincipal;

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
