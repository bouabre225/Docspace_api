<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterBuyerRequest;
use App\Http\Requests\RegisterSellerRequest;
use App\Http\Requests\Login2faRequest;
use App\Http\Requests\loginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AuthController
{
    /**
     * Register a new user
     */
    public function registerBuyer(RegisterBuyerRequest $request, AuthService $authService)
    {
        try {
            //valider les données
            $data = $request->validated();
            $user = $authService->registerBuyer($data);

            // Créer un token pour l'utilisateur
            $token = $user->createToken('auth_token')->plainTextToken;

            //retour de la reponse
            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'User registered successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Register Seller
     */
    public function registerSeller(RegisterSellerRequest $request, AuthService $authService){
        try {
            //valider les donnees
            $data = $request->validated();
            $user = $authService->registerSeller($data);

            // Créer un token pour l'utilisateur
            $token = $user->createToken('auth_token')->plainTextToken;

            //retour de la reponse
            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'User registered successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Login normal:
     * - si requires_2fa => renvoie challenge_id
     * - sinon => renvoie token
     */
    public function login(LoginRequest $request, AuthService $authService)
    {
        try {
            $data = $request->validated();

            $result = $authService->login(
                $data['email'],
                $data['mot_de_passe'],
                $data['device_name'] ?? null
            );

            // Si 2FA requis, pas de token ici
            if (($result['requires_2fa'] ?? false) === true) {
                return response()->json([
                    'message' => '2FA requis',
                    'requires_2fa' => true,
                    'challenge_id' => $result['challenge_id'],
                ], 200);
            }

            return response()->json([
                'message' => 'User logged in successfully',
                'user' => $result['user'],
                'token' => $result['token'],
            ], 200);

        } catch (\RuntimeException $e) {
            // ex: compte suspendu
            $code = (int)$e->getCode();
            $code = ($code >= 100 && $code < 600) ? $code : 423;
            return response()->json(['message' => $e->getMessage()], $code);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }


    /**
     * Admin login: 2FA obligatoire.
     */
    public function loginAdmin(LoginRequest $request, AuthService $authService)
    {
        try {
            $data = $request->validated();

            $result = $authService->loginAdmin(
                $data['email'],
                $data['mot_de_passe'],
                $data['device_name'] ?? null
            );

            //Cas 1: admin sans 2FA => token limité 2FA
            if (($result['requires_2fa_setup'] ?? false) === true) {
                return response()->json([
                    'message' => $result['message'],
                    'requires_2fa_setup' => true,
                    'token' => $result['token'], // token limité uniquement 2FA
                    'user' => $result['user'],
                ], 200);
            }

            //Cas 2: admin avec 2FA => challenge obligatoire
            return response()->json([
                'message' => '2FA requis (admin)',
                'requires_2fa' => true,
                'challenge_id' => $result['challenge_id'],
            ], 200);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 403);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    /**
     * Finalisation login 2FA: si le challenge est admin => re-check role=admin.
     */
    public function login2fa(Login2faRequest $request, AuthService $authService, TwoFactorService $twoFactorService)
    {
        $data = $request->validated();

        $challenge = Cache::get("login_2fa_challenge:{$data['challenge_id']}");

        if (!$challenge) {
            return response()->json(['message' => 'Challenge expiré ou invalide.'], 400);
        }

        $userId = (int) $challenge['user_id'];
        $isAdminFlow = (bool) ($challenge['is_admin'] ?? false);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        // Si c'est un login admin, on re-check le rôle ici aussi (important)
        if ($isAdminFlow && $user->role !== 'admin') {
            Cache::forget("login_2fa_challenge:{$data['challenge_id']}");
            return response()->json(['message' => 'Accès réservé aux admins'], 403);
        }

        if (!$twoFactorService->verifyActiveSecret($user, $data['code'])) {
            return response()->json(['message' => 'Code 2FA invalide'], 422);
        }

        Cache::forget("login_2fa_challenge:{$data['challenge_id']}");

        $issued = $authService->issueTokenAfter2fa(
            $userId,
            $data['device_name'] ?? ($challenge['device_name'] ?? null)
        );

        return response()->json([
            'message' => $isAdminFlow ? 'Admin logged in successfully' : 'User logged in successfully',
            'user' => $issued['user'],
            'token' => $issued['token'],
        ], 200);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // Supprime le token courant uniquement
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnecté'], 200);
    }
}
