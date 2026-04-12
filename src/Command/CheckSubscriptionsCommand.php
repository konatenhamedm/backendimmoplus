<?php

namespace App\Command;

use App\Repository\AbonnementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-subscriptions',
    description: 'Désactive les abonnements expirés',
)]
class CheckSubscriptionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private AbonnementRepository $abonnementRepo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime();

        $expiredSubscriptions = $this->abonnementRepo->createQueryBuilder('a')
            ->where('a.etat = :actif')
            ->andWhere('a.dateFin < :now')
            ->setParameter('actif', 'ACTIF')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($expiredSubscriptions as $abo) {
            $abo->setEtat('EXPIRE');
            $count++;
        }

        $this->em->flush();

        if ($count > 0) {
            $io->success("$count abonnements ont été désactivés.");
        } else {
            $io->info("Aucun abonnement expiré à désactiver.");
        }

        return Command::SUCCESS;
    }
}
