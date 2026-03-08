<?php

namespace App\Services;

use App\Models\Commandes;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use FedaPay\FedaPay;
use FedaPay\Transaction;

class paiementService
{
    public function __construct()
    {
        FedaPay::setApiKey(config('services.fedapay.secret'));
        FedaPay::setEnvironment(config('services.fedapay.environment', 'sandbox'));
    }

    public function createPayment(Commandes $commande)
    {
        return DB::transaction(function () use ($commande) {
            try {
                // Créer une transaction FedaPay
                $transaction = Transaction::create([
                    'description' => "Commande #{$commande->id}",
                    'amount' => $commande->montant,
                    'currency' => ['iso' => 'XOF'],
                    'callback_url' => route('fedapay.webhook'),
                    'customer' => [
                        'firstname' => $commande->acheteur->nom,
                        'lastname' => '',
                        'email' => $commande->acheteur->email,
                        'phone_number' => [
                            'number' => $commande->acheteur->telephone,
                            'country' => 'bj'
                        ]
                    ]
                ]);

                // Générer le token de paiement
                $token = $transaction->generateToken();

                return Paiement::create([
                    'commande_id' => $commande->id,
                    'moyen' => 'fedapay',
                    'montant' => $commande->montant,
                    'statut' => 'en_attente',
                    'provider_reference' => $transaction->id
                ]);

            } catch (\Exception $e) {
                Log::error('FedaPay transaction creation failed', [
                    'error' => $e->getMessage(),
                    'commande_id' => $commande->id
                ]);
                
                // Fallback vers URL simple
                $paymentUrl = $this->generateFedaPayUrl($commande);

                return Paiement::create([
                    'commande_id' => $commande->id,
                    'moyen' => 'fedapay',
                    'montant' => $commande->montant,
                    'statut' => 'en_attente',
                    'provider_reference' => $paymentUrl
                ]);
            }
        });
    }
    public function handleWebhookEvent(string $event, string $transactionId)
    {
        return DB::transaction(function () use ($event, $transactionId) {
            $paiement = Paiement::where('provider_reference', 'LIKE', "%{$transactionId}%")
                ->firstOrFail();

            $commande = $paiement->commande;

            switch ($event) {
                case 'transaction.approved':
                    $paiement->update([
                        'statut' => 'bloque',
                        'date_paiement' => now(),
                    ]);

                    $commande->update([
                        'statut' => 'payee',
                    ]);

                    Log::info('Payment approved', [
                        'transaction_id' => $transactionId,
                        'commande_id' => $commande->id,
                    ]);
                    break;

                case 'transaction.canceled':
                    $paiement->update(['statut' => 'annule']);
                    $commande->update(['statut' => 'annulee']);
                    $commande->annonce->increment('quantite', $commande->quantite);

                    Log::warning('Payment canceled', [
                        'transaction_id' => $transactionId,
                        'commande_id' => $commande->id,
                    ]);
                    break;

                case 'transaction.failed':
                    $paiement->update(['statut' => 'echoue']);

                    Log::error('Payment failed', [
                        'transaction_id' => $transactionId,
                        'commande_id' => $commande->id,
                    ]);
                    break;

                default:
                    Log::warning('Unknown webhook event', ['event' => $event]);
            }

            return $paiement;
        });
    }

    private function generateFedaPayUrl(Commandes $commande): string
    {
        return config('services.fedapay.base_url') . '/pay?amount=' . $commande->montant . '&order_id=' . $commande->id;
    }
}
