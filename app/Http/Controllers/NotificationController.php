<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notifService) {}

    /**
     * GET /notifications
     * Retourne toutes les notifications de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        // Filtre optionnel : ?lu=false
        if ($request->has('lu')) {
            $query->where('lu', filter_var($request->lu, FILTER_VALIDATE_BOOLEAN));
        }

        // Filtre optionnel : ?type=commande
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20);

        return response()->json([
            'status' => 200,
            'data'   => $notifications,
            'non_lues' => Notification::where('user_id', $request->user()->id)
                            ->where('lu', false)->count(),
        ]);
    }

    /**
     * PATCH /notifications/{id}/lire
     * Marque une notification comme lue.
     */
    public function marquerLue(Request $request, string $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->marquerLue();

        return response()->json(['status' => 200, 'message' => 'Notification marquée comme lue.']);
    }

    /**
     * PATCH /notifications/lire-tout
     * Marque toutes les notifications comme lues.
     */
    public function marquerToutesLues(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json(['status' => 200, 'message' => 'Toutes les notifications ont été marquées comme lues.']);
    }

    /**
     * DELETE /notifications/{id}
     * Supprime une notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['status' => 200, 'message' => 'Notification supprimée.']);
    }

    /**
     * GET /notifications/compteur
     * Retourne uniquement le nombre de notifications non lues (pour badge UI).
     */
    public function compteur(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('lu', false)
            ->count();

        return response()->json(['status' => 200, 'non_lues' => $count]);
    }
}
