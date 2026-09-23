<?php

namespace App\Command;

use App\Entity\Groupe;
use App\Entity\GroupeModule;
use App\Entity\Icon;
use App\Entity\Module;
use App\Entity\ModuleAbonnement;
use App\Entity\ModuleGroupePermition;
use App\Entity\ModuleMetier;
use App\Entity\Permition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Initialise les grands modules d'abonnement (Loyers, Résidences, Terrains) sur une base existante.
 * Ne modifie pas ce qui est déjà paramétré : peut être relancée sans risque.
 */
#[AsCommand(
    name: 'app:modules-metier:init',
    description: "Crée les grands modules (Loyers, Résidences, Terrains), les rattache aux sections du menu et aux formules d'abonnement",
)]
class ModulesMetierInitCommand extends Command
{
    private const MODULES = [
        ModuleMetier::LOYERS => [
            'libelle' => 'Gestion des loyers',
            'icone' => 'Home',
            'ordre' => 1,
            'description' => 'Biens, propriétaires, locataires, contrats, factures de loyer, versements et relances.',
            'sections' => ['Gestion Immobilière', 'Gestion Locative', 'Finances', 'Immobilier', 'Contrats & Finances', 'Espace Locataire', 'Gestion agent'],
            'prefixes' => [
                '/api/maison', '/api/appartement', '/api/type-maison', '/api/proprio', '/api/locataire',
                '/api/contrat-location', '/api/etat-lieux', '/api/facture-location', '/api/campagne',
                '/api/versement-proprio', '/api/charge-proprio', '/api/ligne-versement-frais',
                '/api/relances', '/api/modele-relance', '/api/parametre-relance',
                '/api/planning-recouvrement', '/api/suivi-contact',
            ],
        ],
        ModuleMetier::RESIDENCES => [
            'libelle' => 'Gestion des résidences',
            'icone' => 'Hotel',
            'ordre' => 2,
            'description' => 'Résidences meublées : réservations, loyers et dépenses de résidence.',
            'sections' => ['Résidences', 'Gestion Résidence', 'Gestion Résidences', 'Gestion des résidences'],
            'liens' => ['/residence', '/reservation', '/depense-residence', '/loyer-residence'],
            'prefixes' => ['/api/residence', '/api/reservation-residence', '/api/loyer-residence', '/api/depense-residence'],
        ],
        ModuleMetier::TERRAINS => [
            'libelle' => 'Gestion des terrains',
            'icone' => 'MapPin',
            'ordre' => 3,
            'description' => 'Sites, lots, clients, ventes de terrains et démarches.',
            'sections' => ['Gestion Terrains', 'Gestion des terrains', 'Terrains'],
            'liens' => ['/gestion-terrains'],
            'prefixes' => [
                '/api/site', '/api/terrain', '/api/client-terrain', '/api/vente-terrain',
                '/api/compte-clt-t', '/api/type-etape-demarche', '/api/type-frais-terrain',
            ],
        ],
    ];

    /** Écrans du module Terrains (auparavant ajoutés en dur dans la barre latérale du front). */
    private const MENU_TERRAINS = [
        ['Tableau de bord', '/gestion-terrains/statistiques', 'PieChart'],
        ['Sites', '/gestion-terrains/sites', 'MapPin'],
        ['Lots', '/gestion-terrains/lots', 'LayoutGrid'],
        ['Clients', '/gestion-terrains/clients', 'Users'],
        ['Ventes', '/gestion-terrains/ventes', 'Handshake'],
        ['Démarches', '/gestion-terrains/demarches', 'GanttChartSquare'],
        ["Types d'étapes", '/gestion-terrains/parametres/etapes', 'GanttChartSquare'],
        ['Types de frais', '/gestion-terrains/parametres/frais', 'Coins'],
    ];

    private const GROUPES_MENU_TERRAINS = ['SADM', 'ADMIN', 'ADMINAG'];

