<?php

namespace App\Command;

use App\Entity\Annee;
use App\Entity\Appartement;
use App\Entity\Campagne;
use App\Entity\ContratLocation;
use App\Entity\FactureLocation;
use App\Entity\Locataire;
use App\Entity\Reglements;
use App\Entity\TabMois;
use App\Entity\Transaction;
use App\Entity\TypeVersements;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-locataire-data',
    description: 'Crée des données de test réalistes pour un locataire (contrats, factures, paiements).',
)]
class SeedLocataireDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Get the locataire (assuming the one from fixtures exists)
        $locataire = $this->entityManager->getRepository(Locataire::class)->findOneBy(['NPrenoms' => 'Locataire LOCATAIRE']);
        if (!$locataire) {
            $locataire = $this->entityManager->getRepository(Locataire::class)->findOneBy([]);
            if (!$locataire) {
                $io->error("Aucun locataire trouvé. Veuillez d'abord charger les fixtures.");
                return Command::FAILURE;
            }
        }

        $entreprise = $locataire->getEntreprise();
        
        // 2. Ensure Annee and Mois exist
        $annee = $this->entityManager->getRepository(Annee::class)->findOneBy(['libelle' => '2025']);
        if (!$annee) {
            $annee = new Annee();
            $annee->setLibelle('2025');
            $annee->setDateDebut(new \DateTime('2025-01-01'));
            $annee->setDateFin(new \DateTime('2025-12-31'));
            $annee->setEtat(1);
            $this->entityManager->persist($annee);
        }

        $moisJan = $this->entityManager->getRepository(TabMois::class)->findOneBy(['numMois' => 1]);
        if (!$moisJan) {
            $moisJan = new TabMois();
            $moisJan->setNumMois(1);
            $moisJan->setLibMois('Janvier');
            $moisJan->setDebut('01');
            $moisJan->setFin('31');
            $this->entityManager->persist($moisJan);
        }
        $moisFev = $this->entityManager->getRepository(TabMois::class)->findOneBy(['numMois' => 2]);
        if (!$moisFev) {
            $moisFev = new TabMois();
            $moisFev->setNumMois(2);
            $moisFev->setLibMois('Février');
            $moisFev->setDebut('01');
            $moisFev->setFin('28');
            $this->entityManager->persist($moisFev);
        }

        // 3. Create a Campaign
        $campagne = new Campagne();
        $campagne->setLibCampagne("Loyers Janvier 2025");
        $campagne->setAnnee($annee);
        $campagne->setMois($moisJan);
        $campagne->setEntreprise($entreprise);
        $campagne->setNbreProprio(1);
        $campagne->setNbreLocataire(1);
        $campagne->setMntTotal(150000);
        $this->entityManager->persist($campagne);

        // 4. Create an older contract (expired)
        $appart = $this->entityManager->getRepository(Appartement::class)->findOneBy([]);
        $contratOld = new ContratLocation();
        $contratOld->setLocataire($locataire);
        $contratOld->setMntLoyer('120000');
        $contratOld->setDateDebut(new \DateTime('2023-01-01'));
        $contratOld->setDateFin(new \DateTime('2023-12-31'));
        $contratOld->setMntCaution('240000');
        $contratOld->setNbMoisCaution(2);
        $contratOld->setEntreprise($entreprise);
        $contratOld->setEtat(0); // Inactive
        $contratOld->setAppart($appart);
        $contratOld->setFraisanex(0);
        $this->entityManager->persist($contratOld);

        // 5. Create active contract (if not exists)
        $contratActive = $this->entityManager->getRepository(ContratLocation::class)->findOneBy(['locataire' => $locataire, 'etat' => 1]);
        if (!$contratActive) {
            $contratActive = new ContratLocation();
            $contratActive->setLocataire($locataire);
            $contratActive->setMntLoyer('150000');
            $contratActive->setDateDebut(new \DateTime('2024-01-01'));
            $contratActive->setMntCaution('300000');
            $contratActive->setNbMoisCaution(2);
            $contratActive->setEntreprise($entreprise);
            $contratActive->setEtat(1);
            $contratActive->setAppart($appart);
            $contratActive->setFraisanex(0);
            $this->entityManager->persist($contratActive);
        }

        // 6. Create Invoices
        // Invoice 1: Paid
        $facture1 = new FactureLocation();
        $facture1->setLibFacture("LOYER-JANV-2025");
        $facture1->setLocataire($locataire);
        $facture1->setMntFact(150000);
        $facture1->setSoldeFactLoc(0);
        $facture1->setStatut('payer');
        $facture1->setCompagne($campagne);
        $facture1->setDateEmission(new \DateTime('2025-01-01'));
        $facture1->setDateLimite(new \DateTime('2025-01-10'));
        $facture1->setEntreprise($entreprise);
        $this->entityManager->persist($facture1);

        // Invoice 2: Partially Paid
        $facture2 = new FactureLocation();
        $facture2->setLibFacture("LOYER-FEV-2025");
        $facture2->setLocataire($locataire);
        $facture2->setMntFact(150000);
        $facture2->setSoldeFactLoc(50000);
        $facture2->setStatut('impayer');
        $facture2->setCompagne($campagne);
        $facture2->setDateEmission(new \DateTime('2025-02-01'));
        $facture2->setDateLimite(new \DateTime('2025-02-10'));
        $facture2->setEntreprise($entreprise);
        $facture1->setEncaisse('non');
        $facture2->setEncaisse('non');
        $this->entityManager->persist($facture2);

        // 7. Create Payments (Reglements)
        $typeV = $this->entityManager->getRepository(TypeVersements::class)->findOneBy([]) ?: new TypeVersements();
        if (!$typeV->getId()) {
            $typeV->setLibType("Mobile Money");
            $typeV->setCodTyp("MOBILE");
            $this->entityManager->persist($typeV);
        }

        // Payment for Facture 1
        $reglement1 = new Reglements();
        $reglement1->setNumFact($facture1);
        $reglement1->setMontantVerse(150000);
        $reglement1->setDate((new \DateTime('2025-01-03'))->getTimestamp());
        $reglement1->setNumchq("WAVE-TRX-101");
        $reglement1->setTypeversement($typeV);
        $this->entityManager->persist($reglement1);

        // Payment for Facture 2
        $reglement2 = new Reglements();
        $reglement2->setNumFact($facture2);
        $reglement2->setMontantVerse(100000);
        $reglement2->setDate((new \DateTime('2025-02-05'))->getTimestamp());
        $reglement2->setNumchq("OM-TRX-202");
        $reglement2->setTypeversement($typeV);
        $this->entityManager->persist($reglement2);

        // 8. Create Transactions
        $trx1 = new Transaction();
        $trx1->setLocataire($locataire);
        $trx1->setAmount(150000);
        $trx1->setStatus("SUCCESS");
        $trx1->setType("PAYMENT");
        $trx1->setMode("WAVE");
        $trx1->setReference("WAVE-TRX-101");
        $trx1->setDate(new \DateTime('2025-01-03'));
        $trx1->setFactureLocation($facture1);
        $this->entityManager->persist($trx1);

        $trx2 = new Transaction();
        $trx2->setLocataire($locataire);
        $trx2->setAmount(100000);
        $trx2->setStatus("SUCCESS");
        $trx2->setType("PAYMENT");
        $trx2->setMode("ORANGE");
        $trx2->setReference("OM-TRX-202");
        $trx2->setDate(new \DateTime('2025-02-05'));
        $trx2->setFactureLocation($facture2);
        $this->entityManager->persist($trx2);

        $this->entityManager->flush();

        $io->success("Données de test pour le locataire créées avec succès !");

        return Command::SUCCESS;
    }
}
