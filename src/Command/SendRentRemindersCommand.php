<?php

namespace App\Command;

use App\Repository\ParametreRelanceRepository;
use App\Service\RelanceService;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-rent-reminders',
    description: 'Envoie les rappels et relances de loyer des agences configurées en mode automatique (à lancer une fois par jour)',
)]
class SendRentRemindersCommand extends Command
{
    public function __construct(
        private ParametreRelanceRepository $parametreRepository,
        private RelanceService $relanceService,
        private SubscriptionService $subscriptionService,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, "Affiche ce qui serait envoyé sans rien envoyer");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $totalEnvoyes = 0;

        foreach ($this->parametreRepository->findAutomatiques() as $parametres) {
            $agence = $parametres->getAgence();
            if (!$agence->getEntreprise() || !$this->subscriptionService->hasRelancesAuto($agence->getEntreprise())) {
                $io->note("{$agence->getNom()} : relances automatiques non incluses dans l'abonnement, ignorée.");
                continue;
            }
            $echeances = $this->relanceService->getEcheances($parametres);
            $io->section(sprintf('%s : %d envoi(s) dû(s)', $agence->getNom(), count($echeances)));

            foreach ($echeances as $echeance) {
                $facture = $echeance['facture'];
                $ligne = "Facture #{$facture->getId()} ({$echeance['etape']}) – {$facture->getLocataire()?->getNPrenoms()}";

                if ($dryRun) {
                    $io->text("[dry-run] $ligne");
                    continue;
                }

                $resultat = $this->relanceService->envoyer($parametres, $facture, $echeance['etape'], RelanceService::ORIGINE_AUTO);
                $totalEnvoyes += count($resultat['envoyes']);
                $io->text($ligne . ' : ' . ($resultat['envoyes'] ? implode(', ', $resultat['envoyes']) : implode(' ; ', $resultat['erreurs'])));
            }

            $this->em->flush();
        }

        $io->success($dryRun ? 'Simulation terminée.' : "$totalEnvoyes message(s) envoyé(s).");

        return Command::SUCCESS;
    }
}
