<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnonceController extends Controller
{
    // Liste toutes les annonces actives
    public function index()
    {
        $annonces = Annonce::where('statut', 'active')
            ->with('vendeur')
            ->latest('created_at')
            ->paginate(12);
        
        return response()->json($annonces);
    }

    // Enregistrer une nouvelle annonce
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'nullable|string',
            'prix_vendeur' => 'required|numeric|min:0',
            'categorie' => 'nullable|string|max:100',
            'etat' => 'required|string',
            'quantite' => 'required|integer|min:1',
            'pays_expedition' => 'nullable|string|max:50'
        ]);

        $annonce = new Annonce($validated);
        $annonce->vendeur_id = Auth::id();
        $annonce->statut = 'active';
        $annonce->save();

        return response()->json([
            'message' => 'Annonce créée avec succès',
            'annonce' => $annonce->load('vendeur')
        ], 201);
    }

    // Afficher une annonce
    public function show(Annonce $annonce)
    {
        $annonce->load(['vendeur', 'avis.vendeur', 'images']);
        return response()->json($annonce);
    }

    // Mettre à jour
    public function update(Request $request, Annonce $annonce)
    {
        if ($annonce->vendeur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:200',
            'description' => 'nullable|string',
            'prix_vendeur' => 'required|numeric|min:0',
            'categorie' => 'nullable|string|max:100',
            'etat' => 'required|string',
            'quantite' => 'required|integer|min:1',
            'pays_expedition' => 'nullable|string|max:50'
        ]);

        $annonce->fill($validated);
        $annonce->save();

        return response()->json([
            'message' => 'Annonce mise à jour',
            'annonce' => $annonce->load('vendeur')
        ]);
    }

    // Supprimer
    public function destroy(Annonce $annonce)
    {
        if ($annonce->vendeur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $annonce->delete();

        return response()->json(['message' => 'Annonce supprimée']);
    }

    // Recherche
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        $annonces = Annonce::where('statut', 'active')
            ->where(function($q) use ($query) {
                $q->where('titre', 'ILIKE', "%{$query}%")
                  ->orWhere('description', 'ILIKE', "%{$query}%")
                  ->orWhere('categorie', 'ILIKE', "%{$query}%");
            })
            ->with('vendeur', 'avis')
            ->latest('created_at')
            ->paginate(12);

        return response()->json($annonces);
    }
}
