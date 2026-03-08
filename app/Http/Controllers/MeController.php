<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MeController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié',
            ], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'email' => $user->email,
                'role' => $user->role,
                'statut' => $user->statut,
                'type_compte' => $user->type_compte,

                'verifie_kyc' => $user->verifie_kyc,
                'badge_verifie' => $user->badge_verifie,
                'note_moyenne' => $user->note_moyenne,

                'two_factor_enabled' => !empty($user->two_factor_secret),
                'two_factor_enable_at' => $user->two_factor_enable_at,

                'created_at' => $user->created_at,
            ],
        ], 200);
    }
}
