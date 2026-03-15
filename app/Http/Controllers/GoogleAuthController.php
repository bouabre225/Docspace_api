<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Auth\GoogleAuthService;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google for authentication
     */
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google callback
     * @param GoogleAuthService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function callback(GoogleAuthService $service)
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // JSON (ok pour debug). En prod tu peux redirect frontend avec token.
        return response()->json($service->handleGoogleUser((object) [
            'id' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
        ]));
    }
}
