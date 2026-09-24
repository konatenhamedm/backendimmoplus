<?php

namespace App\Command;

use App\Repository\ParametrePenaliteRepository;
use App\Service\PenaliteService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:penalites:appliquer',
    description: 'Ajoute les pénalités de retard aux factures impayées des agences qui les ont activées (à lancer une fois par jour)',
)]
class AppliquerPenalitesCommand extends Command
{
    public function __construct(private ParametrePenaliteRepository $repository, private PenaliteService $penalites)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('date', null, InputOption::VALUE_REQUIRED, "Date de calcul (AAAA-MM-JJ), aujourd'hui par défaut");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jour = new \DateTimeImmutable($input->getOption('date') ?? 'today');
        $total = 0;

        foreach ($this->repository->findActifs() as $parametres) {
            $resultat = $this->penalites->appliquer($parametres, $jour);
            $total += $resultat['montant'];
            $io->text(sprintf(
                '%s : %d facture(s) majorée(s), +%s FCFA',
                $parametres->getAgence()?->getNom(),
                $resultat['factures'],
                number_format($resultat['montant'], 0, ',', ' ')
            ));
        }

        $io->success(sprintf('Pénalités appliquées : +%s FCFA au total.', number_format($total, 0, ',', ' ')));

        return Command::SUCCESS;
    }
}
