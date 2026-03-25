<?php

namespace App\Http\Controllers;

use App\Events\LitigeOuvert;
use App\Models\Commande;
use App\Models\Litige;
use App\Services\LitigeService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class LitigeController extends Controller
{
    public function __construct(
        private LitigeService $litigeService,
        private NotificationService $notificationService,
    ) {}

    /**
     * GET /litiges
     * Litiges de l'utilisateur connecté (acheteur ou vendeur).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $litiges = Litige::whereHas('commande', function ($query) use ($user) {
                $query->where('acheteur_id', $user->id)
                      ->orWhere('vendeur_id', $user->id);
            })
            ->with([
                'commande.annonce:id,titre',
                'commande.acheteur:id,nom,email',
                'commande.vendeur:id,nom,email',
            ])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->latest('date_signalement')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $litiges,
        ]);
    }

    /**
     * GET /litiges/{litige}
     */
    public function show(Request $request, Litige $litige): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'user est impliqué dans la commande liée
        $commande = $litige->commande;
        if ($commande->acheteur_id !== $user->id && $commande->vendeur_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $litige->load([
                'commande.annonce:id,titre,prix_total',
                'commande.acheteur:id,nom,email',
                'commande.vendeur:id,nom,email',
            ]),
        ]);
    }

    /**
     * POST /litiges
     * Ouvre un litige sur une commande livrée.
     *
     * Body: {
     *   commande_id: int,
     *   motif: "non_conforme" | "defectueux" | "perdu",
     *   preuves?: string (description textuelle ou URL)
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commande_id' => 'required|exists:commandes,id',
            'motif'       => 'required|string',
            'preuves'     => 'nullable|string|max:2000',
        ]);

        $commande = Commande::with(['acheteur', 'vendeur', 'paiement'])
            ->findOrFail($validated['commande_id']);

        // Seul l'acheteur peut ouvrir un litige
        if ($commande->acheteur_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'acheteur peut ouvrir un litige.',
            ], 403);
        }

        // Litige possible uniquement sur une commande livrée
        if ($commande->statut !== 'livree') {
            return response()->json([
                'success' => false,
                'message' => 'Un litige ne peut être ouvert que sur une commande livrée.',
            ], 422);
        }

        // Vérifier qu'il n'existe pas déjà un litige ouvert sur cette commande
        $litigeExistant = Litige::where('commande_id', $commande->id)
            ->whereIn('statut', ['ouvert', 'en_cours'])
            ->exists();

        if ($litigeExistant) {
            return response()->json([
                'success' => false,
                'message' => 'Un litige est déjà en cours sur cette commande.',
            ], 409);
        }

        $litige = $this->litigeService->ouvrir(
            $commande,
            $request->user()->id,
            $validated['motif'],
            $validated['preuves'] ?? null
        );

        // Notifie l'acheteur (confirmation) et le vendeur (alerte litige)
        event(new LitigeOuvert($litige));

        return response()->json([
            'success' => true,
            'message' => 'Litige ouvert avec succès. Un administrateur va traiter votre demande.',
            'data'    => $litige,
        ], 201);
    }

    // ─── Routes admin ─────────────────────────────────────────

    /**
     * GET /admin/litiges
     * Tous les litiges (admin).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $litiges = Litige::with([
                'commande.annonce:id,titre',
                'commande.acheteur:id,nom,email',
                'commande.vendeur:id,nom,email',
            ])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->latest('date_signalement')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data'    => $litiges,
        ]);
    }

    /**
     * PATCH /admin/litiges/{litige}/statut
     * Passe un litige en "en_cours" (admin prend en charge).
     */
    public function prendreEnCharge(Litige $litige): JsonResponse
    {
        if (!in_array($litige->statut, ['ouvert', 'en_attente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce litige n\'est pas dans un état ouvert.',
            ], 422);
        }

        $litige->update(['statut' => 'en_cours']);

        // Informe l'acheteur que son litige est pris en charge
        $this->notificationService->envoyer(
            $litige->acheteur,
            'litige',
            "Votre litige #{$litige->id} est en cours de traitement par notre équipe.",
            [
                'canaux'         => ['push', 'email'],
                'reference_type' => 'litige',
                'reference_id'   => $litige->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Litige pris en charge.',
            'data'    => $litige->fresh(),
        ]);
    }

    /**
     * POST /admin/litiges/{litige}/resoudre
     * Résout le litige.
     *
     * Body: { decision: "rembourse" | "rejete" }
     */
    public function resoudre(Request $request, Litige $litige): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:rembourse,rejete',
        ]);

        if (!in_array($litige->statut, ['ouvert', 'en_cours'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce litige est déjà résolu.',
            ], 422);
        }

        $litige = $this->litigeService->resoudre($litige, $validated['decision']);

        $commande = $litige->commande;

        // Notifie l'acheteur de la décision finale
        $this->notificationService->notifierResolutionLitige(
            $commande->acheteur,
            $litige,
            $validated['decision']
        );

        // Notifie le vendeur de la décision finale
        $messageVendeur = $validated['decision'] === 'rembourse'
            ? "Le litige #{$litige->id} a été résolu : l'acheteur sera remboursé."
            : "Le litige #{$litige->id} a été clôturé en votre faveur. Le paiement sera libéré.";

        $this->notificationService->envoyer(
            $commande->vendeur,
            'litige',
            $messageVendeur,
            [
                'canaux'         => ['push', 'email'],
                'reference_type' => 'litige',
                'reference_id'   => $litige->id,
                'metadata'       => ['decision' => $validated['decision']],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Litige résolu avec succès.',
            'data'    => $litige,
        ]);
    }
}
