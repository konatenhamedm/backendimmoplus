<?php

namespace App\Command;

use App\Entity\FactureLocation;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Factures réglées sans trace de paiement (reprises de migration, soldes saisis à la main…) :
 * crée le paiement manquant pour que le détail de la facture affiche ce qui a été payé.
 * Le montant payé d'une facture = loyer + pénalités − reste à payer ; seul l'écart avec les
 * paiements déjà enregistrés est ajouté, daté de la date limite de la facture.
 * Les chiffres du tableau de bord (calculés sur les factures) ne changent pas.
 *
 *   php bin/console app:factures:regulariser-paiements --dry-run
 *   php bin/console app:factures:regulariser-paiements
 */
#[AsCommand(
    name: 'app:factures:regulariser-paiements',
    description: 'Ajoute le paiement manquant aux factures réglées sans paiement enregistré (factures de migration…)',
)]
class RegulariserPaiementsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche ce qui serait ajouté sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        /** @var FactureLocation[] $factures */
        $factures = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f')
            ->leftJoin('f.transactions', 't')
            ->addSelect('t')
            ->where('f.mntFact > 0')
            ->andWhere('f.soldeFactLoc < f.mntFact + f.mntPenalite')
            ->getQuery()
            ->getResult();

        $lignes = [];
        $total = 0;
        foreach ($factures as $facture) {
            $paye = (int) $facture->getMntFact() + $facture->getMntPenalite() - (int) $facture->getSoldeFactLoc();
            $enregistre = 0;
            foreach ($facture->getTransactions() as $t) {
                if ($t->getStatus() === 'SUCCESS') {
                    $enregistre += (int) round((float) $t->getAmount());
                }
            }
            $manquant = $paye - $enregistre;
            if ($manquant <= 0) {
                continue;
            }

            $lignes[] = [$facture->getId(), $facture->getLibFacture(), $facture->getLocataire()?->getNPrenoms(), number_format($manquant, 0, ',', ' ')];
            $total += $manquant;
            if ($dryRun) {
                continue;
            }

            $migration = str_contains((string) $facture->getLibFacture(), 'Migration');
            $transaction = (new Transaction())
                ->setReference('TRX-REPRISE-' . strtoupper(bin2hex(random_bytes(5))))
                ->setAmount((string) $manquant)
                ->setDate($facture->getDateLimite() ?? $facture->getDateEmission() ?? new \DateTime())
                ->setStatus('SUCCESS')
                ->setType('RENTRÉE')
                ->setMode('REPRISE')
                ->setDescription($migration ? 'Paiement antérieur repris lors de la migration' : "Réglé par l'avance ou hors application")
                ->setLocataire($facture->getLocataire())
                ->setEntreprise($facture->getEntreprise())
                ->setFactureLocation($facture);
            $this->em->persist($transaction);
        }

        if (!$lignes) {
            $io->success('Toutes les factures réglées ont déjà leurs paiements.');
            return Command::SUCCESS;
        }

        $io->table(['Facture', 'Libellé', 'Locataire', 'Paiement ajouté (FCFA)'], $lignes);
        if ($dryRun) {
            $io->note(sprintf('Simulation : %d paiement(s) seraient ajoutés (%s FCFA).', count($lignes), number_format($total, 0, ',', ' ')));
            return Command::SUCCESS;
        }

        $this->em->flush();
        $io->success(sprintf('%d paiement(s) ajouté(s) pour %s FCFA.', count($lignes), number_format($total, 0, ',', ' ')));

        return Command::SUCCESS;
    }
}
