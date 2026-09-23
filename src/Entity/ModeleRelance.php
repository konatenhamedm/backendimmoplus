<?php

namespace App\Entity;

use App\Repository\ModeleRelanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Modèle de messages de rappel / relance d'une agence.
 * Chaque agence a un modèle par défaut ; un contrat de location peut être lié à un autre modèle.
 */
#[ORM\Entity(repositoryClass: ModeleRelanceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ModeleRelance
{
    use TraitEntity;

    public const DEFAULT_LIBELLE = 'Modèle par défaut';
    public const DEFAULT_RAPPEL_SUJET = 'Rappel : votre loyer de {mois} arrive à échéance';
    public const DEFAULT_RAPPEL_MESSAGE = "Bonjour {locataire},\n\nNous vous rappelons que votre loyer de {mois} d'un montant de {montant} arrive à échéance le {date_limite} (dans {jours_restants} jour(s)).\n\nMerci de prendre vos dispositions pour effectuer le règlement à temps.\n\nCordialement,\n{agence}\n{agence_contact}";
    public const DEFAULT_RAPPEL_SMS = '{agence} : Bonjour {locataire}, votre loyer de {mois} ({montant}) est attendu le {date_limite}. Merci.';
    public const DEFAULT_RELANCE_SUJET = 'Relance : loyer de {mois} impayé';
    public const DEFAULT_RELANCE_MESSAGE = "Bonjour {locataire},\n\nSauf erreur de notre part, nous n'avons pas reçu le règlement de votre facture {facture} d'un montant restant de {montant}, attendu le {date_limite} ({jours_retard} jour(s) de retard).\n\nNous vous prions de bien vouloir régulariser votre situation dans les plus brefs délais.\n\nCordialement,\n{agence}\n{agence_contact}";
    public const DEFAULT_RELANCE_SMS = '{agence} : Bonjour {locataire}, votre loyer de {mois} ({montant}) est impayé depuis le {date_limite}. Merci de régulariser. {agence_contact}';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['group1', 'group_modele_relance'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 150)]
    #[Groups(['group1', 'group_modele_relance'])]
    private string $libelle = self::DEFAULT_LIBELLE;

    /** Modèle utilisé pour tous les contrats de l'agence qui n'ont pas de modèle propre. */
    #[ORM\Column]
    #[Groups(['group_modele_relance'])]
    private bool $parDefaut = false;

    #[ORM\Column]
    #[Groups(['group_modele_relance'])]
    private bool $actif = true;

    #[ORM\Column(length: 255)]
    #[Groups(['group_modele_relance'])]
    private string $rappelSujet = self::DEFAULT_RAPPEL_SUJET;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['group_modele_relance'])]
    private string $rappelMessage = self::DEFAULT_RAPPEL_MESSAGE;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['group_modele_relance'])]
    private string $rappelSms = self::DEFAULT_RAPPEL_SMS;

    #[ORM\Column(length: 255)]
    #[Groups(['group_modele_relance'])]
    private string $relanceSujet = self::DEFAULT_RELANCE_SUJET;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['group_modele_relance'])]
    private string $relanceMessage = self::DEFAULT_RELANCE_MESSAGE;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['group_modele_relance'])]
    private string $relanceSms = self::DEFAULT_RELANCE_SMS;

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

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function isParDefaut(): bool
    {
        return $this->parDefaut;
    }

    public function setParDefaut(bool $parDefaut): static
    {
        $this->parDefaut = $parDefaut;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getRappelSujet(): string
    {
        return $this->rappelSujet;
    }

    public function setRappelSujet(string $rappelSujet): static
    {
        $this->rappelSujet = $rappelSujet;

        return $this;
    }

    public function getRappelMessage(): string
    {
        return $this->rappelMessage;
    }

    public function setRappelMessage(string $rappelMessage): static
    {
        $this->rappelMessage = $rappelMessage;

        return $this;
    }

    public function getRappelSms(): string
    {
        return $this->rappelSms;
    }

    public function setRappelSms(string $rappelSms): static
    {
        $this->rappelSms = $rappelSms;

        return $this;
    }

    public function getRelanceSujet(): string
    {
        return $this->relanceSujet;
    }

    public function setRelanceSujet(string $relanceSujet): static
    {
        $this->relanceSujet = $relanceSujet;

        return $this;
    }

    public function getRelanceMessage(): string
    {
        return $this->relanceMessage;
    }

    public function setRelanceMessage(string $relanceMessage): static
    {
        $this->relanceMessage = $relanceMessage;

        return $this;
    }

    public function getRelanceSms(): string
    {
        return $this->relanceSms;
    }

    public function setRelanceSms(string $relanceSms): static
    {
        $this->relanceSms = $relanceSms;

        return $this;
    }
}
