<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tâches lentes (notifications push, e-mails) exécutées après l'envoi de la réponse HTTP :
 * l'application reçoit sa réponse tout de suite, les envois partent ensuite
 * (kernel.terminate, après fastcgi_finish_request / litespeed_finish_request).
 * En ligne de commande, elles s'exécutent à la fin de la commande.
 */
class TachesDifferees implements EventSubscriberInterface
{
    /** @var callable[] */
    private array $taches = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'executer',
            ConsoleEvents::TERMINATE => 'executer',
        ];
    }

    public function ajouter(callable $tache): void
    {
        $this->taches[] = $tache;
    }

    public function executer(): void
    {
        while ($tache = array_shift($this->taches)) {
            try {
                $tache();
            } catch (\Throwable $e) {
                $this->logger->error('Tâche différée en échec : ' . $e->getMessage());
            }
        }
    }
}
