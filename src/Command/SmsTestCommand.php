<?php

namespace App\Command;

use App\Entity\FactureLocation;
use App\Entity\ModeleRelance;
use App\Service\RelanceService;
use App\Service\Sms\SmsSenderInterface;
use App\Service\Sms\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'app:sms:test',
    description: "Envoie un SMS de test (texte libre ou modèle de rappel / relance) avec le fournisseur configuré, sans passer par le quota d'abonnement",
)]
class SmsTestCommand extends Command
{
    /** @var array<string, SmsSenderInterface> */
    private array $senders;

    public function __construct(
        #[AutowireIterator('app.sms_sender', defaultIndexMethod: 'getName')] iterable $senders,
        #[Autowire(env: 'default::SMS_FOURNISSEUR')] private ?string $fournisseur,
        private SmsService $smsService,
        #[Autowire(lazy: true)] private RelanceService $relanceService,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
        $this->senders = iterator_to_array($senders);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('numero', InputArgument::REQUIRED, 'Numéro du destinataire (ex : 0700000000 ou +2250700000000)')
            ->addArgument('message', InputArgument::OPTIONAL, 'Texte libre du SMS (ignoré avec --modele ou --facture)', 'Test MOTIPLUS : la passerelle SMS fonctionne.')
            ->addOption('modele', null, InputOption::VALUE_REQUIRED, 'Modèle SMS par défaut avec des données d\'exemple : rappel ou relance')
            ->addOption('facture', null, InputOption::VALUE_REQUIRED, 'ID d\'une facture : SMS réel issu du modèle de son contrat (ou du modèle par défaut de l\'agence)')
            ->addOption('etape', null, InputOption::VALUE_REQUIRED, 'Avec --facture : RAPPEL ou RELANCE (déduit de la date limite par défaut)')
            ->addOption('agence', null, InputOption::VALUE_REQUIRED, 'Avec --modele : nom d\'agence affiché dans l\'exemple', 'MOTIPLUS')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le SMS sans l\'envoyer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sender = $this->senders[$this->fournisseur ?: 'null'] ?? $this->senders['null'];
        $numero = $this->smsService->normaliserNumero($input->getArgument('numero'));

        if (!$numero) {
            $io->error('Numéro invalide.');
            return Command::FAILURE;
        }

        $message = $this->construireMessage($input, $io);
        if ($message === null) {
            return Command::FAILURE;
        }

        $nbSms = $this->smsService->compterSms($message);
        $io->text(sprintf('Fournisseur : %s · destinataire : %s · %d caractère(s), %d SMS', $sender::getName(), $numero, mb_strlen($message), $nbSms));
        $io->block($message, null, 'fg=black;bg=green', ' ', true);

        if ($input->getOption('dry-run')) {
            $io->note('Simulation : rien n\'a été envoyé.');
            return Command::SUCCESS;
        }

        $resultat = $sender->send($numero, $message, 'MOTIPLUS');

        if (!$resultat->success) {
            $io->error($resultat->erreur);
            return Command::FAILURE;
        }

        $io->success('SMS accepté par le fournisseur' . ($resultat->reference ? " (référence : {$resultat->reference})" : '') . '.');

        return Command::SUCCESS;
    }

    private function construireMessage(InputInterface $input, SymfonyStyle $io): ?string
    {
        if ($factureId = $input->getOption('facture')) {
            $facture = $this->em->getRepository(FactureLocation::class)->find((int) $factureId);
            if (!$facture || !$facture->getAgence()) {
                $io->error("Facture #$factureId introuvable ou sans agence.");
                return null;
            }
            $etape = strtoupper((string) $input->getOption('etape')) ?: null;
            $contenu = $this->relanceService->construireMessage($facture, $etape === 'RAPPEL' ? RelanceService::ETAPE_RAPPEL : $etape);
            $io->text("Modèle : {$contenu['modele']['libelle']} · étape : {$contenu['etape']}");

            return $contenu['sms'];
        }

        if ($modele = $input->getOption('modele')) {
            $modele = strtolower($modele);
            if (!in_array($modele, ['rappel', 'relance'], true)) {
                $io->error('--modele doit valoir « rappel » ou « relance ».');
                return null;
            }
            $gabarit = $modele === 'rappel' ? ModeleRelance::DEFAULT_RAPPEL_SMS : ModeleRelance::DEFAULT_RELANCE_SMS;
            $mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            $echeance = new \DateTime($modele === 'rappel' ? '+3 days' : '-7 days');

            return trim(strtr($gabarit, [
                '{agence}' => $input->getOption('agence'),
                '{locataire}' => 'KOUASSI Jean',
                '{nom}' => 'KOUASSI',
                '{prenoms}' => 'Jean',
                '{mois}' => $mois[(int) $echeance->format('n') - 1] . ' ' . $echeance->format('Y'),
                '{montant}' => '150 000 FCFA',
                '{montant_facture}' => '150 000 FCFA',
                '{date_limite}' => $echeance->format('d/m/Y'),
                '{jours_restants}' => '3',
                '{jours_retard}' => '7',
                '{facture}' => 'Facture Loyer',
                '{logement}' => 'Appartement A2',
                '{agence_contact}' => '',
            ]));
        }

        return $input->getArgument('message');
    }
}
