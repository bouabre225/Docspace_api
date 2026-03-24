<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Events\CommandeStatusChanged;
use App\Mail\FactureMail;
use Illuminate\Support\Facades\Mail;


class paiementService
{
    public function __construct()
    {
        FedaPay::setApiKey(config('services.fedapay.secret'));
        FedaPay::setEnvironment(config('services.fedapay.environment'));
    }

    /**
     * Nettoie un numéro de téléphone pour FedaPay : garde les 8 derniers chiffres (format Bénin)
     */
    private function cleanPhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');
        return strlen($digits) >= 8 ? substr($digits, -8) : '00000000';
    }

    public function createPayment(Commande $commande): array
    {
        return DB::transaction(function () use ($commande) {
            $customerData = [
                'firstname' => $commande->acheteur->nom,
                'lastname'  => '',
                'email'     => $commande->acheteur->email,
            ];

            $phone = $this->cleanPhone($commande->acheteur->telephone);
            if ($phone !== '00000000') {
                $customerData['phone_number'] = [
                    'number'  => $phone,
                    'country' => 'bj',
                ];
            }

            // Créer la transaction FedaPay
            $transaction = Transaction::create([
                'description' => "Commande #{$commande->id} — DocSpace",
                'amount'      => (int) $commande->montant,
                'currency'    => ['iso' => 'XOF'],
                'callback_url'=> config('app.frontend_url', config('app.url')) . '/commandes/' . $commande->id,
                'customer'    => $customerData,
            ]);

            // Générer le token — c'est lui qu'on passe au modal JS FedaPay
            $token = $transaction->generateToken();

            // Sauvegarder le paiement en base
            Paiement::create([
                'commande_id'        => $commande->id,
                'moyen'              => 'fedapay',
                'montant'            => $commande->montant,
                'statut'             => 'en_attente',
                'provider_reference' => $transaction->id,
            ]);

            return [
                'token'          => $token->token,   // ← pour le modal JS
                'transaction_id' => $transaction->id,
                'montant'        => $commande->montant,
            ];
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

                    // Charger les relations pour la facture
                    $commande->load(['acheteur', 'vendeur', 'annonce']);

                    event(new CommandeStatusChanged($commande, 'payee'));

                    // Envoyer la facture à l'acheteur
                    Mail::to($commande->acheteur->email)
                        ->queue(new FactureMail($commande));

                    Log::info('Payment approved + facture envoyée', [
                        'transaction_id' => $transactionId,
                        'commande_id'    => $commande->id,
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

    private function generateFedaPayUrl(Commande $commande): string
    {
        return config('services.fedapay.base_url') . '/pay?amount=' . $commande->montant . '&order_id=' . $commande->id;
    }
}
