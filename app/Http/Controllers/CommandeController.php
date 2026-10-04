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
            'adresse_livraison'   => 'required|string|max:255',
            'telephone_livraison' => 'required|string|max:20',
        ]);

        $annonce = \App\Models\Annonce::findOrFail($validated['annonce_id']);

        //Empêche un vendeur de commander son propre article
        if ($annonce->vendeur_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas commander votre propre annonce.',
            ], 403);
        }

        $commande = $this->commandeService->createOrder(
            $request->user(),
            $validated['annonce_id'],
            $validated['quantite'],
            [
                'adresse_livraison'   => $validated['adresse_livraison'],
                'telephone_livraison' => $validated['telephone_livraison'],
            ]
        );
        //\Log::info('Commande créée', ['commande' => $commande]);

        // Notifie l'acheteur (confirmation de commande) et le vendeur (nouvelle commande)
        event(new CommandeStatusChanged($commande, null));

        //\Log::info('Commande créée', ['commande' => $commande]);

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($commande) {
            $commande->update(['statut' => 'annulee']);
            $commande->annonce?->increment('quantite', $commande->quantite);
        });

        event(new \App\Events\CommandeStatusChanged($commande->fresh(), 'annulee'));

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

    public function statsVendeur(Request $request): JsonResponse
    {
        $vendeurId = $request->user()->id;
        $request->validate(['periode' => 'nullable|in:7j,30j,90j,365j,tout']);
        $jours = match ($request->input('periode', '30j')) {
            '7j' => 7, '90j' => 90, '365j' => 365, 'tout' => null, default => 30,
        };
        $debut = $jours ? \Carbon\Carbon::now()->subDays($jours)->startOfDay() : null;
        $payes = ['payee', 'livree', 'cloturee'];

        $base = \App\Models\Commande::where('vendeur_id', $vendeurId)
            ->when($debut, fn($q) => $q->where('created_at', '>=', $debut));
        $payeesQ = (clone $base)->whereIn('statut', $payes);
        $ca = (float) (clone $payeesQ)->sum('montant');
        $nbPayees = (clone $payeesQ)->count();

        $parStatut = (clone $base)->selectRaw('statut, count(*) as total')->groupBy('statut')->get();
        $serie = (clone $base)
            ->selectRaw("created_at::date as date, count(*) as commandes, coalesce(sum(case when statut in ('payee','livree','cloturee') then montant else 0 end),0) as ca")
            ->groupByRaw('created_at::date')->orderBy('date')->get();

        $parAnnonce = \App\Models\Commande::where('commandes.vendeur_id', $vendeurId)
            ->join('annonces as a', 'a.id', '=', 'commandes.annonce_id')
            ->selectRaw("a.id, a.titre, a.quantite as stock, a.statut as annonce_statut, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupBy('a.id', 'a.titre', 'a.quantite', 'a.statut')
            ->orderByDesc('ca')->get();

        $annonces = \App\Models\Annonce::where('vendeur_id', $vendeurId);
        $recurrents = (clone $base)->selectRaw('acheteur_id, count(*) as commandes, coalesce(sum(case when statut in (\'payee\',\'livree\',\'cloturee\') then montant else 0 end),0) as total')
            ->groupBy('acheteur_id')->havingRaw('count(*) >= 2')->orderByDesc('total')->limit(10)->get()
            ->loadMissing('acheteur:id,nom,email');

        $litiges = \App\Models\Litige::whereHas('commande', fn($q) => $q->where('vendeur_id', $vendeurId))
            ->when($debut, fn($q) => $q->where('date_signalement', '>=', $debut))
            ->with('commande:id')->latest('date_signalement')->limit(10)->get();
        $avis = \App\Models\Avis::where('vendeur_id', $vendeurId)->with('commande:id')->latest()->limit(10)->get();
        $noteMoy = \App\Models\Avis::where('vendeur_id', $vendeurId)->selectRaw('round(avg((note_vendeur+note_conformite)/2.0),2) as note, count(*) as total')->first();

        return response()->json([
            'periode' => $request->input('periode', '30j'),
            'kpis' => [
                'ca' => round($ca, 2),
                'net' => round($ca / 1.08, 2),
                'commandes' => (clone $base)->count(),
                'commandes_payees' => $nbPayees,
                'a_expedier' => (clone $base)->where('statut', 'payee')->count(),
                'annonces_actives' => (clone $annonces)->where('statut', 'active')->count(),
                'ruptures' => (clone $annonces)->where('quantite', '<=', 0)->count(),
                'stock_bas' => (clone $annonces)->where('statut', 'active')->where('quantite', '>', 0)->where('quantite', '<=', 2)->count(),
                'note_moyenne' => (float) ($noteMoy->note ?? 0),
                'avis_total' => (int) ($noteMoy->total ?? 0),
            ],
            'par_statut' => $parStatut,
            'serie' => $serie,
            'par_annonce' => $parAnnonce,
            'recurrents' => $recurrents,
            'litiges' => $litiges,
            'avis' => $avis,
        ]);
    }

    public function marquerLivree(Request $request, Commande $commande): JsonResponse
    {
        $user = $request->user();

        //Admin OU vendeur de la commande peuvent marquer livrée
        $isAdmin   = $user->role === 'admin';
        $isVendeur = $user->role === 'vendeur' && $commande->vendeur_id === $user->id;

        if (!$isAdmin && !$isVendeur) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        if ($commande->statut !== 'payee') {
            return response()->json([
                'success' => false,
                'message' => "Impossible : la commande doit être payée (statut actuel : {$commande->statut})"
            ], 400);
        }

        $commande->update(['statut' => 'livree']);
        $commande->load(['acheteur', 'vendeur', 'annonce']);

        event(new CommandeStatusChanged($commande, 'payee'));

        return response()->json([
            'success' => true,
            'message' => 'Commande marquée comme livrée',
            'data'    => $commande,
        ]);
    }
}
