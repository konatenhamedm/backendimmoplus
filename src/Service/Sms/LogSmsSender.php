<?php

namespace App\Service\Sms;

use Psr\Log\LoggerInterface;

/** Simulation pour le développement : le SMS est écrit dans les logs et considéré comme envoyé. */
final class LogSmsSender implements SmsSenderInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getName(): string
    {
        return 'log';
    }

    public function send(string $to, string $message, string $expediteur): SmsResult
    {
        $this->logger->info("[SMS simulé] $expediteur -> $to : $message");

        return SmsResult::ok('log-' . uniqid());
    }
}
