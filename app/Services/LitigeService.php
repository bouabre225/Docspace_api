<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Litige;
use Illuminate\Support\Facades\DB;

class LitigeService
{
    /**
     * Ouvre un litige sur une commande livrée.
     */
    public function ouvrir(Commande $commande, int $acheteurId, string $motif, ?string $preuves): Litige
    {
        return DB::transaction(function () use ($commande, $acheteurId, $motif, $preuves) {
            $litige = Litige::create([
                'commande_id'     => $commande->id,
                'acheteur_id'     => $acheteurId,
                'motif'           => $motif,
                'preuves'         => $preuves,
                'statut'          => 'ouvert',
                'date_signalement'=> now(),
            ]);

            // Passer la commande en statut bloqué (empêche la clôture et le paiement du vendeur)
            $commande->update(['statut' => 'litige']);

            // Bloquer le paiement associé si libérable
            if ($commande->paiement && $commande->paiement->statut === 'libere') {
                $commande->paiement->update(['statut' => 'bloque']);
            }

            return $litige->load(['commande', 'acheteur']);
        });
    }

    /**
     * Résout un litige (admin uniquement).
     * $decision : 'rembourse' | 'rejete'
     */
    public function resoudre(Litige $litige, string $decision): Litige
    {
        return DB::transaction(function () use ($litige, $decision) {
            $litige->update(['statut' => 'resolu']);

            $commande = $litige->commande;

            if ($decision === 'rembourse') {
                // Rembourser l'acheteur → paiement marqué remboursé
                $commande->paiement?->update(['statut' => 'rembourse']);
                $commande->update(['statut' => 'annulee']);

                // Restituer le stock
                $commande->annonce->increment('quantite', $commande->quantite);
            } else {
                // Rejeter le litige → libérer le paiement au vendeur
                $commande->paiement?->update(['statut' => 'libere']);
                $commande->update(['statut' => 'cloturee']);
            }

            return $litige->fresh(['commande.acheteur', 'commande.vendeur']);
        });
    }
}