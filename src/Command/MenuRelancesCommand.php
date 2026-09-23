<?php

namespace App\Command;

use App\Entity\Groupe;
use App\Entity\GroupeModule;
use App\Entity\Icon;
use App\Entity\Module;
use App\Entity\ModuleGroupePermition;
use App\Entity\Permition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ajoute au menu dynamique les écrans de relance (centre de relances, modèles, paramètres).
 * Sans effet sur ce qui existe déjà : peut être relancée sans créer de doublon.
 */
#[AsCommand(
    name: 'app:menu:relances',
    description: 'Met à jour le menu dynamique : Relances, Modèles de relance et Paramètres relances pour les groupes concernés',
)]
class MenuRelancesCommand extends Command
{
    /** lien => [titre, icône, ordre relatif] */
    private const ECRANS = [
        '/relances' => ['Rélances', 'Send', 0],
        '/relances/modeles' => ['Modèles de relance', 'FileText', 1],
        '/relances/parametres' => ['Paramètres relances', 'Settings', 2],
    ];

    /** Groupes qui gèrent les relances : tous les écrans */
    private const GROUPES_ADMIN = ['SADM', 'ADMIN', 'ADMINAG'];

    /** Groupes qui relancent les locataires sans configurer : centre de relances uniquement */
    private const GROUPES_AGENT = ['AGENT'];

    private SymfonyStyle $io;
    private bool $dryRun = false;

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('module', null, InputOption::VALUE_REQUIRED, "Section du menu utilisée si un groupe n'a pas encore « Rélances »", 'Finances')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->dryRun = (bool) $input->getOption('dry-run');

        $permission = $this->em->getRepository(Permition::class)->findOneBy(['code' => 'CRUD']);
        if (!$permission) {
            $this->io->error('Permission « CRUD » introuvable.');
            return Command::FAILURE;
        }

        $moduleParDefaut = $this->em->getRepository(Module::class)->findOneBy(['titre' => $input->getOption('module')]);

        // 1. Écrans (une seule ligne par lien, partagée par tous les groupes)
        $ecrans = [];
        foreach (self::ECRANS as $lien => [$titre, $icone, $ordre]) {
            $ecrans[$lien] = $this->getOuCreerEcran($lien, $titre, $icone, $ordre + 1);
        }

        // 2. Attribution aux groupes
        $ajouts = 0;
        $groupes = $this->em->getRepository(Groupe::class)->findBy(['code' => [...self::GROUPES_ADMIN, ...self::GROUPES_AGENT]]);
        foreach ($groupes as $groupe) {
            $liens = in_array($groupe->getCode(), self::GROUPES_ADMIN, true) ? array_keys(self::ECRANS) : ['/relances'];

            // Placement : même section et même position que « Rélances » s'il est déjà dans le menu du groupe
            $reference = $this->trouverLigne($groupe, $ecrans['/relances']);
            $module = $reference?->getModule() ?? $moduleParDefaut;
            if (!$module) {
                $this->io->warning("{$groupe->getCode()} : section « {$input->getOption('module')} » introuvable, groupe ignoré (utilisez --module=\"Nom de la section\").");
                continue;
            }
            $ordreBase = $reference?->getOrdre() ?? $this->ordreSuivant($groupe, $module);

            foreach ($liens as $lien) {
                $existe = $this->trouverLigne($groupe, $ecrans[$lien]);
                if ($existe) {
                    continue;
                }

                $ligne = (new ModuleGroupePermition())
                    ->setGroupeUser($groupe)
                    ->setModule($module)
                    ->setGroupeModule($ecrans[$lien])
                    ->setPermition($permission)
                    ->setOrdre($ordreBase + self::ECRANS[$lien][2])
                    ->setOrdreGroupe($module->getOrdre() ?? 0)
                    ->setMenuPrincipal(true);
                $ligne->setIsActive(true);
                $this->persister($ligne);
                $ajouts++;
                $this->io->text("+ {$groupe->getCode()} : {$module->getTitre()} › " . self::ECRANS[$lien][0]);
            }
        }

        if (!$this->dryRun) {
            $this->em->flush();
        }

        $this->io->success($ajouts
            ? ($this->dryRun ? "$ajouts entrée(s) de menu seraient ajoutées (simulation)." : "$ajouts entrée(s) de menu ajoutées. Les utilisateurs doivent se reconnecter pour voir le nouveau menu.")
            : 'Le menu est déjà à jour.');

        return Command::SUCCESS;
    }

    private function getOuCreerEcran(string $lien, string $titre, string $codeIcone, int $ordre): GroupeModule
    {
        $ecran = $this->em->getRepository(GroupeModule::class)->findOneBy(['lien' => $lien]);
        if ($ecran) {
            return $ecran;
        }

        $ecran = (new GroupeModule())
            ->setTitre($titre)
            ->setLien($lien)
            ->setOrdre($ordre)
            ->setIcon($this->getOuCreerIcone($codeIcone));
        $ecran->setIsActive(true);
        $this->persister($ecran);
        $this->io->text("+ Écran « $titre » ($lien)");

        return $ecran;
    }

    private function getOuCreerIcone(string $code): Icon
    {
        $icone = $this->em->getRepository(Icon::class)->findOneBy(['code' => $code]);
        if ($icone) {
            return $icone;
        }

        $icone = (new Icon())->setCode($code)->setLibelle($code)->setImage('');
        $this->persister($icone);

        return $icone;
    }

    private function trouverLigne(Groupe $groupe, GroupeModule $ecran): ?ModuleGroupePermition
    {
        // Écran pas encore enregistré (simulation) : aucune attribution possible
        if (!$ecran->getId()) {
            return null;
        }

        return $this->em->getRepository(ModuleGroupePermition::class)->findOneBy(['groupeUser' => $groupe, 'groupeModule' => $ecran]);
    }

    private function ordreSuivant(Groupe $groupe, Module $module): int
    {
        $max = $this->em->createQueryBuilder()
            ->select('MAX(m.ordre)')
            ->from(ModuleGroupePermition::class, 'm')
            ->where('m.groupeUser = :groupe AND m.module = :module')
            ->setParameter('groupe', $groupe)
            ->setParameter('module', $module)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    private function persister(object $entite): void
    {
        if (!$this->dryRun) {
            $this->em->persist($entite);
        }
    }
}
