<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;

class GoogleAuthService
{
    /**
     * 
     */
    public function handleGoogleUser(object $googleUser): array
    {
        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = User::create([
                'google_id' => $googleUser->id,
                'nom' => $googleUser->name ?? 'Utilisateur',
                'email' => $googleUser->email,
                'role' => 'acheteur',
                'type_compte' => 'particulier',
                'statut' => 'actif',
                // obligatoire (NOT NULL) - mutator hash
                'mot_de_passe' => Str::random(32),
            ]);
        } else {
            // Lier google_id si pas lié
            if (!$user->google_id) {
                $user->google_id = $googleUser->id;
                $user->save();
            }
        }

        if (!$user->isActive()) {
            // compte bloqué
            return [
                'message' => 'Compte suspendu',
            ];
        }

        // Si 2FA activé, on pourrait forcer un flow 2FA aussi, mais OAuth est déjà un facteur fort.
        $token = $user->createToken('google')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}