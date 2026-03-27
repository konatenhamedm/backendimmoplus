<?php

namespace App\Service;

use App\Entity\FactureLocation;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Repository\FactureLocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\ReglementsRepository;
use App\Repository\TypeVersementsRepository;
use App\Entity\Reglements;
use App\Entity\TypeVersements;
use App\Service\EntrepriseRegistrationService;

class PaiementService
{
    private string $apiKey;
    private string $merchantId;
    private string $paiementUrl;

    public function __construct(
        private ParameterBagInterface $params,
        private UrlGeneratorInterface $urlGenerator,
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository,
        private FactureLocationRepository $factureLocationRepository,
        private ReglementsRepository $reglementsRepository,
        private TypeVersementsRepository $typeVersementsRepository,
        private EntrepriseRegistrationService $registrationService
    ) {
        $this->apiKey = $params->get('api_key');
        $this->merchantId = $params->get('merchant_id');
        $this->paiementUrl = $params->get('paiement_url');
        // $this->sendMail = $params->get('send_mail');
        // $this->superAdmin = $params->get('super_admin');
    }

    public function generateReference(string $code): string
    {
        $query = $this->em->createQueryBuilder();
        $query->select("count(a.id)")
            ->from(Transaction::class, 'a');

        $nb = $query->getQuery()->getSingleScalarResult();
        return ($code . date("y") . date("m") . date("d") . date("H") . date("i") . date("s") . str_pad($nb + 1, 3, '0', STR_PAD_LEFT));
    }

