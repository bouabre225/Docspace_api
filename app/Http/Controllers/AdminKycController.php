<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\KycDecisionRequest;
use App\Services\Auth\KycService;
use App\Models\KycDocument;

class AdminKycController
{
    /**
     * GET /admin/kyc/pending
     * Liste des docs en attente
     */
    public function pending()
    {
        $docs = KycDocument::with('user')
            ->where('statut', 'en_attente')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'kyc_pending' => $docs,
        ], 200);
    }

    /**
     * POST /admin/kyc/{id}/decide
     * Body: { decision: "valide" | "refuse" }
     */
    public function decide(int $id, KycDecisionRequest $request, KycService $service)
    {
        $doc = KycDocument::with('user')->find($id);

        if (!$doc) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        $decision = $request->validated()['decision'];

        $updated = $service->decide($doc, $decision);

        return response()->json([
            'message' => $decision === 'valide' ? 'KYC validé' : 'KYC refusé',
            'kyc' => $updated,
            'user' => $updated->user,
        ], 200);
    }
}
