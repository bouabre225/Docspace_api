<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KycVerifiedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // On bloque seulement les vendeurs non validés
        if ($user->role === 'vendeur' && !$user->verifie_kyc) {
            return response()->json([
                'message' => 'Votre compte vendeur est en attente de validation KYC. Vous ne pouvez pas créer d’annonces.',
            ], 403);
        }

        return $next($request);
    }
}
