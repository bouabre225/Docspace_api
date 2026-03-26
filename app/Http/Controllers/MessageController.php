<?php

namespace App\Http\Controllers;

use App\Events\MessageReceived;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    // Liste des conversations
   public function index(): JsonResponse
    {
        $userId = (string) Auth::id();

     $conversations = DB::select("
        SELECT
            u.id::text,
            u.nom as name,
            u.email,
            u.avatar,
            MAX(COALESCE(m.created_at, '1970-01-01')) as dernier_message,
            SUM(CASE WHEN m.lu = false AND m.recepteur_id::text = ? THEN 1 ELSE 0 END) as non_lus
        FROM messages m
        JOIN users u ON u.id::text = CASE
            WHEN m.expediteur_id::text = ? THEN m.recepteur_id::text
            ELSE m.expediteur_id::text
        END
        WHERE m.expediteur_id::text = ? OR m.recepteur_id::text = ?
        GROUP BY u.id, u.nom, u.email, u.avatar
        ORDER BY dernier_message DESC
    ", [$userId, $userId, $userId, $userId]);

        return response()->json($conversations);
    }

    // Conversation avec un utilisateur
    public function show(string $userId): JsonResponse
    {
        $currentUserId = (string) Auth::id();

        $messages = Message::where(function ($query) use ($currentUserId, $userId) {
                $query->where('expediteur_id', $currentUserId)
                      ->where('recepteur_id', $userId);
            })
            ->orWhere(function ($query) use ($currentUserId, $userId) {
                $query->where('expediteur_id', $userId)
                      ->where('recepteur_id', $currentUserId);
            })
            ->with(['expediteur', 'recepteur', 'annonce'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Marquer comme lus les messages reçus
        Message::where('expediteur_id', $userId)
            ->where('recepteur_id', $currentUserId)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json($messages);
    }

    // Envoyer un message
    public function store(Request $request): JsonResponse
    {
        Log::info('[MESSAGE] Receiving message request', ['user_id' => Auth::id()]);
        
        $validated = $request->validate([
            'recepteur_id' => 'required|exists:users,id',
            'annonce_id'   => 'nullable|exists:annonces,id',
            'contenu'      => 'required|string|max:1000',
        ]);

        Log::info('[MESSAGE] Validation passed', $validated);

        $message = Message::create([
            'expediteur_id' => (string) Auth::id(),
            'recepteur_id'  => (string) $validated['recepteur_id'],
            'annonce_id'    => $validated['annonce_id'] ?? null,
            'contenu'       => $validated['contenu'],
            'created_at'    => now(),
        ]);

        Log::info('[MESSAGE] Message created', [
            'message_id' => $message->id,
            'recepteur_id' => $message->recepteur_id,
        ]);

        // Notifie le destinataire via push (temps réel)
        Log::info('[MESSAGE] Dispatching MessageReceived event');
        event(new MessageReceived($message->load('recepteur')));

        return response()->json([
            'message' => 'Message envoyé',
            'data'    => $message->load(['expediteur', 'recepteur', 'annonce']),
        ], 201);
    }
}