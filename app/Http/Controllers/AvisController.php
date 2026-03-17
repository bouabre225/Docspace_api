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
        $avis = $annonce->avis()->with('vendeur')->latest('created_at')->get();
        return response()->json($avis);
    }

    // Supprimer un avis
    public function destroy(Avis $avis)
    {
        // Vérifier que l'utilisateur est bien celui qui a posté l'avis via la commande
        if ($avis->commande && $avis->commande->acheteur_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $avis->delete();

        return response()->json(['message' => 'Avis supprimé']);
    }
}