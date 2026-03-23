<?php

namespace App\Entity;

use App\Repository\AbonnementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    use TraitEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["group1", "group_abonnement", "group_auth"])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'abonnements')]
    #[Groups(["group_abonnement", "group_auth"])]
    private ?ModuleAbonnement $moduleAbonnement = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1", "group_abonnement", "group_auth"])]
    private ?string $etat = null;

    #[ORM\ManyToOne]
    #[Groups(["group1", "group_abonnement", "group_auth"])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column]
    #[Groups(["group1", "group_abonnement", "group_auth"])]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(length: 255)]
    #[Groups(["group1", "group_abonnement", "group_auth"])]
    private ?string $type = null;

    /**
     * Calcule la date de début de l'abonnement en fonction de la date de fin et de la durée du module
     * 
     * @return \DateTime|null
     */
    #[Groups(["group1", "group_abonnement", "group_auth"])]
    public function getDateDebut(): ?\DateTime
    {
        if ($this->dateFin === null || $this->moduleAbonnement === null) {
            return null;
        }

        $dureeEnJours = (int) $this->moduleAbonnement->getDuree();
        if ($dureeEnJours <= 0) {
            return null;
        }

        // Cloner la date de fin pour ne pas la modifier
        $dateDebut = clone $this->dateFin;
        
        // Soustraire la durée en jours
        $dateDebut->modify("-{$dureeEnJours} days");
        
        return $dateDebut;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModuleAbonnement(): ?ModuleAbonnement
    {
        return $this->moduleAbonnement;
    }

    public function setModuleAbonnement(?ModuleAbonnement $moduleAbonnement): static
    {
        $this->moduleAbonnement = $moduleAbonnement;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

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

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
