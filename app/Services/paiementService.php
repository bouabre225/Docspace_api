<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class paiementService
{
    public function createPayment(Commande $commande): array
    {
        return DB::transaction(function () use ($commande) {

            if ((int) $commande->montant < 100) {
                throw new \Exception('Le montant minimum pour le paiement est 100 XOF.');
            }

            $client = new \GuzzleHttp\Client();

            // ── Créer la transaction ──────────────────────────────────────
            $response = $client->post('https://api.fedapay.com/v1/transactions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.fedapay.secret'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'description'  => 'Commande #' . substr($commande->id, 0, 8) . ' - DocSpace',
                    'amount'       => (int) $commande->montant,
                    'currency'     => ['iso' => 'XOF'],
                    'callback_url' => route('fedapay.webhook'),
                    'customer'     => [
                        'firstname' => $commande->acheteur->nom ?? 'Client',
                        'lastname'  => ' ',
                        'email'     => $commande->acheteur->email,
                        'phone_number' => [
                            'number'  => $commande->acheteur->telephone ?? '97000000',
                            'country' => 'BJ',
                        ],
                    ],
                ],
                'http_errors' => false,
            ]);

            $body   = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            Log::info('FedaPay create transaction', ['status' => $status, 'body' => $body]);

            if ($status >= 400 || $body === null) {
                throw new \Exception('FedaPay indisponible (status ' . $status . ')');
            }

            $transactionData = $body['v1/transaction'] ?? $body['transaction'] ?? null;

            if (!$transactionData || empty($transactionData['id'])) {
                throw new \Exception('FedaPay: transaction ID manquant. Réponse: ' . json_encode($body));
            }

            $transactionId = $transactionData['id'];

            // ── Récupérer le token ────────────────────────────────────────
            $token = $transactionData['payment_token'] ?? null;

            if (!$token) {
                $tokenResponse = $client->post("https://api.fedapay.com/v1/transactions/{$transactionId}/token", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . config('services.fedapay.secret'),
                        'Content-Type'  => 'application/json',
                    ],
                    'http_errors' => false,
                ]);

                $tokenBody   = json_decode($tokenResponse->getBody(), true);
                $tokenStatus = $tokenResponse->getStatusCode();

                Log::info('FedaPay token fallback', ['status' => $tokenStatus, 'body' => $tokenBody]);

                if ($tokenStatus >= 400) {
                    throw new \Exception('FedaPay token error: ' . json_encode($tokenBody));
                }

                $token = $tokenBody['token'] ?? null;
            }

            if (!$token) {
                throw new \Exception('FedaPay: token manquant.');
            }

            // ── Sauvegarder le paiement ───────────────────────────────────
            Paiement::create([
                'commande_id'        => $commande->id,
                'moyen'              => 'fedapay',
                'montant'            => $commande->montant,
                'statut'             => 'en_attente',
                'provider_reference' => (string) $transactionId,
            ]);

            return [
                'token'          => $token,
                'payment_url'    => $transactionData['payment_url'] ?? "https://process.fedapay.com/{$token}",
                'transaction_id' => $transactionId,
                'montant'        => $commande->montant,
            ];
        });
    }

    public function handleWebhookEvent(string $event, string $transactionId): Paiement
    {
        return DB::transaction(function () use ($event, $transactionId) {
            $paiement = Paiement::where('provider_reference', 'LIKE', "%{$transactionId}%")
                ->firstOrFail();

            $commande = $paiement->commande;

            switch ($event) {
                case 'transaction.approved':
                    $paiement->update([
                        'statut'        => 'bloque',
                        'date_paiement' => now(),
                    ]);
                    $commande->update(['statut' => 'payee']);
                    Log::info('Payment approved', [
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
                        'commande_id'    => $commande->id,
                    ]);
                    break;

                case 'transaction.failed':
                    $paiement->update(['statut' => 'echoue']);
                    Log::error('Payment failed', [
                        'transaction_id' => $transactionId,
                        'commande_id'    => $commande->id,
                    ]);
                    break;

                default:
                    Log::warning('Unknown webhook event', ['event' => $event]);
            }

            return $paiement;
        });
    }
}