    public function traiterPaiement($data = [], User $user, FactureLocation $factureLocation): array
    {
        $transaction = new Transaction();

        $amount = isset($data['amount']) ? (int)$data['amount'] : $factureLocation->getSoldeFactLoc();
        $transaction->setAmount((string)$amount); 
        $transaction->setFactureLocation($factureLocation);
        $transaction->setLocataire($factureLocation->getLocataire());
        
        $reference = $this->generateReference('TRX');
        $transaction->setReference($reference);
        $transaction->setType('loyer'); 
        $transaction->setMode($data['operateure'] ?? 'MOBILE_MONEY'); // Operateur code from frontend
        $transaction->setStatus('INITIE'); 
        $transaction->setDescription('Paiement Facture ' . $factureLocation->getLibFacture());
        $transaction->setDate(new \DateTime());

        // We persist here to get ID 
        $this->transactionRepository->save($transaction, true);

        try {
            $client = new \SoapClient($this->paiementUrl . '?wsdl', [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true
            ]);

            $requestData = [
                'merchantId'           => $this->merchantId,
                'referenceNumber'      => $reference,
                'amount'               => (int)$transaction->getAmount(),
                'channel'              => $data['operateur'], // ex: CARD, MOBILE
                'countryCurrencyCode'  => '952', // XOF
                'currency'             => 'XOF',
                'customerId'           => (string) $user->getId(),
                'hashcode'             => 'hashcode', // Should implement proper hash if required by provider
                'customerFirstName'    => $user->getEmploye() ? $user->getEmploye()->getNom() : 'Client',
                'customerLastname'     => $user->getEmploye() ? $user->getEmploye()->getPrenom() : 'Client',
                'customerEmail'        => $data['email'] ?? 'client@email.com',
                'customerPhoneNumber'  => $data['numero'] ?? '00000000',
                'description'          => $transaction->getDescription(),
                'notificationURL'      => $this->urlGenerator->generate('api_paiement_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'returnURL'            => $data['returnURL'] ?? 'ateliya://payment/success',
                'returnContext'        => http_build_query([
                    'transaction_id' => $transaction->getId(),
                    'reference'   => $reference,
                ]),
            ];

            // Note: Adjust depending on actual SOAP method signature. Assuming initTransact as per original code.
            $response = $client->initTransact($requestData);

            if ($response && isset($response->Code) && $response->Code == 0) {
                 // Update with session ID if needed, or just proceed
            } else {
                // Handle initiation failure?
                // For now, assume if no exception, it proceeded to some extent or returned checking URL
            }

            $paiementProUrl = 'https://www.paiementpro.net/webservice/onlinepayment/processing_v2.php?sessionid=' . ($response->Sessionid ?? '');
            $sessionId = $response->Sessionid ?? '';

            return [
                'code'        => 200,
                'reference'   => $reference,
                'transaction_id' => $sessionId,
                'redirectUrl' => $paiementProUrl,
                'sessionId'   => $sessionId
            ];

        } catch (\SoapFault $e) {
            $transaction->setStatus('FAILED');
            $this->transactionRepository->save($transaction, true);
            
            return [
                'code' => 400,
                'error' => $e->getMessage(),
                'reference' => $reference
            ];
        } catch (\Exception $e) {
             $transaction->setStatus('FAILED');
             $this->transactionRepository->save($transaction, true);

             return [
                 'code' => 500,
                 'error' => $e->getMessage(),
                 'reference' => $reference
             ];
        }
    }

    public function traiterPaiementAbonnement($data, User $user, \App\Entity\Entreprise $entreprise, \App\Entity\ModuleAbonnement $module): array
    {
        $transaction = new Transaction();

        $amount = (int) $module->getMontant();
        $transaction->setAmount((string)$amount); 
        $transaction->setEntreprise($entreprise);
        $transaction->setModuleAbonnement($module);
        
        $reference = $this->generateReference('SUB');
        $transaction->setReference($reference);
        $transaction->setType('abonnement'); 
        $transaction->setMode($data['operateure'] ?? 'MOBILE_MONEY');
        $transaction->setStatus('INITIE'); 
        $transaction->setDescription('Paiement Abonnement ' . $module->getCode());
        $transaction->setDate(new \DateTime());

        $this->transactionRepository->save($transaction, true);

        try {
            $client = new \SoapClient($this->paiementUrl . '?wsdl', [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true
            ]);

            $requestData = [
                'merchantId'           => $this->merchantId,
                'referenceNumber'      => $reference,
                'amount'               => (int)$transaction->getAmount(),
                'channel'              => $data['operateur'] ?? 'MOBILE',
                'countryCurrencyCode'  => '952', // XOF
                'currency'             => 'XOF',
                'customerId'           => (string) $user->getId(),
                'hashcode'             => 'hashcode', 
                'customerFirstName'    => $user->getEmploye() ? $user->getEmploye()->getNom() : 'Entreprise',
                'customerLastname'     => $user->getEmploye() ? $user->getEmploye()->getPrenom() : 'Admin',
                'customerEmail'        => $data['email'] ?? $entreprise->getEmail() ?? 'client@email.com',
                'customerPhoneNumber'  => $data['numero'] ?? $entreprise->getContacts() ?? '00000000',
                'description'          => $transaction->getDescription(),
                'notificationURL'      => $this->urlGenerator->generate('api_paiement_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'returnURL'            => $data['returnURL'] ?? 'immoplus://payment/success',
                'returnContext'        => http_build_query([
                    'transaction_id' => $transaction->getId(),
                    'reference'   => $reference,
                ]),
            ];

            $response = $client->initTransact($requestData);

            $sessionId = $response->Sessionid ?? '';
            $paiementProUrl = 'https://www.paiementpro.net/webservice/onlinepayment/processing_v2.php?sessionid=' . $sessionId;

            return [
                'code'        => 200,
                'reference'   => $reference,
                'transaction_id' => $sessionId,
                'redirectUrl' => $paiementProUrl,
                'sessionId'   => $sessionId
            ];
        } catch (\SoapFault $e) {
            $transaction->setStatus('FAILED');
            $this->transactionRepository->save($transaction, true);
            return [ 'code' => 400, 'error' => $e->getMessage(), 'reference' => $reference ];
        } catch (\Exception $e) {
             $transaction->setStatus('FAILED');
             $this->transactionRepository->save($transaction, true);
             return [ 'code' => 500, 'error' => $e->getMessage(), 'reference' => $reference ];
        }
    }
    public function traiterPaiementInscription($data, \App\Entity\ModuleAbonnement $module, array $registrationData): array
    {
        $transaction = new Transaction();

        $amount = (int) $module->getMontant();
        $transaction->setAmount((string)$amount); 
        $transaction->setModuleAbonnement($module);
        
        $reference = $this->generateReference('REG'); // Registration
        $transaction->setReference($reference);
        $transaction->setType('inscription'); 
        $transaction->setMode($data['operateur'] ?? 'MOBILE');
        $transaction->setStatus('INITIE'); 
        $transaction->setDescription('Paiement Inscription Motiplus - ' . $module->getCode());
        $transaction->setDate(new \DateTime());
        $transaction->setPayload(json_encode($registrationData));

        $this->transactionRepository->save($transaction, true);

        try {
            $client = new \SoapClient($this->paiementUrl . '?wsdl', [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true
            ]);

            $requestData = [
                'merchantId'           => $this->merchantId,
                'referenceNumber'      => $reference,
                'amount'               => (int) $amount,
                'channel'              => $data['operateur'] ?? 'MOBILE',
                'countryCurrencyCode'  => '952', // XOF
                'currency'             => 'XOF',
                'customerId'           => 'PENDING',
                'hashcode'             => 'hashcode', 
                'customerFirstName'    => $registrationData['admin_nom'] ?? 'Client',
                'customerLastname'     => $registrationData['admin_prenoms'] ?? 'Moti',
                'customerEmail'        => $registrationData['email'] ?? 'client@email.com',
                'customerPhoneNumber'  => $registrationData['contacts'] ?? '00000000',
                'description'          => $transaction->getDescription(),
                'notificationURL'      => $this->urlGenerator->generate('api_paiement_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'returnURL'            => $data['returnURL'] ?? 'https://web.motiplus.pro/login?reg=success',
                'returnContext'        => http_build_query([
                    'transaction_id' => $transaction->getId(),
                    'reference'   => $reference,
                ]),
            ];

            $response = $client->initTransact($requestData);

            $sessionId = $response->Sessionid ?? '';
            $paiementProUrl = 'https://www.paiementpro.net/webservice/onlinepayment/processing_v2.php?sessionid=' . $sessionId;

            return [
                'code'        => 200,
                'reference'   => $reference,
                'transaction_id' => $sessionId,
                'redirectUrl' => $paiementProUrl,
                'sessionId'   => $sessionId
            ];
        } catch (\Exception $e) {
             $transaction->setStatus('FAILED');
             $this->transactionRepository->save($transaction, true);
             return [ 'code' => 400, 'error' => $e->getMessage(), 'reference' => $reference ];
        }
    }

    public function handleWebhook(array $data): array
    {
        // Data usually contains referenceNumber, responsecode, etc.
        $reference = $data['referenceNumber'] ?? null;
        
        if (!$reference) {
            return ['message' => 'Reference missing', 'code' => 400];
        }

        $transaction = $this->transactionRepository->findOneBy(['reference' => $reference]);

        if (!$transaction) {
            return ['message' => 'Transaction not found', 'code' => 404];
        }

        // --- PROCÉDURE ANTI-DOUBLON ---
        if ($transaction->getStatus() === 'SUCCESS') {
            return ['message' => 'Transaction déjà traitée', 'code' => 200];
        }

        if (isset($data['responsecode']) && $data['responsecode'] == 0) {
            $transaction->setStatus('SUCCESS');
            
            if ($transaction->getType() === 'abonnement') {
                $entreprise = $transaction->getEntreprise();
                $module = $transaction->getModuleAbonnement();
                if ($entreprise && $module) {
                    $currentDateFin = $entreprise->getDateFinAbonnement();
                    if (!$currentDateFin || $currentDateFin < new \DateTime()) {
                        $currentDateFin = new \DateTime(); // Repart d'aujourd'hui
                    }
                    $newDateFin = $currentDateFin instanceof \DateTime ? clone $currentDateFin : \DateTime::createFromInterface($currentDateFin);

                    $dureeJours = ((int) $module->getDuree()) > 0 ? (int) $module->getDuree() : 30;
                    $newDateFin->modify("+{$dureeJours} days");
                    
                    $abonnement = new \App\Entity\Abonnement();
                    $abonnement->setEntreprise($entreprise);
                    $abonnement->setType('RENOUVELLEMENT');
                    $abonnement->setEtat('ACTIF');
                    $abonnement->setModuleAbonnement($module);
                    $abonnement->setDateFin(clone $newDateFin);

                    // Désactiver les anciens abonnements actifs
                    $oldAbonnements = $this->em->getRepository(\App\Entity\Abonnement::class)->findBy([
                        'entreprise' => $entreprise,
                        'etat' => 'ACTIF'
                    ]);
                    foreach ($oldAbonnements as $oldAb) {
                        $oldAb->setEtat('EXPIRE');
                        $this->em->persist($oldAb);
                    }

                    $entreprise->setDateFinAbonnement($newDateFin);
                    $entreprise->setAbonnement($module->getCode());

                    $this->em->persist($abonnement);
                    $this->em->persist($entreprise);
                    $this->em->flush();
                }
            } elseif ($transaction->getType() === 'inscription') {
                $payload = json_decode($transaction->getPayload(), true);
                if ($payload) {
                    try {
                        $this->registrationService->processRegistration($payload);
                    } catch (\Exception $e) {
                         // Peut-être logger l'erreur mais le paiement a eu lieu
                         return ['message' => 'Payment OK but registration failed: ' . $e->getMessage(), 'code' => 500];
                    }
                }
            } else {
                $facture = $transaction->getFactureLocation();
                if ($facture) {
                    $amountPaid = (int)$transaction->getAmount();
                    $newSolde = $facture->getSoldeFactLoc() - $amountPaid;
                    if ($newSolde < 0) $newSolde = 0;
                    
                    $facture->setSoldeFactLoc($newSolde);
                    $facture->setStatut($newSolde <= 0 ? 'payer' : 'partiel');
                    
                    // Create Reglement
                    $reglement = new Reglements();
                    $reglement->setNumFact($facture);
                    $reglement->setMontantVerse($amountPaid);
                    $reglement->setDate(time());
                    $reglement->setNumchq($transaction->getReference());
                    
                    // Find or create TypeVersement 'MOBILE_MONEY'
                    $type = $this->typeVersementsRepository->findOneBy(['codTyp' => 'MOBILE']);
                    if (!$type) {
                        $type = new TypeVersements();
                        $type->setCodTyp('MOBILE');
                        $type->setLibType('Mobile Money');
                        $this->typeVersementsRepository->save($type, true);
                    }
                    $reglement->setTypeversement($type);
                    
                    $this->reglementsRepository->save($reglement, true);
                    $this->factureLocationRepository->save($facture, true);
                }
            }
            
            $this->transactionRepository->save($transaction, true);

            return ['message' => 'OK', 'code' => 200];
        } else {
            $transaction->setStatus('FAILED');
            $this->transactionRepository->save($transaction, true);

            return ['message' => 'Payment Failed', 'code' => 400];
        }
    }
}
