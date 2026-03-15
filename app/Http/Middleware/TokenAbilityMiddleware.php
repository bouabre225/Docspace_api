<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAbilityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Non Authentifié'], 401);

        if (!$user->tokenCan($ability)) return response()->json(['message' => 'Accès refusé (permission token manquante)'], 403);
        
        return $next($request);
    }
}
