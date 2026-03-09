<?php

namespace App\Http\Controllers;

use App\Models\Commandes;
use App\Services\commandeService;
use Illuminate\Http\Request;

class commandeController
{
    public function __construct(
        private commandeService $commandeService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'annonce_id' => 'required|exists:annonces,id',
            'quantite' => 'required|integer|min:1',
        ]);

        $commande = $this->commandeService->createOrder(
            $request->user(),
            $validated['annonce_id'],
            $validated['quantite']
        );

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => $commande->load(['annonce:id,titre,prix_total', 'vendeur:id,nom'])
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $commandes = Commandes::query()
            ->where(function ($query) use ($user) {
                $query->where('acheteur_id', $user->id)
                    ->orWhere('vendeur_id', $user->id);
            })
            ->with([
                'acheteur:id,nom,email',
                'vendeur:id,nom,email',
                'annonce:id,titre,prix_total',
                'paiement:id,commande_id,statut,montant',
            ])
            ->when($request->statut, function ($query, $statut) {
                $query->where('statut', $statut);
            })
            ->when($request->role, function ($query, $role) use ($user) {
                if ($role === 'acheteur') {
                    $query->where('acheteur_id', $user->id);
                } elseif ($role === 'vendeur') {
                    $query->where('vendeur_id', $user->id);
                }
            })
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $commandes
        ]);
    }

    public function show(Commandes $commande)
    {
        $this->authorize('view', $commande);

        return response()->json([
            'success' => true,
            'data' => $commande->load([
                'acheteur:id,nom,email',
                'vendeur:id,nom,email',
                'annonce:id,titre,prix_total',
                'paiement'
            ])
        ]);
    }

    public function cancel(Commandes $commande)
    {
        $this->authorize('view', $commande);

        if (!in_array($commande->statut, ['en_attente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler cette commande'
            ], 400);
        }

        $commande->update(['statut' => 'annulee']);
        $commande->annonce->increment('quantite', $commande->quantite);

        return response()->json([
            'success' => true,
            'message' => 'Commande annulée avec succès'
        ]);
    }
}
