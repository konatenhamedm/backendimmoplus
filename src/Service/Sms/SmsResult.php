<?php

namespace App\Service\Sms;

final class SmsResult
{
    private function __construct(
        public readonly bool $success,
        public readonly ?string $reference = null,
        public readonly ?string $erreur = null,
    ) {
    }

    public static function ok(?string $reference = null): self
    {
        return new self(true, $reference);
    }

    public static function echec(string $erreur): self
    {
        return new self(false, null, $erreur);
    }
}
