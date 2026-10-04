<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Services\KycService;
use Illuminate\Http\Request;

class adminController extends Controller
{
    public function validateKyc(
        Request $request,
        KycDocument $document,
        KycService $kycService
    ){
        $request->validate([
            'decision' => 'required|in:valide,refuse',
            'commentaire' => 'nullable|string|max:500',
        ]);

        $kycService->validateDocument(
            $document,
            $request->user(),
            $request->decision,
            $request->ip(),
            $request->commentaire
        );

        return response()->json(['status' => true]);
    }
}
