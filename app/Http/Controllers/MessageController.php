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

        $messages = Message::with(['expediteur:id,nom,email,avatar', 'recepteur:id,nom,email,avatar', 'annonce:id,titre'])
            ->where('expediteur_id', $userId)
            ->orWhere('recepteur_id', $userId)
            ->latest('created_at')
            ->limit(200)
            ->get();

        $conversations = [];
        foreach ($messages as $m) {
            $isSender = (string) $m->expediteur_id === $userId;
            $other = $isSender ? $m->recepteur : $m->expediteur;
            if (!$other) continue;
            $oid = (string) $other->id;
            if (!isset($conversations[$oid])) {
                $conversations[$oid] = [
                    'id' => $oid,
                    'name' => $other->nom,
                    'email' => $other->email,
                    'avatar' => $other->avatar,
                    'dernier_message' => $m->created_at,
                    'dernier_contenu' => $m->contenu,
                    'annonce' => $m->annonce,
                    'non_lus' => 0,
                ];
            }
            if (!$isSender && !$m->lu) {
                $conversations[$oid]['non_lus']++;
            }
        }

        return response()->json(array_values($conversations));
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
        $validated = $request->validate([
            'recepteur_id' => 'required|exists:users,id',
            'annonce_id'   => 'nullable|exists:annonces,id',
            'contenu'      => 'required|string|max:1000',
        ]);

        if ((string) $validated['recepteur_id'] === (string) Auth::id()) {
            return response()->json(['message' => 'Impossible de s\'envoyer un message à soi-même.'], 422);
        }

        $message = Message::create([
            'expediteur_id' => (string) Auth::id(),
            'recepteur_id'  => (string) $validated['recepteur_id'],
            'annonce_id'    => $validated['annonce_id'] ?? null,
            'contenu'       => $validated['contenu'],
        ]);

        Log::info('[MESSAGE] Message created', [
            'message_id' => $message->id,
        ]);
        event(new MessageReceived($message->load('recepteur')));

        return response()->json([
            'message' => 'Message envoyé',
            'data'    => $message->load(['expediteur', 'recepteur', 'annonce']),
        ], 201);
    }
}