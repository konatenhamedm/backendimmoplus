<?php

namespace App\Command;

use App\Entity\Campagne;
use App\Entity\ContratLocation;
use App\Entity\FactureLocation;
use App\Entity\TabMois;
use App\Entity\Annee;
use App\Entity\Entreprise;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-rent-invoices',
    description: 'Génère automatiquement les factures de location et les campagnes 3 jours avant l\'échéance',
)]
class GenerateRentInvoicesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTime();

        // La date cible est dans 3 jours
        $targetDate = (clone $today)->modify('+3 days');
        $targetDay = (int)$targetDate->format('d');
        $currentYear = $targetDate->format('Y');
        $currentMonthNum = (int)$targetDate->format('m');
        $monthName = $this->getMonthName($currentMonthNum);

        $io->info("Vérification des contrats avec jour de paiement : $targetDay pour le mois : $monthName $currentYear");

        // Récupérer les contrats actifs où JourGenerationFacture (jour de paiement) correspond au jour cible
        $contratRepository = $this->entityManager->getRepository(ContratLocation::class);

        // On suppose que 'etat' = 1 signifie actif. Ajustez si nécessaire selon votre logique.
        // Filtrage également par JourGenerationFacture (jour du mois)
        $contracts = $contratRepository->createQueryBuilder('c')
            ->where('c.jourGenerationFacture = :day')
            ->andWhere('c.etat = :active')
            ->setParameter('day', $targetDay)
            ->setParameter('active', 1)
            ->getQuery()
            ->getResult();

        if (empty($contracts)) {
            $io->success('Aucun contrat trouvé correspondant aux critères.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('%d contrats trouvés à traiter.', count($contracts)));

        foreach ($contracts as $contract) {
            /** @var ContratLocation $contract */
            $entreprise = $contract->getEntreprise();
            if (!$entreprise) {
                $io->warning("Le contrat ID {$contract->getId()} n'a pas d'entreprise associée. Ignoré.");
                continue;
            }

            // 1. Trouver ou créer l'ANNEE
            $annee = $this->getOrCreateAnnee($currentYear);

            // 2. Trouver le MOIS
            $mois = $this->getMois($currentMonthNum);
            if (!$mois) {
                $io->error("Entité Mois pour le numéro $currentMonthNum introuvable en base de données.");
                continue;
            }

            // 3. Trouver ou créer la CAMPAGNE
            $campagneLib = "Loyer " . $mois->getLibMois() . " " . $currentYear;
            $campagne = $this->getOrCreateCampagne($campagneLib, $annee, $mois, $entreprise);

            // 4. Vérifier si la facture existe déjà
            $existingInvoice = $this->entityManager->getRepository(FactureLocation::class)->findOneBy([
                'contrat' => $contract,
                'compagne' => $campagne
            ]);

            if ($existingInvoice) {
                $io->note("La facture existe déjà pour le contrat ID {$contract->getId()} et la campagne {$campagneLib}. Ignoré.");
                continue;
            }

            // 5. Créer la FactureLocation
            $this->createInvoice($contract, $campagne, $mois, $targetDate);
            $io->text("Facture créée pour le contrat ID {$contract->getId()}");
        }

        $this->entityManager->flush();

        $io->success('Génération des factures terminée.');

        return Command::SUCCESS;
    }

    private function getOrCreateAnnee(string $yearVal): Annee
    {
        $repo = $this->entityManager->getRepository(Annee::class);
        $annee = $repo->findOneBy(['libelle' => $yearVal]);

        if (!$annee) {
            $annee = new Annee();
            $annee->setLibelle($yearVal);
            $annee->setEtat(1);
            $annee->setDateDebut(new \DateTime($yearVal . '-01-01'));
            $annee->setDateFin(new \DateTime($yearVal . '-12-31'));
            $this->entityManager->persist($annee);
            $this->entityManager->flush();
        }

        return $annee;
    }

    private function getMois(int $monthNum): ?TabMois
    {
        return $this->entityManager->getRepository(TabMois::class)->findOneBy(['numMois' => $monthNum]);
    }

    private function getOrCreateCampagne(string $libelle, Annee $annee, TabMois $mois, Entreprise $entreprise): Campagne
    {
        $repo = $this->entityManager->getRepository(Campagne::class);
        $campagne = $repo->findOneBy([
            'libCampagne' => $libelle,
            'entreprise' => $entreprise
        ]);

        if (!$campagne) {
            $campagne = new Campagne();
            $campagne->setLibCampagne($libelle);
            $campagne->setAnnee($annee);
            $campagne->setMois($mois);
            $campagne->setEntreprise($entreprise);
            $campagne->setNbreProprio(0);
            $campagne->setNbreLocataire(0);
            $campagne->setMntTotal(0);
            $campagne->setMntPaye('0');

            $this->entityManager->persist($campagne);
            $this->entityManager->flush();
        }

        return $campagne;
    }

    private function createInvoice(ContratLocation $contract, Campagne $campagne, TabMois $mois, \DateTime $targetDate): void
    {
        $facture = new FactureLocation();
        $facture->setContrat($contract);
        $facture->setLocataire($contract->getLocataire());
        $facture->setAppartement($contract->getAppart());
        $facture->setCompagne($campagne);
        $facture->setMois($mois);
        $facture->setEntreprise($contract->getEntreprise());

        // Générer le numéro de facture / Libellé
        $libelle = "Facture Loyer " . $mois->getLibMois() . " " . $targetDate->format('Y') . " - " . $contract->getLocataire()->getNPrenoms();
        $facture->setLibFacture($libelle);

        // Montants
        $montantLoyer = (int)$contract->getMntLoyer();
        $facture->setMntFact($montantLoyer);

        $avance = (int)$contract->getMntAvance();

        // Check if advance is sufficient exactly as requested by user
        if ($avance >= $montantLoyer) {
            $facture->setSoldeFactLoc(0);
            $facture->setStatut('paye');
            $facture->setEncaisse((string)$montantLoyer);

            $contract->setMntAvance((string)($avance - $montantLoyer));
            $this->entityManager->persist($contract);

            $transaction = new Transaction();
            $transaction->setAmount((string)$montantLoyer);
            $transaction->setFactureLocation($facture);
            $transaction->setLocataire($contract->getLocataire());
            $transaction->setMode('ESPECE');
            $transaction->setType('RENTRÉE');
            $transaction->setStatus('SUCCESS');
            $transaction->setReference('TRX-AUTO-' . time() . rand(100, 999));
            $transaction->setDate(new \DateTime());
            $transaction->setDescription('Paiement automatique déduit de l\'avance');

            // Transaction has no setEntreprise method according to the class file
            $this->entityManager->persist($transaction);
        } else {
            $facture->setSoldeFactLoc($montantLoyer);
            $facture->setStatut('impayer');
            $facture->setEncaisse('0');
        }

        // Dates
        $facture->setDateEmission(new \DateTime());
        $facture->setDateLimite($targetDate);

        $this->entityManager->persist($facture);
    }

    private function getMonthName(int $monthNum): string
    {
        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre'
        ];
        return $months[$monthNum] ?? '';
    }
}
