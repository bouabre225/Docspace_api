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
        KycDocument $kycDocument,
        KycService $kycService
    ){
        $request->validate([
            'decision' => 'required|in:valide, refuse',
            'commentaire' => 'required|string',
        ]);

        $kycService->validateDocument(
            $kycDocument,
            $request->user(),
            $request->decision,
            $request->commentaire,
            $request->ip()
        );

        return response()->json(['status' => true]);
    }
}
