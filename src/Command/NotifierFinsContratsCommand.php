<?php

namespace App\Command;

use App\Entity\ContratLocation;
use App\Service\FinContratNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:contrats:notifier-fins',
    description: "Prévient les agents des contrats actifs qui arrivent à leur date de fin aujourd'hui (à lancer une fois par jour)",
)]
class NotifierFinsContratsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private FinContratNotifier $notifier)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('date', null, InputOption::VALUE_REQUIRED, 'Date traitée (AAAA-MM-JJ), aujourd\'hui par défaut');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jour = new \DateTimeImmutable($input->getOption('date') ?? 'today');

        $contrats = $this->em->getRepository(ContratLocation::class)->createQueryBuilder('c')
            ->where('c.etat = 1')
            ->andWhere('c.dateFin >= :debut AND c.dateFin < :fin')
            ->setParameter('debut', $jour->setTime(0, 0))
            ->setParameter('fin', $jour->setTime(0, 0)->modify('+1 day'))
            ->getQuery()
            ->getResult();

        $notifies = 0;
        foreach ($contrats as $contrat) {
            if ($this->notifier->notifierAgent($contrat, FinContratNotifier::ECHEANCE)) {
                $notifies++;
                $io->text("Contrat #{$contrat->getId()} : agent prévenu");
            } else {
                $io->text("Contrat #{$contrat->getId()} : aucun agent assigné à la maison");
            }
        }

        $io->success(sprintf("%d contrat(s) arrivant à terme le %s, %d agent(s) prévenu(s).", count($contrats), $jour->format('d/m/Y'), $notifies));

        return Command::SUCCESS;
    }
}
