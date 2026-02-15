<?php

namespace App\Entity;

use App\Repository\ModuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: '_admin_param_module')]
#[ORM\HasLifecycleCallbacks]
class Module
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $titre = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $ordre = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "icon_id", referencedColumnName: "id")]
    #[Groups(['group1'])]
    private ?Icon $icon = null;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: ModuleGroupePermition::class)]
    #[Ignore]
    private Collection $moduleGroupePermitions;

    public function __construct()
    {
        $this->moduleGroupePermitions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
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

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return Collection<int, ModuleGroupePermition>
     */
    public function getModuleGroupePermitions(): Collection
    {
        return $this->moduleGroupePermitions;
    }

    public function addModuleGroupePermition(ModuleGroupePermition $moduleGroupePermition): self
    {
        if (!$this->moduleGroupePermitions->contains($moduleGroupePermition)) {
            $this->moduleGroupePermitions->add($moduleGroupePermition);
            $moduleGroupePermition->setModule($this);
        }

        return $this;
    }

    public function removeModuleGroupePermition(ModuleGroupePermition $moduleGroupePermition): self
    {
        if ($this->moduleGroupePermitions->removeElement($moduleGroupePermition)) {
            // set the owning side to null (unless already changed)
            if ($moduleGroupePermition->getModule() === $this) {
                $moduleGroupePermition->setModule(null);
            }
        }

        return $this;
    }
}
