<?php

namespace App\Service;

use App\Entity\FactureLocation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FneGeneratorService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Simule ou réalise la génération d'une Facture Normalisée Électronique
     * 
     * @param FactureLocation $facture
     * @return array Résultat contenant uid, qr_code, et statut
     */
    public function generateFneForFacture(FactureLocation $facture): array
    {
        $entreprise = $facture->getEntreprise();
        
        if (!$entreprise) {
            throw new \Exception("La facture n'est pas rattachée à une entreprise.");
        }

        $fneLogin = $entreprise->getFneLogin();
        $fnePassword = $entreprise->getFnePassword();

        if (!$fneLogin || !$fnePassword) {
            throw new \Exception("Les identifiants FNE (Login/Mot de passe) ne sont pas configurés pour cette entreprise.");
        }

        // TODO: Implémenter le VRAI appel HTTP vers la DGI
        // Exemple (commenté) :
        // $response = $this->httpClient->request('POST', 'https://api.dgi.gouv.ci/v1/factures', [
        //     'auth_basic' => [$fneLogin, $fnePassword],
        //     'json' => [
        //         'montantHT' => $facture->getMntFact(),
        //         'montantTTC' => $facture->getMntFact(),
        //         'client' => [
        //             'nom' => $facture->getLocataire()->getNom(),
        //         ]
        //     ]
        // ]);
        // $data = $response->toArray();
        // return [ 'uid' => $data['uid'], 'qr_code' => $data['qr_code'] ];
        
        // ------------- SIMULATION DE LA RÉPONSE API -------------
        // Nous simulons la réussite de la génération
        $uid = 'FNE-CI-'. strtoupper(uniqid());
        $qrCodeData = 'https://e-impots.gouv.ci/verify?uid=' . $uid; 

        // En réalité ce sera un base64 ou une url d'image générée par le serveur FNE
        return [
            'status' => 'SUCCESS',
            'uid' => $uid,
            'qr_code' => $qrCodeData, 
            'message' => 'Facture générée avec succès sur le serveur FNE simulé.'
        ];
    }
}
