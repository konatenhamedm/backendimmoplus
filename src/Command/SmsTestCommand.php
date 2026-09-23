<?php

namespace App\Command;

use App\Service\Sms\SmsSenderInterface;
use App\Service\Sms\SmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'app:sms:test',
    description: "Envoie un SMS de test avec le fournisseur configuré (SMS_FOURNISSEUR), sans passer par le quota d'abonnement",
)]
class SmsTestCommand extends Command
{
    /** @var array<string, SmsSenderInterface> */
    private array $senders;

    public function __construct(
        #[AutowireIterator('app.sms_sender', defaultIndexMethod: 'getName')] iterable $senders,
        #[Autowire(env: 'default::SMS_FOURNISSEUR')] private ?string $fournisseur,
        private SmsService $smsService,
    ) {
        parent::__construct();
        $this->senders = iterator_to_array($senders);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('numero', InputArgument::REQUIRED, 'Numéro du destinataire (ex : 0700000000 ou +2250700000000)')
            ->addArgument('message', InputArgument::OPTIONAL, 'Texte du SMS', 'Test MOTIPLUS : la passerelle SMS fonctionne.');
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

        $io->text(sprintf('Fournisseur : %s · destinataire : %s', $sender::getName(), $numero));
        $resultat = $sender->send($numero, $input->getArgument('message'), 'MOTIPLUS');

        if (!$resultat->success) {
            $io->error($resultat->erreur);
            return Command::FAILURE;
        }

        $io->success('SMS accepté par le fournisseur' . ($resultat->reference ? " (référence : {$resultat->reference})" : '') . '.');

        return Command::SUCCESS;
    }
}
