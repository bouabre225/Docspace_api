<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MeController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        return response()->json([
            'user' => $this->formatUser($user),
        ], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom'       => ['sometimes', 'string', 'max:100'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pays'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'adresse'   => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user'    => $this->formatUser($user->fresh()),
        ], 200);
    }

    private function formatUser($user): array
    {
        return [
            'id'                  => $user->id,
            'nom'                 => $user->nom,
            'email'               => $user->email,
            'role'                => $user->role,
            'statut'              => $user->statut,
            'type_compte'         => $user->type_compte,
            'telephone'           => $user->telephone,
            'pays'                => $user->pays,
            'adresse'             => $user->adresse,
            'verifie_kyc'         => $user->verifie_kyc,
            'badge_verifie'       => $user->badge_verifie,
            'note_moyenne'        => $user->note_moyenne,
            'two_factor_enabled'  => !empty($user->two_factor_secret),
            'two_factor_enable_at'=> $user->two_factor_enable_at,
            'created_at'          => $user->created_at,
        ];
    }
}