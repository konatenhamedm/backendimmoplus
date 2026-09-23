<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\ModuleAbonnement;
use App\Entity\ModuleMetier;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\ModuleMetierRepository;

/**
 * Grands modules (Loyers, Résidences, Terrains…) accessibles selon l'abonnement de l'entreprise.
 *
 * Pas de restriction pour le super administrateur (SADM), pour un utilisateur sans entreprise,
 * pour une entreprise sans formule, ni tant qu'aucun grand module n'est paramétré.
 */
class AccesModulesService
{
    /** @var array<int, ?string[]> codes autorisés par entreprise (null = aucune restriction), le temps de la requête */
    private array $cache = [];

    /** @var ModuleMetier[]|null */
    private ?array $modulesActifs = null;

    public function __construct(
        private ModuleMetierRepository $moduleMetierRepository,
        private AbonnementRepository $abonnementRepository,
        private ModuleAbonnementRepository $moduleAbonnementRepository,
    ) {
    }

    /** @return ModuleMetier[] */
    public function getModulesActifs(): array
    {
        return $this->modulesActifs ??= $this->moduleMetierRepository->findActifs();
    }

    /**
     * Codes des grands modules autorisés, ou null si l'utilisateur n'est pas restreint.
     *
     * @return string[]|null
     */
    public function getCodesAutorises(?User $user): ?array
    {
        if (!$user || $user->getGroupe()?->getCode() === 'SADM' || !$user->getEntreprise()) {
            return null;
        }

        return $this->getCodesAutorisesEntreprise($user->getEntreprise());
    }

    /** @return string[]|null */
    public function getCodesAutorisesEntreprise(Entreprise $entreprise): ?array
    {
        $id = $entreprise->getId();
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        $formule = $this->getFormule($entreprise);
        if (!$formule || !$this->getModulesActifs()) {
            return $this->cache[$id] = null;
        }

        $codes = [];
        foreach ($formule->getModulesMetier() as $module) {
            if ($module->isActive()) {
                $codes[] = $module->getCode();
            }
        }

        return $this->cache[$id] = $codes;
    }

    public function estAutorise(?User $user, ?ModuleMetier $module): bool
    {
        if (!$module) {
            return true; // section ou route commune
        }
        $codes = $this->getCodesAutorises($user);

        return $codes === null || in_array($module->getCode(), $codes, true);
    }

    /** Grand module non inclus dans l'abonnement dont relève cette route API, ou null si elle est accessible. */
    public function getModuleInterdit(?User $user, string $chemin): ?ModuleMetier
    {
        $codes = $this->getCodesAutorises($user);
        if ($codes === null) {
            return null;
        }

        foreach ($this->getModulesActifs() as $module) {
            if (in_array($module->getCode(), $codes, true)) {
                continue;
            }
            foreach ($module->getPrefixesApi() as $prefixe) {
                if ($chemin === $prefixe || str_starts_with($chemin, rtrim($prefixe, '/') . '/')) {
                    return $module;
                }
            }
        }

        return null;
    }

    private function getFormule(Entreprise $entreprise): ?ModuleAbonnement
    {
        $abonnement = $this->abonnementRepository->findOneBy(['entreprise' => $entreprise, 'etat' => 'ACTIF']);
        if ($abonnement?->getModuleAbonnement()) {
            return $abonnement->getModuleAbonnement();
        }

        return $entreprise->getAbonnement() ? $this->moduleAbonnementRepository->findOneBy(['code' => $entreprise->getAbonnement()]) : null;
    }
}
