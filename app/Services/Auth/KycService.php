<?php

namespace App\Services\Auth;

class KycService
{
    /**
     * Vendeur soumet / remplace son document (en_attente)
     */
    public function submit(User $user, array $data): KycDocument
    {
        // Si tu veux garder l'historique, fais create() à chaque fois.
        // Ici on fait updateOrCreate pour n’avoir qu’un doc “actif”.
        return KycDocument::updateOrCreate(
            ['user_id' => $user->id],
            [
                'type_document' => $data['type_document'],
                'fichier' => $data['fichier'],
                'statut' => 'en_attente',
                'date_validation' => null,
            ]
        );
    }

    /**
     * Admin valide/refuse KYC
     */
    public function decide(KycDocument $doc, string $decision): KycDocument
    {
        $doc->statut = $decision;
        $doc->date_validation = now();
        $doc->save();

        // Met à jour le user
        $user = $doc->user;
        $user->verifie_kyc = ($decision === 'valide');
        $user->badge_verifie = ($decision === 'valide');
        $user->save();

        return $doc;
    }
}