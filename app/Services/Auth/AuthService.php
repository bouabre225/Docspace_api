<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    /**
     * Register a new user
     */
    public function registerBuyer(array $data) : User
    {
        //vérifie si l'email est déjà utilisé
        if (User::where('email', $data['email'])->exists()) {
            throw new \Exception('Email already used');
        }

        // Récupérer le mot de passe (soit 'password' soit 'mot_de_passe')
        $password = $data['password'] ?? $data['mot_de_passe'] ?? null;

        //renvoi l'utilisateur créé
        return User::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'mot_de_passe' => $password,
            'telephone' => $data['telephone'] ?? null,
            'pays' => $data['pays'] ?? 'France',
            'adresse' => $data['adresse'] ?? null,
            'role' => 'acheteur',
            'statut' => 'actif',
            'type_compte' => 'particulier',
        ]);
    }

    /**
     * Register a new seller
     */
    public function registerSeller(array $data) : User
    {
          //vérifie si l'email est déjà utilisé
        if (User::where('email', $data['email'])->exists()) {
            throw new \Exception('Email already used');
        }

        // vendeur: si non fourni → professionnel par défaut
        $typeCompte = $data['type_compte'] ?? 'professionnel';

        // Récupérer le mot de passe (soit 'password' soit 'mot_de_passe')
        $password = $data['password'] ?? $data['mot_de_passe'] ?? null;

        //renvoi l'utilisateur créé
        return User::create([
            'nom' => $data['nom'],
            'email' => $data['email'],
            'mot_de_passe' => $password,
            'telephone' => $data['telephone'] ?? null,
            'pays' => $data['pays'] ?? 'France',
            'adresse' => $data['adresse'] ?? null,
            'role' => 'vendeur',
            'statut' => 'actif',
            'type_compte' => $typeCompte,
            'verifie_kyc' => false,
        ]);
    }


    /**
     * Login:
     * - si user actif et pas de 2FA => retourne token
     * - si 2FA activé => retourne challenge_id
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('Identifiants invalides');
        }

        if (!Hash::check($password, $user->mot_de_passe)) {
            throw new \Exception('Identifiants invalides');
        }

        if (!$user->isActive()) {
            // 423 Locked est propre pour un compte bloqué
            throw new \RuntimeException('Compte suspendu', 423);
        }

        // 2FA activé -> challenge
        if ($user->has2faEnabled()) {
            $challengeId = (string) Str::uuid();

            Cache::put(
                "login_2fa_challenge:{$challengeId}",
                ['user_id' => $user->id, 'device_name' => $deviceName],
                now()->addMinutes(10)
            );

            return [
                'requires_2fa' => true,
                'challenge_id' => $challengeId,
            ];
        }

        $token = $user->createToken($deviceName ?: 'auth_token')->plainTextToken;

        return [
            'requires_2fa' => false,
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Login admin:
     * - si user actif et pas de 2FA => retourne token
     * - si 2FA activé => retourne challenge_id
     */
    public function loginAdmin(string $email, string $password, ?string $deviceName = null): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->mot_de_passe)) {
            throw new \Exception('Identifiants invalides');
        }

        if (!$user->isActive()) {
            throw new \RuntimeException('Compte suspendu', 423);
        }

        if ($user->role !== 'admin') {
            throw new \Exception('Accès réservé aux admins');
        }

        if (!$user->has2faEnabled()) {
            $bootstrapToken = $user->createToken(
                $deviceName ?: 'admin_bootstrap',
                ['2fa-bootstrap'] // ability UNIQUE
            )->plainTextToken;

            return [
                'requires_2fa_setup' => true,
                'message' => '2FA obligatoire pour les admins. Token bootstrap délivré uniquement pour activer 2FA.',
                'token' => $bootstrapToken,
                'user' => $user,
            ];
        }

        //Si 2FA déjà activé: on force le flow normal (challenge)
        return $this->create2faChallenge($user, $deviceName, true);
    }

    private function create2faChallenge(User $user, ?string $deviceName, bool $isAdmin): array
    {
        $challengeId = (string) Str::uuid();

        Cache::put(
            "login_2fa_challenge:{$challengeId}",
            [
                'user_id' => $user->id,
                'device_name' => $deviceName,
                'is_admin' => $isAdmin,
            ],
            now()->addMinutes(10)
        );

        return [
            'requires_2fa' => true,
            'challenge_id' => $challengeId,
        ];
    }


    /**
     * Finalise le login 2FA:
     * - vérifie le code via TwoFactorService (appelé dans controller)
     * - supprime challenge
     * - délivre token
     */
    public function issueTokenAfter2fa(int $userId, ?string $deviceName = null): array
    {
        $user = User::findOrFail($userId);

        if (!$user->isActive()) {
            throw new \RuntimeException('Compte suspendu', 423);
        }

        $token = $user->createToken($deviceName ?: 'auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}