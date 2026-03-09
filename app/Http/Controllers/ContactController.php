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

        Mail::send('emails.contact', $validated, function ($mail) use ($validated) {
            $mail->to(config('mail.from.address'))
                 ->subject('[DocSpace Contact] ' . ($validated['sujet'] ?: $validated['categorie'] ?: 'Nouveau message'))
                 ->replyTo($validated['email'], $validated['nom']);
        });

        return response()->json(['message' => 'Message envoyé avec succès.'], 200);
    }
}