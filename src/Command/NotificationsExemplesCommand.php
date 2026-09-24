<?php

namespace App\Command;

use App\Entity\ContratLocation;
use App\Entity\FactureLocation;
use App\Entity\Maison;
use App\Entity\Notification;
use App\Entity\Transaction;
use App\Entity\User;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;

/**
 * Notifications d'exemple pour tester l'onglet Notifications de l'application.
 *
 * Chaque utilisateur reçoit 4 notifications adaptées à son rôle (locataire, agent, administrateur),
 * construites à partir de ses vraies données quand elles existent, et étalées dans le temps
 * (les plus anciennes marquées comme lues). Les identifiants créés sont notés dans
 * var/notifications_exemples.json : --supprimer efface exactement ces notifications-là.
 *
 *   php bin/console app:notifications:exemples                  # tous les utilisateurs
 *   php bin/console app:notifications:exemples --utilisateur=12 # un seul utilisateur
 *   php bin/console app:notifications:exemples --push           # + notification push sur le téléphone
 *   php bin/console app:notifications:exemples --supprimer      # efface les notifications d'exemple
 */
#[AsCommand(
    name: 'app:notifications:exemples',
    description: "Crée des notifications d'exemple bien formatées pour chaque utilisateur (ou les supprime avec --supprimer)",
)]
class NotificationsExemplesCommand extends Command
{
    private const PAIES = ['payer', 'paye', 'solde'];
    private const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public function __construct(
        private EntityManagerInterface $em,
        /** Firebase n'est chargé qu'avec --push */
        #[AutowireServiceClosure(PushNotificationService::class)]
        private \Closure $push,
        #[Autowire('%kernel.project_dir%/var/notifications_exemples.json')]
        private string $fichierIds,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('supprimer', null, InputOption::VALUE_NONE, "Supprime les notifications d'exemple créées par cette commande")
            ->addOption('utilisateur', null, InputOption::VALUE_REQUIRED, "Id d'un seul utilisateur")
            ->addOption('push', null, InputOption::VALUE_NONE, 'Envoie aussi la plus récente en push aux appareils enregistrés')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les messages sans rien enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        return $input->getOption('supprimer') ? $this->supprimer($io) : $this->creer($io, $input);
    }

    // ---------- Création ----------

    private function creer(SymfonyStyle $io, InputInterface $input): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $criteres = ['isActive' => true];
        if ($input->getOption('utilisateur')) {
            $criteres['id'] = (int) $input->getOption('utilisateur');
        }
        /** @var User[] $users */
        $users = $this->em->getRepository(User::class)->findBy($criteres);
        if (!$users) {
            $io->warning('Aucun utilisateur trouvé.');
            return Command::SUCCESS;
        }

        $ids = $this->idsEnregistres();
        $total = 0;

        foreach ($users as $user) {
            $messages = match ($user->getGroupe()?->getCode()) {
                'LOCATAIRE' => $this->pourLocataire($user),
                'AGENT', 'CAISSE' => $this->pourAgent($user),
                'ADMIN', 'ADMINAG' => $this->pourGestionnaire($user),
                default => $this->pourAutre($user),
            };

            $io->section(sprintf('%s · %s', $user->getNomPrenoms(), $user->getGroupe()?->getCode() ?? 'sans groupe'));
            $creees = [];
            foreach ($messages as $i => [$titre, $texte]) {
                $io->text(sprintf('<info>%s</info> — %s', $titre, $texte));
                if ($dryRun) {
                    continue;
                }
                // Étalées dans le temps : la plus récente il y a quelques minutes, les anciennes lues
                $notification = (new Notification())
                    ->setUser($user)
                    ->setEntreprise($user->getEntreprise())
                    ->setTitre($titre)
                    ->setLibelle($texte)
                    ->setEtat($i >= 2)
                    ->setCreatedBy($user)
                    ->setUpdatedBy($user)
                    ->setCreatedAt(new \DateTimeImmutable(['-8 minutes', '-3 hours', '-1 day -2 hours', '-3 days -5 hours'][$i] ?? '-5 days'));
                $this->em->persist($notification);
                $creees[] = $notification;
            }
            if ($dryRun) {
                continue;
            }
            $this->em->flush();
            foreach ($creees as $n) {
                $ids[] = $n->getId();
            }
            $total += count($creees);

            if ($input->getOption('push') && $user->getFcmToken() && $creees) {
                try {
                    ($this->push)()->sendPush($user->getFcmToken(), $creees[0]->getTitre(), $creees[0]->getLibelle(), ['type' => 'exemple']);
                } catch (\Throwable $e) {
                    $io->note("Push impossible pour {$user->getNomPrenoms()} : {$e->getMessage()}");
                }
            }
        }

        if ($dryRun) {
            $io->success('Simulation : rien n\'a été enregistré.');
            return Command::SUCCESS;
        }

