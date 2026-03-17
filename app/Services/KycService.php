<?php

namespace App\Services;

use App\Models\KycAudit;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KycService
{
    public function submitDocument(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $path = $data['fichier']->store('documents/kyc/' . $user->id, 'private');
            
            return KycDocument::create([
                'user_id' => $user->id,
                'type_documents' => $data['type_document'],
                'fichier' => $path,
                'statut' => 'en_attente',
            ]);
        });
    }

    public function validateDocument(
        KycDocument $document,
        User $admin,
        string $decision,
        ?string $commentaire,
        string $ip_address
    ) {
        if (!in_array($decision, ['valide', 'refuse'])) {
            throw new \InvalidArgumentException('Décision invalide');
        }

        return DB::transaction(function () use ($document, $admin, $decision, $commentaire, $ip_address) {
            $ancienStatut = $document->statut;
            
            $document->update([
                'statut' => $decision,
                'date_validation' => now(),
            ]);

            if ($decision === 'valide') {
                $document->user->update(['verifie_kyc' => true]);
            }

            KycAudit::create([
                'user_id' => $document->user_id,
                'admin_id' => $admin->id,
                'document_id' => $document->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $decision,
                'commentaire' => $commentaire,
                'ip_address' => $ip_address,
            ]);

            return $document->fresh();
        });
    }
}
