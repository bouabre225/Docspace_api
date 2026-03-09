<?php

namespace App\Http\Controllers;

use App\Models\Commandes;
use App\Services\paiementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaiementWebhookController
{
    public function __construct(
        private paiementService $service
    ) {}

    public function pay(Commandes $commande)
    {
        $this->authorize('view', $commande);

        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut pas être payée'
            ], 400);
        }

        if ($commande->paiement) {
            return response()->json([
                'success' => false,
                'message' => 'Un paiement existe déjà pour cette commande'
            ], 400);
        }

        try {
            $payment = $this->service->createPayment($commande);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_url' => $payment->provider_reference,
                    'payment_id' => $payment->id,
                    'montant' => $payment->montant,
                    'statut' => $payment->statut,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Payment creation failed', [
                'commande_id' => $commande->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Échec de création du paiement'
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        // ── Vérification de la signature FedaPay ──────────────────────────────
        $signature = $request->header('X-FedaPay-Signature');
        $webhookSecret = config('services.fedapay.webhook_secret');

        if (empty($webhookSecret)) {
            Log::warning('FedaPay webhook secret non configuré — vérification ignorée');
        } elseif (empty($signature)) {
            Log::warning('FedaPay webhook reçu sans signature', ['ip' => $request->ip()]);
            return response()->json([
                'success' => false,
                'message' => 'Signature manquante'
            ], 401);
        } else {
            $payload = $request->getContent();
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('FedaPay webhook : signature invalide', [
                    'ip' => $request->ip(),
                    'signature_reçue' => $signature,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Signature invalide'
                ], 401);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        Log::info('FedaPay webhook received', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $event = $request->input('event');
            $transactionId = $request->input('transaction.id');

            if (empty($event) || empty($transactionId)) {
                Log::warning('FedaPay webhook : payload incomplet', ['payload' => $request->all()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Payload incomplet'
                ], 400);
            }

            $result = $this->service->handleWebhookEvent($event, $transactionId);

            return response()->json([
                'success' => true,
                'message' => 'Webhook traité avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Échec du traitement du webhook'
            ], 500);
        }
    }
}
