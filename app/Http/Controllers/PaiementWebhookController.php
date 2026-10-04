<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Services\paiementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use FedaPay\Transaction;
use Illuminate\Support\Facades\Log;
use App\Mail\FactureMail;
use Illuminate\Support\Facades\Mail;
use App\Events\CommandeStatusChanged;

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

        if ((int) $commande->montant < 100) {
            return response()->json(['success' => false, 'message' => 'Montant minimum 100 FCFA pour le paiement en ligne (FedaPay).'], 422);
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
            Log::error('Création paiement échouée', ['error' => $e->getMessage(), 'commande_id' => $commande->id]);
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'initier le paiement. Réessaie plus tard.',
            ], 500);
        }
    }

   public function handleWebhook(Request $request)
    {
        $expectedSecret = config('services.fedapay.webhook_secret');
        if (!empty($expectedSecret)) {
            $provided = $request->header('X-FedaPay-Signature') ?? $request->input('webhook_secret');
            if (!hash_equals((string) $expectedSecret, (string) $provided)) {
                Log::warning('FedaPay webhook signature invalide');
                return response()->json(['success' => false, 'message' => 'Signature invalide'], 403);
            }
        }
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

    public function verify(Commande $commande)
    {        if ($commande->acheteur_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!$commande->paiement) {
            return response()->json(['statut' => $commande->statut]);
        }

        if (in_array($commande->statut, ['payee', 'livree', 'cloturee', 'annulee', 'litige'])) {
            return response()->json(['statut' => $commande->statut]);
        }

        try {
            $transaction = Transaction::retrieve($commande->paiement->provider_reference);

            if (($transaction->status ?? null) === 'approved') {
                if ((int) ($transaction->amount ?? 0) !== (int) $commande->montant) {
                    Log::warning('Verify montant incohérent', ['commande_id' => $commande->id]);
                    return response()->json(['statut' => $commande->statut], 422);
                }
                $commande->paiement->update([
                    'statut'        => 'bloque',
                    'date_paiement' => now(),
                ]);
                $commande->update(['statut' => 'payee']);
                $commande->load(['acheteur', 'vendeur', 'annonce']);

                event(new CommandeStatusChanged($commande, 'payee'));

                try {
                    \Mail::to($commande->acheteur->email)
                        ->queue(new \App\Mail\FactureMail($commande));
                } catch (\Exception $e) {
                    Log::error('Envoi facture verify échoué', ['error' => $e->getMessage()]);
                }
            }

            return response()->json(['statut' => $commande->fresh()->statut]);

        } catch (\Exception $e) {
            Log::error('Verify payment error', ['error' => $e->getMessage(), 'commande_id' => $commande->id]);
            return response()->json(['statut' => $commande->statut]);
        }
    }

    /**
     * Renvoie la facture par email (acheteur de la commande ou admin).
     * Utile si le mail initial est parti en spam / jamais reçu.
     */
    public function renvoyerFacture(Commande $commande)
    {
        $user = auth()->user();
        $isAcheteur = $commande->acheteur_id === $user->id;
        $isAdmin = $user->role === 'admin';

        if (!$isAcheteur && !$isAdmin) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if (!in_array($commande->statut, ['payee', 'livree', 'cloturee'])) {
            return response()->json(['message' => 'Facture disponible uniquement après paiement.'], 422);
        }

        $commande->loadMissing(['acheteur', 'vendeur', 'annonce']);

        try {
            \Mail::to($commande->acheteur->email)
                ->queue(new \App\Mail\FactureMail($commande));
        } catch (\Exception $e) {
            Log::error('Renvoi facture échoué', ['error' => $e->getMessage(), 'commande_id' => $commande->id]);
            return response()->json(['message' => 'Envoi impossible pour le moment.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Facture envoyée à ' . $commande->acheteur->email]);
    }
}
