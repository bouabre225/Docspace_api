<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EnableTwoFactorRequest;
use App\Http\Requests\VerifyTwoFactorRequest;
use App\Services\Auth\TwoFactorService;
use Illuminate\Routing\Controller;

class TwoFactorController extends Controller
{
    /**
     * Activer le 2FA pour l'utilisateur
     */
    public function enable(Request $request, TwoFactorService $service)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $payload = $service->generatePendingSecret($user);

        return response()->json([
            'message' => '2FA en attente de confirmation',
            'secret' => $payload['secret'],
            'otpauth_url' => $payload['otpauth_url'],
        ], 200);
    }

    /**
     * Vérifier le code 2FA
     */
    public function verify(Request $rawRequest, VerifyTwoFactorRequest $request, TwoFactorService $service)
    {
        $user = $rawRequest->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        try {
            $service->confirmEnable($user, $request->validated()['code']);

            // Si c'était un token bootstrap, on le détruit après activation
            if ($user->tokenCan('2fa-bootstrap') && $user->role == 'admin') {
                $user->currentAccessToken()?->delete();

                return response()->json([
                    'message' => '2FA activé. Token bootstrap supprimé. Reconnecte-toi via /admin/login.',
                ], 200);
            }

            return response()->json([
                'message' => '2FA activé',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Désactiver le 2FA pour l'utilisateur
     */
    public function disable(Request $rawRequest, VerifyTwoFactorRequest $request, TwoFactorService $service)
    {
        $user = $rawRequest->user();

        if ($user->tokenCan('2fa-bootstrap') && $user->role == 'admin') {
            return response()->json(['message' => 'Action interdite avec un token bootstrap.'], 403);
        }

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        try {
            $service->disable($user, $request->validated()['code']);

            return response()->json([
                'message' => '2FA désactivé',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
