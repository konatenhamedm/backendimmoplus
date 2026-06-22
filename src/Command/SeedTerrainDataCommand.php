<?php

namespace App\Command;

use App\Entity\Agence;
use App\Entity\ClientTerrain;
use App\Entity\DemarcheAdministrative;
use App\Entity\EchancierTerrain;
use App\Entity\Entreprise;
use App\Entity\EtapeDemarche;
use App\Entity\Site;
use App\Entity\Terrain;
use App\Entity\TypeEtapeDemarche;
use App\Entity\TypeFraisTerrain;
use App\Entity\VenteTerrain;
use App\Entity\VersementTerrain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-terrain-data',
    description: 'Insère des données de démonstration complètes pour le module Gestion des Terrains.',
)]
class SeedTerrainDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('entreprise', null, InputOption::VALUE_OPTIONAL, 'ID de l\'entreprise', 2)
            ->addOption('agence', null, InputOption::VALUE_OPTIONAL, 'ID de l\'agence', 1)
            ->addOption('purge', 'p', InputOption::VALUE_NONE, 'Supprimer les données existantes avant insertion');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entrepriseId = (int) $input->getOption('entreprise');
        $agenceId     = (int) $input->getOption('agence');
        $purge        = (bool) $input->getOption('purge');

        $io->title("🌍 Seed — Module Gestion des Terrains");
        $io->text("Entreprise ID: $entrepriseId | Agence ID: $agenceId");

        // ─── Résolution des entités de base ───────────────────────────────
        $entreprise = $this->em->getRepository(Entreprise::class)->find($entrepriseId);
        if (!$entreprise) {
            $io->error("Entreprise ID $entrepriseId introuvable.");
            return Command::FAILURE;
        }

        $agence = null;
        if ($agenceId > 0) {
            $agence = $this->em->getRepository(Agence::class)->find($agenceId);
            if (!$agence) {
                $io->warning("Agence ID $agenceId introuvable. Création des données sans agence spécifique.");
            } else {
                $io->success("✓ Agence trouvée : " . $agence->getNom());
            }
        }

        $io->success("✓ Entreprise trouvée : " . $entreprise->getDenomination());

        // ─── Purge optionnelle ────────────────────────────────────────────
        if ($purge) {
            $io->warning("Purge des données existantes en cours...");
            $this->purgeExistingData($entreprise, $agence, $io);
        }

        // ═════════════════════════════════════════════════════════════════
        // ÉTAPE 1 — Paramètres : TypeEtapeDemarche
        // ═════════════════════════════════════════════════════════════════
        $io->section("1. Configuration des types d'étapes de démarche");

        $typesEtapes = $this->em->getRepository(TypeEtapeDemarche::class)
            ->findBy(['entreprise' => $entreprise]);

        if (count($typesEtapes) > 0) {
            $io->comment("Types d'étapes déjà configurés (" . count($typesEtapes) . "). Ignoré.");
        } else {
            $typesEtapes = [];
            foreach (TypeEtapeDemarche::DEFAULTS as $def) {
                $type = new TypeEtapeDemarche();
                $type->setNom($def['nom']);
                $type->setDescription($def['description']);
                $type->setOrdre($def['ordre']);
                $type->setIsActif(true);
                $type->setEntreprise($entreprise);
                $this->em->persist($type);
                $typesEtapes[] = $type;
            }
            $this->em->flush();
            $io->success("✓ " . count($typesEtapes) . " types d'étapes insérés");
        }

        // ═════════════════════════════════════════════════════════════════
        // ÉTAPE 2 — Paramètres : TypeFraisTerrain
        // ═════════════════════════════════════════════════════════════════
        $io->section("2. Configuration des types de frais");

        $typesFrais = $this->em->getRepository(TypeFraisTerrain::class)
            ->findBy(['entreprise' => $entreprise]);

        if (count($typesFrais) > 0) {
            $io->comment("Types de frais déjà configurés (" . count($typesFrais) . "). Ignoré.");
        } else {
            foreach (TypeFraisTerrain::DEFAULTS as $def) {
                $frais = new TypeFraisTerrain();
                $frais->setNom($def['nom']);
                $frais->setDescription($def['description']);
                $frais->setMontantDefaut($def['montantDefaut']);
                $frais->setIsActif(true);
                $frais->setEntreprise($entreprise);
                $this->em->persist($frais);
            }
            $this->em->flush();
            $io->success("✓ " . count(TypeFraisTerrain::DEFAULTS) . " types de frais insérés");
        }

        // ═════════════════════════════════════════════════════════════════
        // ÉTAPE 3 — Sites de Lotissement
        // ═════════════════════════════════════════════════════════════════
        $io->section("3. Création des sites de lotissement");

        $sitesData = [
            [
                'nom'                  => 'Lotissement Les Cocotiers',
                'localisation'         => 'Abidjan, Yopougon Toits Rouges',
                'superficieTotale'     => '25 000 m²',
                'situationGeographique'=> 'Situé dans la commune de Yopougon, borné au Nord par la route principale menant au marché de Toits Rouges, à l\'Est par la résidence Les Palmiers, au Sud par le boulevard de la paix et à l\'Ouest par le terrain municipal.',
                'description'          => 'Lotissement résidentiel haut standing avec voirie bitumée et accès à l\'eau et l\'électricité. Idéal pour construction villa.',
            ],
            [
                'nom'                  => 'Lotissement Étoile du Sud',
                'localisation'         => 'Bassam, Grand-Bassam',
                'superficieTotale'     => '18 500 m²',
                'situationGeographique'=> 'Situé à Grand-Bassam, à 2km de la plage, borné au Nord par la voie ferrée Abidjan-Bassam, à l\'Est par le domaine privé de la famille N\'Guessan, au Sud par le lac Ébrié et à l\'Ouest par la piste agricole.',
                'description'          => 'Lotissement en bord de lagune, vue imprenable sur le lac Ébrié. Terrain plat, constructible immédiatement.',
            ],
        ];

        $sites = [];
        foreach ($sitesData as $sd) {
            // Vérifier si le site existe déjà
            $existing = $this->em->getRepository(Site::class)->findOneBy([
                'nom' => $sd['nom'], 'entreprise' => $entreprise
            ]);
            if ($existing) {
                $io->comment("Site '{$sd['nom']}' existe déjà. Réutilisation.");
                $sites[] = $existing;
                continue;
            }

            $site = new Site();
            $site->setNom($sd['nom']);
            $site->setLocalisation($sd['localisation']);
            $site->setSuperficieTotale($sd['superficieTotale']);
            $site->setSituationGeographique($sd['situationGeographique']);
            $site->setDescription($sd['description']);
            $site->setAgence($agence);
            $site->setEntreprise($entreprise);
            $this->em->persist($site);
            $sites[] = $site;
        }
        $this->em->flush();
        $io->success("✓ " . count($sites) . " sites créés/réutilisés");

        // ═════════════════════════════════════════════════════════════════
        // ÉTAPE 4 — Lots / Terrains
        // ═════════════════════════════════════════════════════════════════
        $io->section("4. Création des lots et terrains");

        $terrainsData = [
            // Site 1 — Les Cocotiers
            ['num' => 'C-01', 'superfice' => '500', 'prix' => '15000000', 'dimensions' => '20m x 25m', 'etat' => 'vendu',      'site_idx' => 0],
            ['num' => 'C-02', 'superfice' => '600', 'prix' => '18000000', 'dimensions' => '20m x 30m', 'etat' => 'disponible',  'site_idx' => 0],
            ['num' => 'C-03', 'superfice' => '450', 'prix' => '13500000', 'dimensions' => '18m x 25m', 'etat' => 'disponible',  'site_idx' => 0],
            ['num' => 'C-04', 'superfice' => '520', 'prix' => '15600000', 'dimensions' => '20m x 26m', 'etat' => 'reserve',     'site_idx' => 0],
            ['num' => 'C-05', 'superfice' => '700', 'prix' => '21000000', 'dimensions' => '25m x 28m', 'etat' => 'vendu',      'site_idx' => 0],
            ['num' => 'C-06', 'superfice' => '480', 'prix' => '14400000', 'dimensions' => '20m x 24m', 'etat' => 'disponible',  'site_idx' => 0],

            // Site 2 — Étoile du Sud
            ['num' => 'ES-01', 'superfice' => '800', 'prix' => '32000000', 'dimensions' => '32m x 25m', 'etat' => 'vendu',     'site_idx' => 1],
            ['num' => 'ES-02', 'superfice' => '750', 'prix' => '30000000', 'dimensions' => '30m x 25m', 'etat' => 'disponible', 'site_idx' => 1],
            ['num' => 'ES-03', 'superfice' => '650', 'prix' => '26000000', 'dimensions' => '26m x 25m', 'etat' => 'disponible', 'site_idx' => 1],
            ['num' => 'ES-04', 'superfice' => '900', 'prix' => '36000000', 'dimensions' => '36m x 25m', 'etat' => 'reserve',    'site_idx' => 1],
        ];

        $terrains = [];
        foreach ($terrainsData as $td) {
            $existing = $this->em->getRepository(Terrain::class)->findOneBy([
                'num' => $td['num'], 'entreprise' => $entreprise
            ]);
            if ($existing) {
                $io->comment("Terrain LOT {$td['num']} existe déjà. Réutilisation.");
                $terrains[$td['num']] = $existing;
                continue;
            }

            $terrain = new Terrain();
            $terrain->setNum($td['num']);
            $terrain->setSuperfice($td['superfice']);
            $terrain->setPrix($td['prix']);
            $terrain->setDimensions($td['dimensions']);
            $terrain->setEtat($td['etat']);
            $terrain->setSite($sites[$td['site_idx']]);
            $terrain->setAgence($agence);
            $terrain->setEntreprise($entreprise);
            $this->em->persist($terrain);
            $terrains[$td['num']] = $terrain;
        }
        $this->em->flush();
        $io->success("✓ " . count($terrains) . " lots créés/réutilisés");

        // ═════════════════════════════════════════════════════════════════
        // ÉTAPE 5 — Clients Terrain
        // ═════════════════════════════════════════════════════════════════
        $io->section("5. Création des clients terrain");

        $clientsData = [
            [
                'nom'           => 'KOUADIO',
                'prenoms'       => 'Jean-Baptiste Arsène',
                'contact'       => '07 01 02 03 04',
                'email'         => 'jb.kouadio@gmail.com',
                'adresse'       => 'Cocody, Abidjan',
                'pieceIdentite' => 'CNI N°CI-0012345-A',
            ],
            [
                'nom'           => 'N\'GUESSAN',
                'prenoms'       => 'Marie-Claire Adjoua',
                'contact'       => '05 45 67 89 01',
                'email'         => 'mc.nguessan@yahoo.fr',
                'adresse'       => 'Plateau, Abidjan',
                'pieceIdentite' => 'PASSEPORT N°CI-98765',
            ],
            [
                'nom'           => 'DIABATÉ',
                'prenoms'       => 'Ibrahim Oumar',
                'contact'       => '01 23 45 67 89',
                'email'         => 'ibrahim.diabate@hotmail.com',
                'adresse'       => 'Marcory, Abidjan',
                'pieceIdentite' => 'CNI N°CI-0054321-B',
            ],
            [
                'nom'           => 'YAO',
                'prenoms'       => 'Kouamé Félix',
                'contact'       => '07 77 88 99 00',
                'email'         => 'felix.yao@immoplus.ci',
                'adresse'       => 'Grand-Bassam',
                'pieceIdentite' => 'CNI N°CI-0011122-C',
            ],
        ];

        $clients = [];
        foreach ($clientsData as $cd) {
            $existing = $this->em->getRepository(ClientTerrain::class)->findOneBy([
                'contact' => $cd['contact'], 'entreprise' => $entreprise
            ]);
            if ($existing) {
                $io->comment("Client {$cd['nom']} existe déjà. Réutilisation.");
                $clients[] = $existing;
                continue;
            }

            $client = new ClientTerrain();
            $client->setNom($cd['nom']);
            $client->setPrenoms($cd['prenoms']);
            $client->setContact($cd['contact']);
            $client->setEmail($cd['email']);
            $client->setAdresse($cd['adresse']);
            $client->setPieceIdentite($cd['pieceIdentite']);
            $client->setEntreprise($entreprise);
            $this->em->persist($client);
            $clients[] = $client;
        }
        $this->em->flush();
        $io->success("✓ " . count($clients) . " clients créés/réutilisés");

        // ═════════════════════════════════════════════════════════════════
        // ÉTAPE 6 — Ventes de Terrain
        // ═════════════════════════════════════════════════════════════════
        $io->section("6. Création des ventes de terrain");

        // Récupérer les types d'étapes actifs pour cet entreprise
        $typesEtapesActifs = $this->em->getRepository(TypeEtapeDemarche::class)
            ->findBy(['entreprise' => $entreprise, 'isActif' => true], ['ordre' => 'ASC']);

        // ── VENTE 1 : Avec papiers, soldée (C-01) ──────────────────────
        $this->creerVente($io, [
            'terrain'       => $terrains['C-01'],
            'client'        => $clients[0],
            'prixVente'     => '15000000',
            'apportInitial' => '15000000',
            'resteAPayer'   => '0',
            'typeVente'     => 'avec_papier',
            'etat'          => 'solde',
            'dateVente'     => new \DateTime('2024-03-15'),
            'versements'    => [
                ['montant' => '15000000', 'ref' => 'Paiement comptant', 'mode' => 'Virement', 'date' => new \DateTime('2024-03-15')],
            ],
            'demarche'      => null,
        ], $agence, $entreprise, $typesEtapesActifs);

        // ── VENTE 2 : Sans papier, gestion agence, en cours (C-05) ──────
        $this->creerVente($io, [
            'terrain'       => $terrains['C-05'],
            'client'        => $clients[1],
            'prixVente'     => '21000000',
            'apportInitial' => '5000000',
            'resteAPayer'   => '16000000',
            'typeVente'     => 'sans_papier_gestion_agence',
            'etat'          => 'en_cours',
            'dateVente'     => new \DateTime('2024-11-10'),
            'versements'    => [
                ['montant' => '5000000', 'ref' => 'Apport Initial', 'mode' => 'Espece', 'date' => new \DateTime('2024-11-10')],
                ['montant' => '2000000', 'ref' => 'Versement DEC-24', 'mode' => 'Mobile Money', 'date' => new \DateTime('2024-12-05')],
            ],
            'echanciers' => [
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2024-12-01'), 'etat' => 'paye'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-01-01'), 'etat' => 'en_attente'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-02-01'), 'etat' => 'en_attente'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-03-01'), 'etat' => 'en_attente'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-04-01'), 'etat' => 'en_attente'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-05-01'), 'etat' => 'en_attente'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-06-01'), 'etat' => 'en_attente'],
                ['montant' => '2000000', 'datePrevue' => new \DateTime('2025-07-01'), 'etat' => 'en_attente'],
            ],
            'demarche' => [
                'titre'         => 'Démarches pour LOT C-05 — N\'GUESSAN Marie-Claire',
                'fraisEstimes'  => '1500000',
                'statutGlobal'  => 'en_cours',
                'etapesValidees' => ['Lettre d\'Attribution', 'Compromis de Vente'],
            ],
        ], $agence, $entreprise, $typesEtapesActifs);

        // ── VENTE 3 : Sans papier, démarche avancée (ES-01) ─────────────
        $this->creerVente($io, [
            'terrain'       => $terrains['ES-01'],
            'client'        => $clients[2],
            'prixVente'     => '32000000',
            'apportInitial' => '10000000',
            'resteAPayer'   => '22000000',
            'typeVente'     => 'sans_papier_gestion_agence',
            'etat'          => 'en_cours',
            'dateVente'     => new \DateTime('2024-06-20'),
            'versements'    => [
                ['montant' => '10000000', 'ref' => 'Apport Initial', 'mode' => 'Virement', 'date' => new \DateTime('2024-06-20')],
                ['montant' => '5000000',  'ref' => 'Versement JUIL-24', 'mode' => 'Chèque', 'date' => new \DateTime('2024-07-15')],
                ['montant' => '5000000',  'ref' => 'Versement OCT-24', 'mode' => 'Virement', 'date' => new \DateTime('2024-10-10')],
            ],
            'echanciers' => [
                ['montant' => '4400000', 'datePrevue' => new \DateTime('2024-08-01'), 'etat' => 'paye'],
                ['montant' => '4400000', 'datePrevue' => new \DateTime('2024-09-01'), 'etat' => 'paye'],
                ['montant' => '4400000', 'datePrevue' => new \DateTime('2024-10-01'), 'etat' => 'paye'],
                ['montant' => '4400000', 'datePrevue' => new \DateTime('2025-01-01'), 'etat' => 'en_attente'],
                ['montant' => '4400000', 'datePrevue' => new \DateTime('2025-04-01'), 'etat' => 'en_attente'],
            ],
            'demarche' => [
                'titre'         => 'Démarches pour LOT ES-01 — DIABATÉ Ibrahim',
                'fraisEstimes'  => '2000000',
                'statutGlobal'  => 'en_cours',
                'etapesValidees' => [
                    'Lettre d\'Attribution',
                    'Compromis de Vente',
                    'Approbation Coutumière',
                    'Géomètre / Bornage',
                ],
            ],
        ], $agence, $entreprise, $typesEtapesActifs);

        // ─── Résumé final ─────────────────────────────────────────────────
        $io->newLine();
        $io->success("✅ Seed terminé avec succès !");
        $io->table(
            ['Entité', 'Nombre', 'Détail'],
            [
                ['TypeEtapeDemarche', count($typesEtapesActifs) ?: 7, '7 étapes de démarche standard'],
                ['TypeFraisTerrain',  count(TypeFraisTerrain::DEFAULTS), '5 types de frais'],
                ['Sites',             count($sites), 'Les Cocotiers, Étoile du Sud'],
                ['Terrains',          count($terrains), '6 lots Cocotiers + 4 lots Étoile du Sud'],
                ['Clients',           count($clients), '4 acheteurs'],
                ['Ventes',            '3', '1 soldée, 2 en cours avec démarches'],
            ]
        );

        return Command::SUCCESS;
    }

    // ═════════════════════════════════════════════════════════════════
    // Méthode utilitaire : créer une vente complète
    // ═════════════════════════════════════════════════════════════════
    private function creerVente(
        SymfonyStyle $io,
        array $data,
        ?Agence $agence,
        Entreprise $entreprise,
        array $typesEtapes
    ): void {
        $terrain = $data['terrain'];
        $client  = $data['client'];

        // Éviter les doublons : une vente par terrain
        $existing = $this->em->getRepository(VenteTerrain::class)->findOneBy([
            'terrain'    => $terrain,
            'entreprise' => $entreprise,
        ]);

        if ($existing) {
            $io->comment("Vente pour LOT {$terrain->getNum()} existe déjà. Ignorée.");
            return;
        }

        $vente = new VenteTerrain();
        $vente->setTerrain($terrain);
        $vente->setClient($client);
        $vente->setPrixVente($data['prixVente']);
        $vente->setApportInitial($data['apportInitial']);
        $vente->setResteAPayer($data['resteAPayer']);
        $vente->setTypeVente($data['typeVente']);
        $vente->setEtat($data['etat']);
        $vente->setDateVente($data['dateVente']);
        $vente->setAgence($agence);
        $vente->setEntreprise($entreprise);
        $this->em->persist($vente);

        // ── Versements ──────────────────────────────────────────────────
        foreach ($data['versements'] ?? [] as $vd) {
            $versement = new VersementTerrain();
            $versement->setMontant($vd['montant']);
            $versement->setVenteTerrain($vente);
            $versement->setReference($vd['ref']);
            $versement->setModePaiement($vd['mode']);
            $versement->setDateVersement($vd['date']);
            $this->em->persist($versement);
        }

        // ── Échéancier ──────────────────────────────────────────────────
        foreach ($data['echanciers'] ?? [] as $ed) {
            $echeance = new EchancierTerrain();
            $echeance->setMontant($ed['montant']);
            $echeance->setDatePrevue($ed['datePrevue']);
            $echeance->setEtat($ed['etat']);
            $echeance->setVenteTerrain($vente);
            $this->em->persist($echeance);
        }

        // ── Démarche Administrative ──────────────────────────────────────
        if (!empty($data['demarche'])) {
            $dd = $data['demarche'];

            $demarche = new DemarcheAdministrative();
            $demarche->setTitre($dd['titre']);
            $demarche->setFraisEstimes($dd['fraisEstimes']);
            $demarche->setStatutGlobal($dd['statutGlobal']);
            $demarche->setVenteTerrain($vente);
            $this->em->persist($demarche);

            $etapesValidees = $dd['etapesValidees'] ?? [];

            foreach ($typesEtapes as $typeEtape) {
                $etape = new EtapeDemarche();
                $etape->setTypeEtape($typeEtape);
                $etape->setNomEtape($typeEtape->getNom());
                $etape->setDemarche($demarche);

                if (in_array($typeEtape->getNom(), $etapesValidees)) {
                    $etape->setStatut('termine');
                    $etape->setDateValidation(new \DateTime());
                    $etape->setCommentaire('Étape validée — données de démonstration');
                } else {
                    $etape->setStatut('attente');
                }

                $this->em->persist($etape);
            }
        }

        $this->em->flush();
        $io->success("✓ Vente LOT {$terrain->getNum()} — {$client->getNom()} {$client->getPrenoms()} créée");
    }

    // ═════════════════════════════════════════════════════════════════
    // Méthode de purge (option --purge)
    // ═════════════════════════════════════════════════════════════════
    private function purgeExistingData(Entreprise $entreprise, Agence $agence, SymfonyStyle $io): void
    {
        // Supprimer dans l'ordre des dépendances
        $conn = $this->em->getConnection();

        // Chercher les ventes de l'entreprise
        $ventes = $this->em->getRepository(VenteTerrain::class)->findBy(['entreprise' => $entreprise]);
        foreach ($ventes as $v) {
            // Supprimer les versements, échéanciers, démarches (cascade le fera aussi)
            $this->em->remove($v);
        }

        $clients = $this->em->getRepository(ClientTerrain::class)->findBy(['entreprise' => $entreprise]);
        foreach ($clients as $c) { $this->em->remove($c); }

        $terrains = $this->em->getRepository(Terrain::class)->findBy(['entreprise' => $entreprise]);
        foreach ($terrains as $t) { $this->em->remove($t); }

        $sites = $this->em->getRepository(Site::class)->findBy(['entreprise' => $entreprise]);
        foreach ($sites as $s) { $this->em->remove($s); }

        $typesEtapes = $this->em->getRepository(TypeEtapeDemarche::class)->findBy(['entreprise' => $entreprise]);
        foreach ($typesEtapes as $te) { $this->em->remove($te); }

        $typesFrais = $this->em->getRepository(TypeFraisTerrain::class)->findBy(['entreprise' => $entreprise]);
        foreach ($typesFrais as $tf) { $this->em->remove($tf); }

        $this->em->flush();
        $io->warning("Purge effectuée.");
    }
}
