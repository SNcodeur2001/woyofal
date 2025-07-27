<?php

namespace App\Service;

use App\Entity\Achat;
use App\Entity\LogAchat;
use App\Repository\CompteurRepository;
use App\Repository\ClientRepository;
use App\Repository\TrancheRepository;
use App\Repository\AchatRepository;
use App\Repository\LogAchatRepository;

class WoyofalService
{
    private CompteurRepository $compteurRepository;
    private ClientRepository $clientRepository;
    private TrancheRepository $trancheRepository;
    private AchatRepository $achatRepository;
    private LogAchatRepository $logRepository;

    public function __construct()
    {
        $this->compteurRepository = new CompteurRepository();
        $this->clientRepository = new ClientRepository();
        $this->trancheRepository = new TrancheRepository();
        $this->achatRepository = new AchatRepository();
        $this->logRepository = new LogAchatRepository();
    }

    public function effectuerAchat(string $numero_compteur, float $montant): array
    {
        $log = new LogAchat();
        $log->setNumeroCompteur($numero_compteur)
            ->setLocalisation('Dakar, Sénégal')
            ->setAdresseIp($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') // Ajouter l'IP
            ->setStatut('Échec');

        try {
            // 1. Vérifier l'existence du compteur
            $compteur = $this->compteurRepository->findByNumero($numero_compteur);

            if (!$compteur || !$compteur->isActif()) {
                $log->setMessageErreur('Numéro de compteur inexistant ou inactif');
                $this->logRepository->save($log);

                return [
                    'data' => null,
                    'statut' => 'error',
                    'code' => 404,
                    'message' => 'Numéro de compteur inexistant ou inactif'
                ];
            }

            // 2. Récupérer les informations du client
            $client = $this->clientRepository->findById($compteur->getClientId());
            if (!$client) {
                $log->setMessageErreur('Client associé au compteur introuvable');
                $this->logRepository->save($log);

                return [
                    'data' => null,
                    'statut' => 'error',
                    'code' => 404,
                    'message' => 'Client associé au compteur introuvable'
                ];
            }

            // 3. Valider le montant minimum
            if ($montant < 100) {
                $log->setMessageErreur('Montant minimum requis: 100 FCFA');
                $this->logRepository->save($log);

                return [
                    'data' => null,
                    'statut' => 'error',
                    'code' => 400,
                    'message' => 'Montant minimum requis: 100 FCFA'
                ];
            }

            // 4. Calculer les kWh selon les tranches
            $calculResult = $this->calculerKilowatts($montant);

            // 5. Générer l'achat
            $achat = new Achat();
            $achat->setReference($this->genererReference())
                ->setCodeRecharge($this->genererCodeRecharge())
                ->setNumeroCompteur($numero_compteur)
                ->setMontant($montant)
                ->setNbreKwt($calculResult['kwh'])
                ->setTranche($calculResult['tranche'])
                ->setPrixKw($calculResult['prix_moyen'])
                ->setClientNom($client->getNomComplet());

            // 6. Sauvegarder l'achat
            $this->achatRepository->save($achat);

            // 7. Logger le succès
            $log->setStatut('Success')
                ->setCodeRecharge($achat->getCodeRecharge())
                ->setNbreKwt($achat->getNbreKwt())
                ->setMessageErreur(null);
            $this->logRepository->save($log);

            // 8. Retourner la réponse
            return [
                'data' => [
                    'compteur' => $achat->getNumeroCompteur(),
                    'reference' => $achat->getReference(),
                    'code' => $achat->getCodeRecharge(),
                    'date' => $achat->getDateAchat()->format('Y-m-d\TH:i:s\Z'),
                    'tranche' => $achat->getTranche(),
                    'prix' => (string)$achat->getPrixKw(),
                    'nbreKwt' => (string)$achat->getNbreKwt(),
                    'client' => strtolower($achat->getClientNom())
                ],
                'statut' => 'success',
                'code' => 200,
                'message' => 'Achat effectué avec succès'
            ];
        } catch (\Exception $e) {
            $log->setMessageErreur($e->getMessage());

            // Essayer de sauvegarder le log, mais ne pas faire échouer si ça ne marche pas
            try {
                $this->logRepository->save($log);
            } catch (\Exception $logError) {
                error_log("Erreur lors de la sauvegarde du log: " . $logError->getMessage());
            }

            return [
                'data' => null,
                'statut' => 'error',
                'code' => 500,
                'message' => 'Erreur système: ' . $e->getMessage()
            ];
        }
    }

    public function verifierCompteur(string $numero_compteur): array
    {
        try {
            // SUPPRIMER CETTE LIGNE !
            // var_dump('ok');die();

            $compteur = $this->compteurRepository->findByNumero($numero_compteur);

            if (!$compteur || !$compteur->isActif()) {
                return [
                    'data' => null,
                    'statut' => 'error',
                    'code' => 404,
                    'message' => 'Numéro de compteur inexistant ou inactif'
                ];
            }

            $client = $this->clientRepository->findById($compteur->getClientId());

            return [
                'data' => [
                    'compteur' => $compteur->getNumero(),
                    'client' => $client ? $client->getNomComplet() : 'Client inconnu',
                    'actif' => $compteur->isActif(),
                    'date_creation' => $compteur->getDateCreation()->format('Y-m-d\TH:i:s\Z')
                ],
                'statut' => 'success',
                'code' => 200,
                'message' => 'Compteur trouvé'
            ];
        } catch (\Exception $e) {
            return [
                'data' => null,
                'statut' => 'error',
                'code' => 500,
                'message' => 'Erreur système: ' . $e->getMessage()
            ];
        }
    }

    // ... reste du code identique (calculerKilowatts, genererReference, etc.)

    private function calculerKilowatts(float $montant): array
    {
        // Tranches de prix par kWh (en FCFA) - Identique à votre code original
        $tranches = [
            ['nom' => 'Tranche 1', 'min' => 0, 'max' => 5000, 'prix' => 98],
            ['nom' => 'Tranche 2', 'min' => 5001, 'max' => 15000, 'prix' => 105],
            ['nom' => 'Tranche 3', 'min' => 15001, 'max' => 30000, 'prix' => 115],
            ['nom' => 'Tranche 4', 'min' => 30001, 'max' => PHP_INT_MAX, 'prix' => 125]
        ];

        $montantRestant = $montant;
        $kwhTotal = 0;
        $trancheUtilisee = '';
        $totalMontantCalcule = 0;
        $montantCumule = 0;

        foreach ($tranches as $tranche) {
            if ($montantRestant <= 0) break;

            $capaciteTrancheMax = $tranche['max'] - $tranche['min'] + 1;

            if ($tranche['max'] == PHP_INT_MAX) {
                $capaciteTrancheMax = $montantRestant;
            }

            if ($montantCumule + $montantRestant > $tranche['min']) {
                $montantEntreeDansTranche = max(0, $tranche['min'] - $montantCumule);
                $montantDansTranche = min($montantRestant - $montantEntreeDansTranche, $capaciteTrancheMax - $montantEntreeDansTranche);

                if ($montantDansTranche > 0) {
                    $kwhTranche = $montantDansTranche / $tranche['prix'];
                    $kwhTotal += $kwhTranche;
                    $totalMontantCalcule += $montantDansTranche;

                    if (empty($trancheUtilisee)) {
                        $trancheUtilisee = $tranche['nom'];
                    }

                    $montantRestant -= $montantDansTranche;
                }
            }

            $montantCumule += $capaciteTrancheMax;
        }

        $prixMoyen = $kwhTotal > 0 ? round($totalMontantCalcule / $kwhTotal, 2) : 0;

        return [
            'kwh' => round($kwhTotal, 2),
            'tranche' => $trancheUtilisee ?: 'Tranche 1',
            'prix_moyen' => $prixMoyen
        ];
    }

    private function genererReference(): string
    {
        $date = date('Ymd');

        try {
            // Version simplifiée : générer un numéro aléatoire unique
            $sequence = rand(1000, 9999);
            $reference = "WOY-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // Vérifier l'unicité si la méthode existe
            $maxTentatives = 10;
            $tentative = 0;

            while ($tentative < $maxTentatives) {
                try {
                    // Essayer de vérifier l'unicité si possible
                    if (method_exists($this->achatRepository, 'findByReference')) {
                        $existe = $this->achatRepository->findByReference($reference);
                        if (!$existe) {
                            break; // Référence unique trouvée
                        }
                    } else {
                        // Si pas de méthode de vérification, accepter la référence
                        break;
                    }

                    // Générer une nouvelle référence
                    $sequence = rand(1000, 9999);
                    $reference = "WOY-{$date}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $tentative++;
                } catch (\Exception $e) {
                    // En cas d'erreur, continuer avec la référence actuelle
                    break;
                }
            }

            return $reference;
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser un numéro aléatoire avec timestamp
            $timestamp = time();
            return "WOY-{$date}-" . substr($timestamp, -4);
        }
    }

    private function genererCodeRecharge(): string
    {
        $maxTentatives = 10;
        $tentative = 0;

        do {
            $codes = [];
            for ($i = 0; $i < 4; $i++) {
                $codes[] = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            }
            $codeRecharge = implode('-', $codes);
            $tentative++;

            try {
                // Vérifier l'unicité si la méthode existe
                if (method_exists($this->achatRepository, 'existsByCodeRecharge')) {
                    $existe = $this->achatRepository->existsByCodeRecharge($codeRecharge);
                } elseif (method_exists($this->achatRepository, 'findByCodeRecharge')) {
                    $existe = $this->achatRepository->findByCodeRecharge($codeRecharge) !== null;
                } else {
                    // Si aucune méthode de vérification, accepter le code
                    $existe = false;
                }
            } catch (\Exception $e) {
                // Si erreur de vérification, accepter le code
                $existe = false;
            }
        } while ($existe && $tentative < $maxTentatives);

        // Si trop de tentatives, ajouter un timestamp pour garantir l'unicité
        if ($tentative >= $maxTentatives) {
            $codeRecharge .= '-' . substr(time(), -4);
        }

        return $codeRecharge;
    }
}
