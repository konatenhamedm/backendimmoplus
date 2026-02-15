<?php

namespace App\Entity;

use App\Repository\ConfigAppRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConfigAppRepository::class)]
#[ORM\Table(name: '_admin_param_config_app')]
class ConfigApp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fichier $logo = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fichier $favicon = null;


    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fichier $imageLogin = null;

    #[ORM\ManyToOne(cascade: ["persist"], fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Fichier $logoLogin = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $mainColorAdmin = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $defaultColorAdmin = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $mainColorLogin = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $defaultColorLogin = null;

    #[ORM\ManyToOne(inversedBy: 'configApps')]
    private ?Entreprise $entreprise = null;

    public function getFavicon(): ?Fichier
    {
        return $this->favicon;
    }

    public function setFavicon(?Fichier $favicon): self
    {
        $this->favicon = $favicon;

        return $this;
    }

    public function getLogo(): ?Fichier
    {
        return $this->logo;
    }

    public function setLogo(?Fichier $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }




    public function getImageLogin(): ?Fichier
    {
        return $this->imageLogin;
    }

    public function setImageLogin(?Fichier $imageLogin): self
    {
        $this->imageLogin = $imageLogin;

        return $this;
    }

    public function getMainColorAdmin(): ?string
    {
        return $this->mainColorAdmin;
    }

    public function setMainColorAdmin(string $mainColorAdmin): self
    {
        $this->mainColorAdmin = $mainColorAdmin;

        return $this;
    }

    public function getDefaultColorAdmin(): ?string
    {
        return $this->defaultColorAdmin;
    }

    public function setDefaultColorAdmin(string $defaultColorAdmin): self
    {
        $this->defaultColorAdmin = $defaultColorAdmin;

        return $this;
    }

    public function getMainColorLogin(): ?string
    {
        return $this->mainColorLogin;
    }

    public function setMainColorLogin(string $mainColorLogin): self
    {
        $this->mainColorLogin = $mainColorLogin;

        return $this;
    }

    public function getDefaultColorLogin(): ?string
    {
        return $this->defaultColorLogin;
    }

    public function setDefaultColorLogin(string $defaultColorLogin): self
    {
        $this->defaultColorLogin = $defaultColorLogin;

        return $this;
    }
    public function getLogoLogin(): ?Fichier
    {
        return $this->logoLogin;
    }

    public function setLogoLogin(?Fichier $logoLogin): self
    {
        $this->logoLogin = $logoLogin;

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
