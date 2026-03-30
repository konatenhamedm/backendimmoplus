<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]

#[UniqueEntity(['code'], message: 'Ce code est déjà utilisé')]
#[ORM\Table(name:'_admin_user_groupe')]
#[ORM\HasLifecycleCallbacks]
class Groupe
{
    use TraitEntity;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['group1'])]
    private array $roles = [];

   /* #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groupes')]
    private Collection $utilisateurs;*/



    #[ORM\OneToMany(mappedBy: 'groupeUser', targetEntity: ModuleGroupePermition::class ,orphanRemoval: true, cascade:['persist'])]
    private Collection $moduleGroupePermitions;

    #[ORM\OneToMany(mappedBy: 'groupe', targetEntity: User::class)]
    #[Ignore]
    private Collection $utilisateurs;

    #[ORM\Column(length: 255)]
    #[Groups(['group1'])]
    private ?string $code = null;



    public function __construct()
    {
        $this->moduleGroupePermitions = new ArrayCollection();
        $this->utilisateurs = new ArrayCollection();
        $this->setRoles(['ROLE_USER']);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }


    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }


    public function setRoles(array $roles): self
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }
        return $this;
    }

   
    public function addRole($role)
    {
        $role = strtoupper($role);
        if (!$this->hasRole($role)) {
            $this->roles[] = $role;
        }

        return $this;
    }

   
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->roles, true);
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
            $moduleGroupePermition->setGroupeUser($this);
        }

        return $this;
    }

    public function removeModuleGroupePermition(ModuleGroupePermition $moduleGroupePermition): self
    {
        if ($this->moduleGroupePermitions->removeElement($moduleGroupePermition)) {
            // set the owning side to null (unless already changed)
            if ($moduleGroupePermition->getGroupeUser() === $this) {
                $moduleGroupePermition->setGroupeUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUser(User $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
            $utilisateur->setGroupe($this);
        }

        return $this;
    }

    public function removeUser(User $utilisateur): self
    {
        if ($this->utilisateurs->removeElement($utilisateur)) {
            // set the owning side to null (unless already changed)
            if ($utilisateur->getGroupe() === $this) {
                $utilisateur->setGroupe(null);
            }
        }

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


}
