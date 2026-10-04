<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\Favori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriController extends Controller
{
    // Mes favoris (acheteur connecté)
    public function index(): JsonResponse
    {
        $favoris = Favori::with(['annonce.images', 'annonce.vendeur:id,nom'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $favoris]);
    }

    // Toggle favori (ajoute ou retire)
    public function toggle(Annonce $annonce): JsonResponse
    {
        $existant = Favori::where('user_id', Auth::id())
            ->where('annonce_id', $annonce->id)
            ->first();

        if ($existant) {
            $existant->delete();
            return response()->json(['success' => true, 'favori' => false]);
        }

        Favori::create(['user_id' => Auth::id(), 'annonce_id' => $annonce->id]);

        return response()->json(['success' => true, 'favori' => true], 201);
    }

    // Sync des favoris locaux (au login) : { ids: [uuid...] }
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'nullable|array|max:200',
            'ids.*' => 'uuid|exists:annonces,id',
        ]);

        foreach ($validated['ids'] ?? [] as $annonceId) {
            Favori::firstOrCreate(['user_id' => Auth::id(), 'annonce_id' => $annonceId]);
        }

        $ids = Favori::where('user_id', Auth::id())->pluck('annonce_id');

        return response()->json(['success' => true, 'data' => $ids]);
    }
}
