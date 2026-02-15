<?php

namespace App\Entity;

use App\Repository\IconRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: IconRepository::class)]
#[ORM\Table(name: '_admin_param_icon')]
#[ORM\HasLifecycleCallbacks]
class Icon
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $code = null;

    #[ORM\Column(length: 255,nullable:true)]
    #[Groups(['group1'])]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'icon', targetEntity: GroupeModule::class)]
    #[Ignore]
    private Collection $groupeModules;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $libelle = null;

    public function __construct()
    {
        $this->groupeModules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return Collection<int, GroupeModule>
     */
    public function getGroupeModules(): Collection
    {
        return $this->groupeModules;
    }

    public function addGroupeModule(GroupeModule $groupeModule): self
    {
        if (!$this->groupeModules->contains($groupeModule)) {
            $this->groupeModules->add($groupeModule);
            $groupeModule->setIcon($this);
        }

        return $this;
    }

    public function removeGroupeModule(GroupeModule $groupeModule): self
    {
        if ($this->groupeModules->removeElement($groupeModule)) {
            // set the owning side to null (unless already changed)
            if ($groupeModule->getIcon() === $this) {
                $groupeModule->setIcon(null);
            }
        }

        return $this;
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
}
