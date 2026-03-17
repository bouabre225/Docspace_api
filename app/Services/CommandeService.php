<?php

namespace App\Services;

use App\Models\Annonce;
use App\Models\Commande;
use Illuminate\Support\Facades\DB;

class CommandeService
{
    public function createOrder($user, string $annonce_id, int $quantite)
    {
        return DB::transaction(function () use ($user, $annonce_id, $quantite) {
            $annonce = Annonce::lockForUpdate()->findOrFail($annonce_id);

            if ($annonce->quantite < $quantite) {
                throw new \Exception('Stock insuffisant');
            }

            if ($annonce->vendeur_id === $user->id) {
                throw new \Exception('Vous ne pouvez pas commander votre propre annonce');
            }

            $montant = $annonce->prix_total * $quantite;

            $commande = Commande::create([
                'acheteur_id' => $user->id,
                'vendeur_id' => $annonce->vendeur_id,
                'annonce_id' => $annonce_id,
                'quantite' => $quantite,
                'montant' => $montant,
                'statut' => 'en_attente',
            ]);

            $annonce->decrement('quantite', $quantite);

            return $commande;
        });
    }
}
