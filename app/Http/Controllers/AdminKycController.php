<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\KycDecisionRequest;
use App\Services\KycService;
use App\Services\NotificationService;
use App\Models\KycDocument;

class AdminKycController
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function pending(): JsonResponse
    {
        $docs = KycDocument::with('user')
            ->where('statut', 'en_attente')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['kyc_pending' => $docs], 200);
    }

    public function decide(string $id, KycDecisionRequest $request, KycService $service): JsonResponse
    {
        $doc = KycDocument::with('user')->find($id);

        if (!$doc) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        $decision    = $request->validated()['decision'];

        $updated = $service->validateDocument(  // ← méthode correcte dans KycService
            $doc,
            $request->user(),
            $decision,
            $request->ip(),
        );

        $this->notificationService->notifierResultatKyc(
            $updated->user,
            $decision,
        );

        return response()->json([
            'message' => $decision === 'valide' ? 'KYC validé.' : 'KYC refusé.',
            'kyc'     => $updated,
            'user'    => $updated->user,
        ], 200);
    }
}