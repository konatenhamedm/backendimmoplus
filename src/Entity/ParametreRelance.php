<?php

namespace App\Entity;

use App\Repository\ParametreRelanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Mode d'envoi, canaux et délais des rappels / relances, propres à chaque agence.
 * Les textes des messages sont dans ModeleRelance.
 */
#[ORM\Entity(repositoryClass: ParametreRelanceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ParametreRelance
{
    use TraitEntity;

    public const MODE_AUTOMATIQUE = 'AUTOMATIQUE';
    public const MODE_MANUEL = 'MANUEL';

    public const CANAL_EMAIL = 'EMAIL';
    public const CANAL_SMS = 'SMS';
    public const CANAL_NOTIFICATION = 'NOTIFICATION';
    public const CANAUX = [self::CANAL_EMAIL, self::CANAL_SMS, self::CANAL_NOTIFICATION];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1'])]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Entreprise $entreprise = null;

    /** AUTOMATIQUE : envoi par la tâche planifiée ; MANUEL : l'agence lance elle-même les envois. */
    #[ORM\Column(length: 20)]
    #[Groups(['group1'])]
    private string $mode = self::MODE_MANUEL;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['group1'])]
    private array $canaux = [self::CANAL_EMAIL, self::CANAL_SMS];

    #[ORM\Column]
    #[Groups(['group1'])]
    private bool $rappelActif = true;

    /** Nombre de jours avant la date limite pour envoyer le rappel. */
    #[ORM\Column]
    #[Groups(['group1'])]
    private int $joursAvantEcheance = 3;

    #[ORM\Column]
    #[Groups(['group1'])]
    private bool $relanceActif = true;

    /** Paliers de relance, en jours de retard après la date limite (ex : [1, 7, 15]). */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['group1'])]
    private array $joursApresEcheance = [1, 7, 15];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(Agence $agence): static
    {
        $this->agence = $agence;

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

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode === self::MODE_AUTOMATIQUE ? self::MODE_AUTOMATIQUE : self::MODE_MANUEL;

        return $this;
    }

    public function isAutomatique(): bool
    {
        return $this->mode === self::MODE_AUTOMATIQUE;
    }

    public function getCanaux(): array
    {
        return $this->canaux;
    }

    public function setCanaux(array $canaux): static
    {
        $this->canaux = array_values(array_intersect(self::CANAUX, $canaux));

        return $this;
    }

    public function isRappelActif(): bool
    {
        return $this->rappelActif;
    }

    public function setRappelActif(bool $rappelActif): static
    {
        $this->rappelActif = $rappelActif;

        return $this;
    }

    public function getJoursAvantEcheance(): int
    {
        return $this->joursAvantEcheance;
    }

    public function setJoursAvantEcheance(int $jours): static
    {
        $this->joursAvantEcheance = max(1, min(30, $jours));

        return $this;
    }

    public function isRelanceActif(): bool
    {
        return $this->relanceActif;
    }

    public function setRelanceActif(bool $relanceActif): static
    {
        $this->relanceActif = $relanceActif;

        return $this;
    }

    public function getJoursApresEcheance(): array
    {
        return $this->joursApresEcheance;
    }

    public function setJoursApresEcheance(array $jours): static
    {
        $jours = array_unique(array_filter(array_map('intval', $jours), fn (int $j) => $j >= 1 && $j <= 365));
        sort($jours);
        $this->joursApresEcheance = array_values($jours);

        return $this;
    }
}
