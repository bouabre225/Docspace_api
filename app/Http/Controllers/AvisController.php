<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Annonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvisController extends Controller
{
    // Afficher les avis d'une annonce
    public function index(Annonce $annonce)
    {
        $avis = $annonce->avis()->with('commande.acheteur:id,nom')->latest('avis.created_at')->paginate(10);
        return response()->json(['success' => true, 'data' => $avis]);
    }

    // Noter une commande (acheteur uniquement, 1 seul avis)
    public function store(Request $request, \App\Models\Commande $commande)
    {
        if ($commande->acheteur_id !== Auth::id()) {
            return response()->json(['message' => 'Seul l\'acheteur peut noter cette commande.'], 403);
        }

        if (!in_array($commande->statut, ['livree', 'cloturee'])) {
            return response()->json(['message' => 'Vous pourrez noter après livraison.'], 422);
        }

        $validated = $request->validate([
            'note_vendeur' => 'required|integer|min:1|max:5',
            'note_conformite' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        if (Avis::where('commande_id', $commande->id)->exists()) {
            return response()->json(['message' => 'Cette commande a déjà été notée.'], 409);
        }

        $avis = Avis::create([
            'commande_id' => $commande->id,
            'vendeur_id' => $commande->vendeur_id,
            'note_vendeur' => $validated['note_vendeur'],
            'note_conformite' => $validated['note_conformite'],
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        $this->recalculerNoteVendeur($commande->vendeur_id);

        return response()->json(['success' => true, 'message' => 'Merci pour votre avis !', 'data' => $avis], 201);
    }

    // Supprimer un avis
    public function destroy(Avis $avis)
    {
        $user = Auth::user();
        $isOwner = $avis->commande && $avis->commande->acheteur_id === $user->id;

        if (!$isOwner && $user->role !== 'admin') {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $vendeurId = $avis->vendeur_id;
        $avis->delete();
        $this->recalculerNoteVendeur($vendeurId);

        return response()->json(['message' => 'Avis supprimé']);
    }

    private function recalculerNoteVendeur(string $vendeurId): void
    {
        $moyenne = Avis::where('vendeur_id', $vendeurId)
            ->selectRaw('coalesce(avg((note_vendeur + note_conformite) / 2.0), 0) as note')
            ->value('note');

        $vendeur = \App\Models\User::find($vendeurId);
        if ($vendeur) {
            $vendeur->note_moyenne = round((float) $moyenne, 1);
            $vendeur->save();
        }
    }
}