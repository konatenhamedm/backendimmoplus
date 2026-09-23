<?php

namespace App\Service\Sms;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Passerelle « SMS Gateway for Android » (API 3rdparty/v1) : les SMS partent depuis la carte SIM du téléphone passerelle.
 *
 * Activation : SMS_FOURNISSEUR=smsgateway, avec SMS_GATEWAY_URL, SMS_GATEWAY_USERNAME, SMS_GATEWAY_PASSWORD
 * et éventuellement SMS_GATEWAY_SIM (numéro de SIM à utiliser, 1 par défaut).
 */
final class SmsGatewaySender implements SmsSenderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire(env: 'default::SMS_GATEWAY_URL')] private ?string $url,
        #[Autowire(env: 'default::SMS_GATEWAY_USERNAME')] private ?string $username,
        #[Autowire(env: 'default::SMS_GATEWAY_PASSWORD')] private ?string $password,
        #[Autowire(env: 'default::SMS_GATEWAY_SIM')] private ?string $sim,
    ) {
    }

    public static function getName(): string
    {
        return 'smsgateway';
    }

    public function send(string $to, string $message, string $expediteur): SmsResult
    {
        // Le nom d'expéditeur n'est pas utilisé : le SMS part avec le numéro de la SIM de la passerelle.
        if (!$this->url || !$this->username || !$this->password) {
            return SmsResult::echec('Passerelle SMS non configurée (SMS_GATEWAY_URL, SMS_GATEWAY_USERNAME, SMS_GATEWAY_PASSWORD)');
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->url, '/') . '/3rdparty/v1/messages', [
                'auth_basic' => [$this->username, $this->password],
                'json' => [
                    'textMessage' => ['text' => $message],
                    'phoneNumbers' => [$to],
                    'simNumber' => (int) ($this->sim ?: 1),
                ],
                'timeout' => 15,
            ]);

            $statut = $response->getStatusCode();
            $contenu = $response->toArray(false);

            if ($statut >= 200 && $statut < 300) {
                $etat = $contenu['state'] ?? null;
                if ($etat === 'Failed') {
                    return SmsResult::echec('La passerelle a refusé le SMS : ' . json_encode($contenu['recipients'] ?? $contenu));
                }

                return SmsResult::ok($contenu['id'] ?? null);
            }

            $erreur = $contenu['message'] ?? $response->getContent(false);
            $this->logger->error("Passerelle SMS : HTTP $statut – $erreur");

            return SmsResult::echec("Passerelle SMS : HTTP $statut – $erreur");
        } catch (\Throwable $e) {
            $this->logger->error('Passerelle SMS injoignable : ' . $e->getMessage());

            return SmsResult::echec('Passerelle SMS injoignable : ' . $e->getMessage());
        }
    }
}
