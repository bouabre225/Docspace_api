<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Services\paiementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PaiementWebhookController extends Controller
{
    public function __construct(
        private paiementService $service
    ) {}

    public function pay(Commande $commande)
    {
        if ($commande->acheteur_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if ($commande->statut !== 'en_attente') {
            return response()->json(['success' => false, 'message' => 'Cette commande ne peut pas être payée'], 400);
        }

        // Si un paiement en_attente existe depuis + d'1 min, le supprimer pour permettre un nouveau
        if ($commande->paiement && $commande->paiement->statut === 'en_attente') {
            if ($commande->paiement->created_at->diffInMinutes(now()) >= 1) {
                $commande->paiement->delete();
                $commande->unsetRelation('paiement');
            } else {
                return response()->json(['success' => false, 'message' => 'Un paiement est déjà en cours'], 400);
            }
        }

        if ($commande->paiement && !in_array($commande->paiement->statut, ['en_attente'])) {
            return response()->json(['success' => false, 'message' => 'Un paiement existe déjà'], 400);
        }

        try {
            $result = $this->service->createPayment($commande);

            return response()->json([
                'success' => true,
                'data'    => [
                    'token'          => $result['token'],           // ← token pour modal JS
                    'transaction_id' => $result['transaction_id'],
                    'montant'        => $result['montant'],
                ],
            ], 201);
        } catch (\Exception $e) {
            // ← temporaire pour debug
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        // ── Vérification de la signature FedaPay ──────────────────────────────
        $signature = $request->header('X-FedaPay-Signature');
        $webhookSecret = config('services.fedapay.webhook_secret');

        \Log::info('Webhook headers', [
            'signature' => $request->header('X-FedaPay-Signature'),
            'all_headers' => $request->headers->all(),
        ]);
        \Log::info('Webhook payload', ['body' => $request->getContent()]);

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

    public function verify(Commande $commande)
    {
        if ($commande->acheteur_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!$commande->paiement) {
            return response()->json(['statut' => $commande->statut]);
        }

        try {
            // Vérifier directement chez FedaPay
            $transaction = Transaction::retrieve($commande->paiement->provider_reference);
            
            if ($transaction->status === 'approved') {
                $commande->paiement->update([
                    'statut'        => 'bloque',
                    'date_paiement' => now(),
                ]);
                $commande->update(['statut' => 'payee']);
                $commande->load(['acheteur', 'vendeur', 'annonce']);
                \Mail::to($commande->acheteur->email)->queue(new \App\Mail\FactureMail($commande));
            }

            return response()->json(['statut' => $commande->fresh()->statut]);
        } catch (\Exception $e) {
            return response()->json(['statut' => $commande->statut]);
        }
    }
}
