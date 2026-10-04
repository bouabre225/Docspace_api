<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email',
            'sujet'     => 'nullable|string|max:200',
            'categorie' => 'nullable|string|max:100',
            'message'   => 'required|string|max:1000',
        ]);

        $sujet = '[DocSpace Contact] ' . ($validated['sujet'] ?? $validated['categorie'] ?? 'Nouveau message');

        $data = $validated;
        $data['contenu'] = $data['message'];  // renomme pour éviter le conflit avec $message de Laravel
        unset($data['message']);

        Mail::queue('emails.contact', $data, function ($mail) use ($validated, $sujet) {
            $mail->to(config('mail.from.address'))
                 ->subject($sujet)
                 ->replyTo($validated['email'], $validated['nom']);
        });

        return response()->json(['message' => 'Message envoyé avec succès.'], 200);
    }
}