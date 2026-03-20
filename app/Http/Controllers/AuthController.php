<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterBuyerRequest;
use App\Http\Requests\RegisterSellerRequest;
use App\Http\Requests\Login2faRequest;
use App\Http\Requests\loginRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

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
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'User registered successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'User registered successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'success' => true,
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

            return response()->json([
                'message'      => 'Code envoyé par mail',
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

        $userId = (string) $challenge['user_id'];
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
            'success' => true,
            'message' => $isAdminFlow ? 'Admin logged in successfully' : 'User logged in successfully',
            'user' => $issued['user'],
            'token' => $issued['token'],
        ], 200);
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // On retourne toujours 200 pour ne pas révéler si l'email existe
        return response()->json([
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
        ], 200);
    }

    /**
     * Reset mot de passe 
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'mot_de_passe'              => 'required|min:8|same:password_confirmation',
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'mot_de_passe', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'mot_de_passe' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
        }

        return response()->json([
            'message' => match($status) {
                Password::INVALID_TOKEN => 'Token invalide ou expiré.',
                Password::INVALID_USER  => 'Aucun compte associé à cet email.',
                default                 => 'Erreur lors de la réinitialisation.',
            }
        ], 422);
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

        return response()->json(['success' => true, 'message' => 'Déconnecté'], 200);
    }
}
