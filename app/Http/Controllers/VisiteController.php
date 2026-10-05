<?php

namespace App\Http\Controllers;

use App\Models\Visite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VisiteController extends Controller
{
    // Tracking anonyme : 1 vue / visiteur / annonce / 24h (anti-gonflage)
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'visitor_id' => 'required|string|max:64',
            'annonce_id' => 'nullable|uuid|exists:annonces,id',
            'page' => 'nullable|string|max:255',
            'referer' => 'nullable|string|max:500',
        ]);

        $dejaVue = Visite::where('visitor_id', $validated['visitor_id'])
            ->when(!empty($validated['annonce_id']),
                fn($q) => $q->where('annonce_id', $validated['annonce_id']),
                fn($q) => $q->whereNull('annonce_id')->where('page', $validated['page'] ?? '/'))
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if (!$dejaVue) {
            Visite::create([
                'visitor_id' => $validated['visitor_id'],
                'user_id' => $request->user('sanctum')?->id,
                'annonce_id' => $validated['annonce_id'] ?? null,
                'page' => $validated['page'] ?? '/',
                'referer' => substr($request->header('Referer', $validated['referer'] ?? ''), 0, 500) ?: null,
            ]);
        }

        return response()->noContent();
    }
}
