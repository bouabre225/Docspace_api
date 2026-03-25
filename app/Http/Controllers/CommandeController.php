<?php

namespace App\Http\Controllers;

use App\Events\CommandeStatusChanged;
use App\Models\Commande;
use App\Services\CommandeService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CommandeController extends Controller
{
    public function __construct(
        private CommandeService $commandeService,
        private NotificationService $notificationService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'annonce_id' => 'required|string|exists:annonces,id',
            'quantite'   => 'required|integer|min:1',
        ]);

        $annonce = \App\Models\Annonce::findOrFail($validated['annonce_id']);

        // ✅ Empêche un vendeur de commander son propre article
        if ($annonce->vendeur_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas commander votre propre annonce.',
            ], 403);
        }

        $commande = $this->commandeService->createOrder(
            $request->user(),
            $validated['annonce_id'],
            $validated['quantite']
        );
        \Log::info('Commande créée', ['commande' => $commande]);

        // Notifie l'acheteur (confirmation de commande) et le vendeur (nouvelle commande)
        event(new CommandeStatusChanged($commande, null));

        \Log::info('Commande créée', ['commande' => $commande]);

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data'    => $commande->load(['annonce:id,titre,prix_total', 'vendeur:id,nom']),
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $commandes = Commande::query()
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

    public function show(Request $request, Commande $commande): JsonResponse
    {
        // Vérification manuelle : seuls acheteur et vendeur peuvent voir
        if ($commande->acheteur_id !== $request->user()->id && 
            $commande->vendeur_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }
        
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

    public function cancel(Request $request, Commande $commande): JsonResponse
    {
        // Seul l'acheteur peut annuler
        if ($commande->acheteur_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler cette commande'
            ], 400);
        }

        $commande->update(['statut' => 'annulee']);
        $commande->annonce->increment('quantite', $commande->quantite);

        event(new \App\Events\CommandeStatusChanged($commande, 'annulee'));

        return response()->json([
            'success' => true,
            'message' => 'Commande annulée avec succès'
        ]);
    }

    public function adminIndex(Request $request)
    {
        $commandes = Commande::with(['annonce', 'acheteur', 'vendeur'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $commandes,
        ]);
    }

    public function recues(Request $request)
    {
        $commandes = Commande::with(['annonce', 'acheteur'])
            ->where('vendeur_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $commandes]);
    }

    public function marquerLivree(Request $request, Commande $commande): JsonResponse
    {
        // Seul admin peut appeler cette route
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        if ($commande->statut !== 'payee') {
            return response()->json([
                'success' => false,
                'message' => "Impossible : la commande doit être payée (statut actuel : {$commande->statut})"
            ], 400);
        }

        $commande->update(['statut' => 'livree']);
        $commande->load(['acheteur', 'vendeur', 'annonce']);

        // ✅ Notifie acheteur + vendeur
        event(new CommandeStatusChanged($commande, 'payee'));

        return response()->json([
            'success' => true,
            'message' => 'Commande marquée comme livrée',
            'data'    => $commande,
        ]);
    }
}
