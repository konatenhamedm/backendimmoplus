<?php

namespace App\Service\Sms;

/** Aucun fournisseur configuré : l'envoi échoue proprement (aucun SMS n'est décompté du quota). */
final class NullSmsSender implements SmsSenderInterface
{
    public static function getName(): string
    {
        return 'null';
    }

    public function send(string $to, string $message, string $expediteur): SmsResult
    {
        return SmsResult::echec("Aucun fournisseur SMS n'est configuré");
    }
}
