<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AnnonceController extends Controller
{
    // Liste toutes les annonces actives
    public function index(Request $request)
    {
        $query = Annonce::with(['images', 'vendeur']);

        if ($request->boolean('my')) {
            $user = auth('sanctum')->user();
            if ($user) {
                $query->where('vendeur_id', $user->id);
            }
            $query->latest('created_at');
        } else {
            
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            } else {
                $query->where('statut', 'active');
            }

            // Recherche texte
            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('titre', 'ILIKE', '%' . $request->search . '%')
                    ->orWhere('categorie', 'ILIKE', '%' . $request->search . '%')
                    ->orWhere('description', 'ILIKE', '%' . $request->search . '%');
                });
            }

            // Filtre catégorie
            if ($request->filled('categorie')) {
                $query->where('categorie', $request->categorie);
            }

            // Filtre état
            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            // Tri — une seule fois, pas de double orderBy
            if ($request->sort === 'prix_asc') {
                $query->orderBy('prix_vendeur', 'asc');
            } elseif ($request->sort === 'prix_desc') {
                $query->orderBy('prix_vendeur', 'desc');
            } else {
                $query->latest('created_at');
            }
        }

        // Supprime ce bloc — il causait un double orderBy et ignorait le tri
        // if (!$request->filled('sort') || $request->boolean('my')) {
        //     $query->latest('created_at');
        // }

        return response()->json($query->paginate($request->per_page ?? 12));
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
        // Charge manuellement les avis via les commandes
        $annonce->load(['vendeur', 'images']);
        
        $commandeIds = $annonce->commandes()->pluck('id');
        $avis = \App\Models\Avis::whereIn('commande_id', $commandeIds)
            ->with('vendeur:id,nom,avatar')  // si tu as une relation vendeur sur Avis
            ->get();

        return response()->json([
            ...$annonce->toArray(),
            'avis' => $avis,
            'note_moyenne' => $annonce->noteMoyenne(),
        ]);
    }

    // Mettre à jour
    public function update(Request $request, Annonce $annonce)
    {
        if ($annonce->vendeur_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'titre' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'prix_vendeur' => 'nullable|numeric|min:0',
            'categorie' => 'nullable|string|max:100',
            'etat' => 'nullable|string',
            'quantite' => 'nullable|integer|min:1',
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
        $q = $request->input('q');

        $query = Annonce::where('statut', 'active')
            ->where(function($query) use ($q) {
                $query->where('titre', 'ILIKE', "%{$q}%")
                    ->orWhere('description', 'ILIKE', "%{$q}%")
                    ->orWhere('categorie', 'ILIKE', "%{$q}%");
            })
            ->with('vendeur', 'images');

        // Filtres additionnels
        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        if ($request->sort === 'prix_asc') {
            $query->orderBy('prix_vendeur', 'asc');
        } elseif ($request->sort === 'prix_desc') {
            $query->orderBy('prix_vendeur', 'desc');
        } else {
            $query->latest('created_at');
        }

        return response()->json($query->paginate(12));
    }

    public function countsParCategorie(): JsonResponse
    {
        $counts = Annonce::where('statut', 'active')
            ->selectRaw('categorie, COUNT(*) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie');

        return response()->json($counts);
    }

    public function adminDestroy(Annonce $annonce): JsonResponse
    {
        //Vérifie si des commandes actives existent
        $commandesActives = $annonce->commandes()
            ->whereNotIn('statut', ['annulee', 'livree', 'cloturee'])
            ->count();

        if ($commandesActives > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer : {$commandesActives} commande(s) active(s) liée(s) à cette annonce.",
            ], 422);
        }

        // Supprime les images du storage
        foreach ($annonce->images as $img) {
            \Storage::disk('public')->delete($img->image_url);
            $img->delete();
        }

        $annonce->delete();

        return response()->json([
            'success' => true,
            'message' => 'Annonce supprimée.',
        ]);
    }
}
