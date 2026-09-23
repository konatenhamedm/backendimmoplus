<?php

namespace App\Service\Sms;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Fournisseur d'envoi de SMS.
 *
 * Pour brancher un fournisseur (Orange, Infobip, Twilio…) : créer une classe dans src/Service/Sms/
 * qui implémente cette interface (elle est détectée automatiquement), puis définir
 * SMS_FOURNISSEUR=<valeur retournée par getName()> dans le .env.
 */
#[AutoconfigureTag('app.sms_sender')]
interface SmsSenderInterface
{
    /** Identifiant du fournisseur, utilisé dans SMS_FOURNISSEUR. */
    public static function getName(): string;

    /**
     * @param string $to        numéro au format international (+225XXXXXXXXXX)
     * @param string $expediteur nom d'expéditeur affiché (11 caractères max chez la plupart des opérateurs)
     */
    public function send(string $to, string $message, string $expediteur): SmsResult;
}
