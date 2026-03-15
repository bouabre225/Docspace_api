<?php

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\AnnonceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AnnonceImageController extends Controller
{
    // Ajouter une image à une annonce existante
    public function store(Request $request, Annonce $annonce)
    {
        // Vérifier que c'est le propriétaire
        if ($annonce->vendeur_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'image' => 'required|image|max:2048' // 2MB max
        ]);

        // Upload de l'image
        $path = $request->file('image')->store('annonces', 'public');

        // Trouver l'ordre (dernier + 1)
        $ordre = $annonce->images()->max('ordre') + 1;

        // Créer l'enregistrement
        $image = AnnonceImage::create([
            'annonce_id' => $annonce->id,
            'image_url' => $path,
            'ordre' => $ordre
        ]);

        return response()->json([
            'message' => 'Image ajoutée',
            'image' => $image
        ], 201);
    }

    // Supprimer une image
    public function destroy(AnnonceImage $image)
    {
        // Vérifier que c'est le propriétaire de l'annonce
        if ($image->annonce->vendeur_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        // Supprimer le fichier du disque
        if (Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }

        // Supprimer l'enregistrement
        $image->delete();

        return response()->json(['message' => 'Image supprimée']);
    }

    // Réorganiser l'ordre des images
    public function reorder(Request $request, Annonce $annonce)
    {
        // Vérifier que c'est le propriétaire
        if ($annonce->vendeur_id !== Auth::id()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:annonce_images,id',
            'images.*.ordre' => 'required|integer'
        ]);

        foreach ($validated['images'] as $imageData) {
            AnnonceImage::where('id', $imageData['id'])
                ->update(['ordre' => $imageData['ordre']]);
        }

        return response()->json(['message' => 'Ordre des images mis à jour']);
    }
}