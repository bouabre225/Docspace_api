<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    // Liste des conversations
    public function index()
    {
        $userId = Auth::id();

        $conversations = Message::select(
                'users.id', 
                'users.nom as name',
                'users.email',
                'users.avatar',
                DB::raw('MAX(messages.created_at) as dernier_message'),
                DB::raw("SUM(CASE WHEN messages.lu = false AND messages.recepteur_id = '{$userId}' THEN 1 ELSE 0 END) as non_lus")
            )
            ->join('users', function($join) use ($userId) {
                $join->on('users.id', '=', 'messages.expediteur_id')
                     ->orOn('users.id', '=', 'messages.recepteur_id');
            })
            ->where(function($query) use ($userId) {
                $query->where('messages.expediteur_id', $userId)
                      ->orWhere('messages.recepteur_id', $userId);
            })
            ->where('users.id', '!=', $userId)
            ->groupBy('users.id', 'users.nom', 'users.email', 'users.avatar')
            ->orderBy('dernier_message', 'desc')
            ->get();

        return response()->json($conversations);
    }

    // Conversation avec un utilisateur
    public function show($userId)
    {
        $currentUserId = Auth::id();

        $messages = Message::where(function($query) use ($currentUserId, $userId) {
                $query->where('expediteur_id', $currentUserId)
                      ->where('recepteur_id', $userId);
            })
            ->orWhere(function($query) use ($currentUserId, $userId) {
                $query->where('expediteur_id', $userId)
                      ->where('recepteur_id', $currentUserId);
            })
            ->with(['expediteur', 'recepteur', 'annonce'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Marquer comme lus
        Message::where('expediteur_id', $userId)
            ->where('recepteur_id', $currentUserId)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json($messages);
    }

    // Envoyer un message
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recepteur_id' => 'required|exists:users,id',
            'annonce_id' => 'nullable|exists:annonces,id',
            'contenu' => 'required|string|max:1000'
        ]);

        $message = Message::create([
            'expediteur_id' => Auth::id(),
            'recepteur_id' => $validated['recepteur_id'],
            'annonce_id' => $validated['annonce_id'] ?? null,
            'contenu' => $validated['contenu']
        ]);

        return response()->json([
            'message' => 'Message envoyé',
            'data' => $message->load(['expediteur', 'recepteur', 'annonce'])
        ], 201);
    }
}