        file_put_contents($this->fichierIds, json_encode(array_values(array_unique($ids))));
        $io->success(sprintf(
            "%d notification(s) d'exemple créées pour %d utilisateur(s). Pour les effacer : php bin/console app:notifications:exemples --supprimer",
            $total,
            count($users)
        ));

        return Command::SUCCESS;
    }

    // ---------- Suppression ----------

    private function supprimer(SymfonyStyle $io): int
    {
        $ids = $this->idsEnregistres();
        if (!$ids) {
            $io->success("Aucune notification d'exemple à supprimer.");
            return Command::SUCCESS;
        }

        $supprimees = $this->em->createQueryBuilder()
            ->delete(Notification::class, 'n')
            ->where('n.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
        @unlink($this->fichierIds);

        $io->success("$supprimees notification(s) d'exemple supprimée(s).");

        return Command::SUCCESS;
    }

    /** @return int[] */
    private function idsEnregistres(): array
    {
        if (!is_file($this->fichierIds)) {
            return [];
        }
        $ids = json_decode((string) file_get_contents($this->fichierIds), true);

        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    // ---------- Messages par rôle ----------

    /** @return array<int, array{0: string, 1: string}> */
    private function pourLocataire(User $user): array
    {
        $prenom = $this->prenom($user);
        $locataire = $user->getLocataire();
        /** @var FactureLocation|null $facture */
        $facture = $locataire ? $this->em->getRepository(FactureLocation::class)->findOneBy(['locataire' => $locataire], ['dateLimite' => 'DESC']) : null;
        $contrat = $locataire ? $this->em->getRepository(ContratLocation::class)->findOneBy(['locataire' => $locataire, 'etat' => 1]) : null;

        $libelle = $facture?->getLibFacture() ?? 'Loyer ' . $this->moisCourant();
        $montant = $this->fcfa((int) ($facture?->getMntFact() ?? $contrat?->getMntLoyer() ?? 150000));
        $echeance = ($facture?->getDateLimite() ?? new \DateTime('first day of next month +4 days'))->format('d/m/Y');
        $logement = $contrat?->getAppart()?->getLibAppart() ?? 'votre logement';
        $agence = $contrat?->getAgence()?->getNom() ?? $user->getEntreprise()?->getDenomination() ?? 'Votre agence';

        return [
            ['Nouvelle facture de loyer', "Bonjour $prenom, votre facture « $libelle » de $montant est disponible. Merci de la régler avant le $echeance."],
            ["Rappel d'échéance", "Votre loyer de $montant pour $logement arrive à échéance le $echeance. Réglez-le à temps pour éviter des pénalités de retard."],
            ['Paiement reçu', "Nous avons bien reçu votre paiement de $montant. Votre compte est à jour, merci pour votre confiance !"],
            ['Message de votre agence', "$agence vous informe : une coupure d'eau est prévue samedi de 8 h à 12 h pour l'entretien des installations. Merci de votre compréhension."],
        ];
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function pourAgent(User $user): array
    {
        $prenom = $this->prenom($user);
        $impayes = $this->facturesImpayees(['m.idAgent = :agent'], ['agent' => $user]);
        $nb = count($impayes);
        $totalDu = array_sum(array_map(fn (FactureLocation $f) => (int) $f->getSoldeFactLoc(), $impayes));
        $retard = $impayes[0] ?? null;
        /** @var Maison|null $site */
        $site = $this->em->getRepository(Maison::class)->findOneBy(['idAgent' => $user], ['id' => 'DESC']);

        $locataire = $retard?->getLocataire()?->getNPrenoms() ?? 'KONÉ Issouf';
        $logement = $retard?->getAppartement()?->getLibAppart() ?? 'Appartement A1';
        $jours = $retard?->getDateLimite() ? max(1, (int) $retard->getDateLimite()->diff(new \DateTime())->days) : 12;

        return [
            ['Factures à encaisser', sprintf('Bonjour %s, %d facture(s) sont à encaisser ce mois-ci pour un total de %s.', $prenom, max($nb, 3), $this->fcfa($totalDu ?: 450000))],
            ['Locataire en retard', "$locataire ($logement) a $jours jour(s) de retard sur son loyer. Pensez à le relancer lors de votre passage."],
            ['Nouveau site à suivre', sprintf('Le site « %s » vous a été confié pour le recouvrement des loyers.', $site?->getLibMaison() ?? 'Résidence Les Palmiers')],
            ['Encaissement enregistré', sprintf('Votre encaissement de %s pour %s a bien été enregistré. Le reçu est disponible pour le locataire.', $this->fcfa((int) ($retard?->getMntFact() ?? 150000)), $locataire)],
        ];
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function pourGestionnaire(User $user): array
    {
        $prenom = $this->prenom($user);
        $agence = $user->getGroupe()?->getCode() === 'ADMINAG' ? $user->getAgence() : null;
        $conditions = ['f.entreprise = :entreprise'];
        $parametres = ['entreprise' => $user->getEntreprise()];
        if ($agence) {
            $conditions[] = 'f.agence = :agence';
            $parametres['agence'] = $agence;
        }
        $impayes = $this->facturesImpayees($conditions, $parametres, true);
        $locatairesEnRetard = count(array_unique(array_map(fn (FactureLocation $f) => $f->getLocataire()?->getId(), $impayes)));
        $totalRetard = array_sum(array_map(fn (FactureLocation $f) => (int) $f->getSoldeFactLoc(), $impayes));

        $qbPaiement = $this->em->getRepository(Transaction::class)->createQueryBuilder('t')
            ->join('t.factureLocation', 'f')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1);
        foreach ($conditions as $condition) {
            $qbPaiement->andWhere($condition);
        }
        foreach ($parametres as $cle => $valeur) {
            $qbPaiement->setParameter($cle, $valeur);
        }
        /** @var Transaction|null $paiement */
        $paiement = $qbPaiement->getQuery()->getOneOrNullResult();
        $contrat = $this->em->getRepository(ContratLocation::class)->findOneBy(
            array_filter(['entreprise' => $user->getEntreprise(), 'agence' => $agence, 'etat' => 1]),
            ['id' => 'DESC']
        );

        $agent = $paiement?->getAgent()?->getNomPrenoms() ?? 'Moussa Traoré';
        $payeur = $paiement?->getFactureLocation()?->getLocataire()?->getNPrenoms() ?? 'KONÉ Issouf';
        $montantPaiement = $this->fcfa((int) ($paiement?->getAmount() ?? 150000));
        $nouveauLocataire = $contrat?->getLocataire()?->getNPrenoms() ?? 'YAO Aya';
        $logement = $contrat?->getAppart()?->getLibAppart() ?? 'Appartement B2';
        $loyer = $this->fcfa((int) ($contrat?->getMntLoyer() ?? 120000));

        return [
            ['Paiement encaissé', "$agent a encaissé $montantPaiement de $payeur ({$this->moisCourant()})."],
            ['Loyers en retard', $locatairesEnRetard > 0
                ? sprintf('Bonjour %s, %d locataire(s) sont en retard de paiement pour un total de %s. Consultez l\'onglet Retards pour les relancer.', $prenom, $locatairesEnRetard, $this->fcfa($totalRetard))
                : "Bonjour $prenom, aucun loyer en retard pour le moment. Beau travail !"],
            ['Nouveau contrat', "$nouveauLocataire a signé pour $logement. Loyer : $loyer par mois."],
            ['Bilan du mois', sprintf('Le bilan de %s est disponible : consultez vos encaissements et votre taux de recouvrement dans le tableau de bord.', $this->moisCourant())],
        ];
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function pourAutre(User $user): array
    {
        $prenom = $this->prenom($user);

        return [
            ['Bienvenue sur Motiplus', "Bonjour $prenom, vos notifications apparaîtront ici : paiements, factures, contrats et informations de votre agence."],
            ['Nouveauté', "Vous pouvez désormais suivre l'activité de votre agence directement depuis l'application mobile."],
            ['Conseil', 'Activez les notifications de votre téléphone pour être prévenu en temps réel.'],
            ['Maintenance terminée', 'La maintenance de la plateforme est terminée. Merci de votre patience.'],
        ];
    }

    // ---------- Outils ----------

    /**
     * Factures non soldées, les plus anciennes échéances d'abord.
     *
     * @return FactureLocation[]
     */
    private function facturesImpayees(array $conditions, array $parametres, bool $echuesSeulement = false): array
    {
        $qb = $this->em->getRepository(FactureLocation::class)->createQueryBuilder('f')
            ->leftJoin('f.appartement', 'a')
            ->leftJoin('a.maisson', 'm')
            ->andWhere('f.soldeFactLoc > 0')
            ->andWhere('f.statut IS NULL OR f.statut NOT IN (:paies)')
            ->setParameter('paies', self::PAIES)
            ->orderBy('f.dateLimite', 'ASC');
        foreach ($conditions as $condition) {
            $qb->andWhere($condition);
        }
        foreach ($parametres as $cle => $valeur) {
            $qb->setParameter($cle, $valeur);
        }
        if ($echuesSeulement) {
            $qb->andWhere('f.dateLimite < :aujourdhui')->setParameter('aujourdhui', new \DateTime('today'));
        }

        return $qb->getQuery()->getResult();
    }

    private function prenom(User $user): string
    {
        $prenoms = trim((string) $user->getPrenoms());

        return $prenoms !== '' ? explode(' ', $prenoms)[0] : ($user->getNomPrenoms() ?: 'cher client');
    }

    private function moisCourant(): string
    {
        $maintenant = new \DateTime();

        return self::MOIS[(int) $maintenant->format('n') - 1] . ' ' . $maintenant->format('Y');
    }

    private function fcfa(int $montant): string
    {
        return number_format($montant, 0, ',', ' ') . ' FCFA';
    }
}
