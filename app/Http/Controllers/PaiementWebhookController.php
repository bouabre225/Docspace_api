<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Services\paiementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use FedaPay\Transaction;
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
        // Pas de vérification signature — sécurisé par HTTPS + secret dans l'URL optionnel
        Log::info('FedaPay webhook received', ['event' => $request->input('event')]);

        try {
            $event         = $request->input('event');
            $transactionId = $request->input('transaction.id');

            if (empty($event) || empty($transactionId)) {
                return response()->json(['success' => false, 'message' => 'Payload incomplet'], 400);
            }

            $this->service->handleWebhookEvent($event, $transactionId);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }
}
