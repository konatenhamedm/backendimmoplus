<?php

namespace App\Command;

use App\Entity\Abonnement;
use App\Entity\Module;
use App\Entity\ModuleGroupePermition;
use App\Entity\User;
use App\Service\AccesModulesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:modules-metier:diagnostic',
    description: "Explique quelles sections du menu un utilisateur voit selon l'abonnement de son entreprise",
)]
class ModulesMetierDiagnosticCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private AccesModulesService $accesModules,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('login', InputArgument::REQUIRED, "Login (email) de l'utilisateur");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = $this->em->getRepository(User::class)->findOneBy(['login' => $input->getArgument('login')]);
        if (!$user) {
            $io->error('Utilisateur introuvable.');
            return Command::FAILURE;
        }

        $entreprise = $user->getEntreprise();
        $abonnementActif = $entreprise ? $this->em->getRepository(Abonnement::class)->findOneBy(['entreprise' => $entreprise, 'etat' => 'ACTIF']) : null;
        $codes = $this->accesModules->getCodesAutorises($user);

        $io->definitionList(
            ['Groupe' => $user->getGroupe()?->getCode() ?? '—'],
            ['Entreprise' => $entreprise ? "{$entreprise->getDenomination()} (#{$entreprise->getId()})" : '—'],
            ['Abonnement ACTIF' => $abonnementActif?->getModuleAbonnement()?->getCode() ?? 'aucun'],
            ['Code abonnement (entreprise)' => $entreprise?->getAbonnement() ?? '—'],
            ['Grands modules paramétrés' => implode(', ', array_map(fn ($m) => $m->getCode(), $this->accesModules->getModulesActifs())) ?: 'aucun'],
            ['Modules autorisés' => $codes === null ? 'AUCUNE RESTRICTION' : (implode(', ', $codes) ?: 'aucun')],
        );

        if ($codes === null) {
            $io->warning(match (true) {
                $user->getGroupe()?->getCode() === 'SADM' => 'Super administrateur : voit tout.',
                !$entreprise => "L'utilisateur n'a pas d'entreprise.",
                !$this->accesModules->getModulesActifs() => "Aucun grand module actif : lancez app:modules-metier:init.",
                default => "Aucune formule trouvée pour l'entreprise (pas d'abonnement ACTIF et code d'abonnement inconnu).",
            });
        }

        // Sections du menu de son groupe
        $sections = $this->em->createQueryBuilder()
            ->select('DISTINCT m.id', 'm.titre')
            ->from(ModuleGroupePermition::class, 'mgp')
            ->join('mgp.module', 'm')
            ->where('mgp.groupeUser = :groupe')
            ->setParameter('groupe', $user->getGroupe())
            ->orderBy('m.titre')
            ->getQuery()
            ->getArrayResult();

        $lignes = [];
        foreach ($sections as $s) {
            $module = $this->em->getRepository(Module::class)->find($s['id'])?->getModuleMetier();
            $visible = !$module || !$module->isActive() || $codes === null || in_array($module->getCode(), $codes, true);
            $lignes[] = [$s['titre'], $module ? $module->getCode() . ($module->isActive() ? '' : ' (inactif)') : 'commune', $visible ? 'oui' : 'NON'];
        }
        $io->table(['Section du menu', 'Grand module', 'Visible'], $lignes);

        return Command::SUCCESS;
    }
}
