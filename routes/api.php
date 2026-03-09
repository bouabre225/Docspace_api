<?php

use App\Http\Controllers\AnnonceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\commandeController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PaiementWebhookController;
use App\Http\Controllers\admin\adminController;
use App\Http\Controllers\AdminKycController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('kyc')->group(function () {
    Route::get('/documents', [KycController::class, 'index']);
    Route::post('/documents', [KycController::class, 'store']);
    Route::delete('/documents/{id}', [KycController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('commandes')->group(function () {
    Route::get('/', [commandeController::class, 'index']);
    Route::post('/', [commandeController::class, 'store']);
    Route::get('/{commande}', [commandeController::class, 'show']);
    Route::post('/{commande}/cancel', [commandeController::class, 'cancel']);
    Route::post('/{commande}/pay', [PaiementWebhookController::class, 'pay']);
});

Route::post('/webhooks/fedapay', [PaiementWebhookController::class, 'handleWebhook'])
    ->name('fedapay.webhook');

Route::get('/annonces', [AnnonceController::class, 'index']);
Route::get('/annonces/search', [AnnonceController::class, 'search']);
Route::get('/annonces/{annonce}', [AnnonceController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/annonces', [AnnonceController::class, 'store']);
    Route::put('/annonces/{annonce}', [AnnonceController::class, 'update']);
    Route::delete('/annonces/{annonce}', [AnnonceController::class, 'destroy']);
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/{userId}', [MessageController::class, 'show']);
    Route::post('/messages', [MessageController::class, 'store']);
});

Route::get('/', function () {
    return response()->json(['status'=> 200, 'message' => 'API is running']);
});

// Auth
Route::middleware('throttle:login')->group(function () {
    Route::post('/register/acheteur', [AuthController::class, 'registerBuyer']);
    Route::post('/register/vendeur', [AuthController::class, 'registerSeller']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AuthController::class, 'loginAdmin']);

    // finalisation si 2FA requis (user ou admin)
    Route::post('/login/2fa', [AuthController::class, 'login2fa']);
});



// Google OAuth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// 2FA (activation/désactivation) -> protégé
Route::middleware('auth:sanctum')->prefix('2fa')->group(function () {
    Route::post('/enable', [TwoFactorController::class, 'enable']);
    Route::post('/verify', [TwoFactorController::class, 'verify']);   // confirmation activation
    Route::post('/disable', [TwoFactorController::class, 'disable']);
});

// Logout protégé
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

//Route /me
Route::middleware('auth:sanctum')->get('/me', [MeController::class, '__invoke']);

// KYC vendeur (accessible même si verifie_kyc=false)
Route::middleware(['auth:sanctum', 'role:vendeur'])->prefix('kyc')->group(function () {
    Route::post('/submit', [KycController::class, 'submit']);
    Route::get('/status', [KycController::class, 'status']);
});

// KYC admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/kyc')->group(function () {
    Route::get('/pending', [AdminKycController::class, 'pending']);
    Route::post('/{id}/decide', [AdminKycController::class, 'decide']);
    Route::post('/documents/{document}/validate', [adminController::class, 'validateKyc']);

});

// Annonces vendeur : INTERDIT si KYC non validé
/*Route::middleware(['auth:sanctum', 'role:vendeur', 'kyc'])->group(function () {
    Route::post('/annonces', [AnnonceController::class, 'store']);
});*/
// Exemple routes protégées rôle (quand tu voudras)
// Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/dashboard', fn() => 'Admin OK');