    private SymfonyStyle $io;

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sans-menu-terrains', null, InputOption::VALUE_NONE, 'Ne pas créer la section « Gestion Terrains » dans le menu')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $connexion = $this->em->getConnection();
        $connexion->beginTransaction();
        try {
            $modules = $this->creerModules();
            if (!$input->getOption('sans-menu-terrains')) {
                $this->creerMenuTerrains();
            }
            $this->rattacherSections($modules);
            $this->rattacherFormules($modules);
            $this->em->flush();

            if ($dryRun) {
                $connexion->rollBack();
                $this->io->note('Simulation : rien n\'a été enregistré.');
            } else {
                $connexion->commit();
                $this->io->success('Grands modules initialisés. Les utilisateurs doivent se reconnecter pour voir leur nouveau menu.');
            }
        } catch (\Throwable $e) {
            $connexion->rollBack();
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /** @return array<string, ModuleMetier> */
    private function creerModules(): array
    {
        $this->io->section('Grands modules');
        $modules = [];
        foreach (self::MODULES as $code => $config) {
            $module = $this->em->getRepository(ModuleMetier::class)->findOneBy(['code' => $code]);
            if ($module) {
                $this->io->text("= $code existe déjà (non modifié)");
            } else {
                $module = (new ModuleMetier())
                    ->setCode($code)
                    ->setLibelle($config['libelle'])
                    ->setIcone($config['icone'])
                    ->setOrdre($config['ordre'])
                    ->setDescription($config['description'])
                    ->setPrefixesApi($config['prefixes']);
                $module->setIsActive(true);
                $this->em->persist($module);
                $this->io->text("+ $code : {$config['libelle']} (" . count($config['prefixes']) . ' routes API protégées)');
            }
            $modules[$code] = $module;
        }

        return $modules;
    }

    private function creerMenuTerrains(): void
    {
        $this->io->section('Menu « Gestion Terrains »');
        foreach ($this->em->getRepository(Module::class)->findAll() as $existante) {
            if ($this->contientLien($existante, ['/gestion-terrains'])) {
                $this->io->text("= Section « {$existante->getTitre()} » contient déjà les écrans Terrains : pas de nouvelle section.");
                return;
            }
        }
        $section = $this->em->getRepository(Module::class)->findOneBy(['titre' => 'Gestion Terrains']);
        if (!$section) {
            $ordre = (int) $this->em->createQueryBuilder()->select('MAX(m.ordre)')->from(Module::class, 'm')->getQuery()->getSingleScalarResult();
            $section = (new Module())->setTitre('Gestion Terrains')->setOrdre($ordre + 1)->setIcon($this->icone('MapPin'));
            $section->setIsActive(true);
            $this->em->persist($section);
            $this->io->text('+ Section « Gestion Terrains »');
        }

        $permission = $this->em->getRepository(Permition::class)->findOneBy(['code' => 'CRUD']);
        $groupes = $this->em->getRepository(Groupe::class)->findBy(['code' => self::GROUPES_MENU_TERRAINS]);

        foreach (self::MENU_TERRAINS as $ordre => [$titre, $lien, $icone]) {
            $ecran = $this->em->getRepository(GroupeModule::class)->findOneBy(['lien' => $lien]);
            if (!$ecran) {
                $ecran = (new GroupeModule())->setTitre($titre)->setLien($lien)->setOrdre($ordre + 1)->setIcon($this->icone($icone));
                $ecran->setIsActive(true);
                $this->em->persist($ecran);
                $this->em->flush();
            }

            foreach ($groupes as $groupe) {
                if ($this->em->getRepository(ModuleGroupePermition::class)->findOneBy(['groupeUser' => $groupe, 'groupeModule' => $ecran])) {
                    continue;
                }
                $ligne = (new ModuleGroupePermition())
                    ->setGroupeUser($groupe)
                    ->setModule($section)
                    ->setGroupeModule($ecran)
                    ->setPermition($permission)
                    ->setOrdre($ordre + 1)
                    ->setOrdreGroupe($section->getOrdre())
                    ->setMenuPrincipal(true);
                $ligne->setIsActive(true);
                $this->em->persist($ligne);
                $this->io->text("+ {$groupe->getCode()} : Gestion Terrains › $titre");
            }
        }
        $this->em->flush();
    }

    /** @param array<string, ModuleMetier> $modules */
    private function rattacherSections(array $modules): void
    {
        $this->io->section('Sections du menu');
        foreach ($this->em->getRepository(Module::class)->findBy([], ['ordre' => 'ASC']) as $section) {
            if ($section->getModuleMetier()) {
                $this->io->text("= {$section->getTitre()} → {$section->getModuleMetier()->getCode()} (déjà rattachée)");
                continue;
            }
            $titre = $this->normaliser($section->getTitre());
            foreach (self::MODULES as $code => $config) {
                $parTitre = in_array($titre, array_map(fn ($t) => $this->normaliser($t), $config['sections']), true);
                $parLien = !$parTitre && isset($config['liens']) && $this->contientLien($section, $config['liens']);
                if ($parTitre || $parLien) {
                    $section->setModuleMetier($modules[$code]);
                    $this->io->text("+ {$section->getTitre()} → $code" . ($parLien ? ' (détectée par ses écrans)' : ''));
                    continue 2;
                }
            }
            $this->io->text("  {$section->getTitre()} : commune (visible quel que soit l'abonnement)");
        }
    }

    /** @param array<string, ModuleMetier> $modules */
    private function rattacherFormules(array $modules): void
    {
        $this->io->section("Formules d'abonnement");
        foreach ($this->em->getRepository(ModuleAbonnement::class)->findAll() as $formule) {
            if (!$formule->getModulesMetier()->isEmpty()) {
                $codes = $formule->getModulesMetier()->map(fn (ModuleMetier $m) => $m->getCode())->getValues();
                $this->io->text("= {$formule->getCode()} : " . implode(', ', $codes) . ' (déjà paramétrée)');
                continue;
            }

            // Reprise des anciens indicateurs de la formule
            $inclus = array_filter([
                $formule->isHasGestionImmobiliere() !== false ? $modules[ModuleMetier::LOYERS] : null,
                $formule->isHasGestionResidence() ? $modules[ModuleMetier::RESIDENCES] : null,
                $formule->isHasGestionTerrains() ? $modules[ModuleMetier::TERRAINS] : null,
            ]);
            $formule->setModulesMetier(array_values($inclus));
            $this->io->text("+ {$formule->getCode()} : " . implode(', ', array_map(fn (ModuleMetier $m) => $m->getCode(), $inclus)));
        }
    }

    /** Vrai si un écran de la section (pour n'importe quel groupe) a un lien commençant par l'un des préfixes. */
    private function contientLien(Module $section, array $prefixes): bool
    {
        if (!$section->getId()) {
            return false;
        }
        $liens = $this->em->createQueryBuilder()
            ->select('DISTINCT gm.lien')
            ->from(ModuleGroupePermition::class, 'mgp')
            ->join('mgp.groupeModule', 'gm')
            ->where('mgp.module = :section')
            ->setParameter('section', $section)
            ->getQuery()
            ->getSingleColumnResult();

        foreach ($liens as $lien) {
            foreach ($prefixes as $prefixe) {
                if ($lien === $prefixe || str_starts_with((string) $lien, $prefixe . '/') || str_starts_with((string) $lien, $prefixe . '-')) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Minuscules, sans accents ni espaces superflus : « Gestion Résidence » = « gestion residence ». */
    private function normaliser(?string $texte): string
    {
        $texte = mb_strtolower(trim((string) $texte));
        $texte = strtr($texte, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ç' => 'c']);

        return preg_replace('/\s+/', ' ', $texte);
    }

    private function icone(string $code): Icon
    {
        $icone = $this->em->getRepository(Icon::class)->findOneBy(['code' => $code]);
        if (!$icone) {
            $icone = (new Icon())->setCode($code)->setLibelle($code)->setImage('');
            $this->em->persist($icone);
        }

        return $icone;
    }
}
