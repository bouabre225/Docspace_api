<?php

use App\Http\Controllers\AnnonceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PaiementWebhookController;
use App\Http\Controllers\admin\adminController;
use App\Http\Controllers\AdminKycController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\LitigeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schedule;


Route::middleware('auth:sanctum')->prefix('kyc')->group(function () {
    Route::get('/documents', [KycController::class, 'index']);
    Route::post('/documents', [KycController::class, 'store']);
    Route::delete('/documents/{id}', [KycController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('commandes')->group(function () {
    Route::get('/', [CommandeController::class, 'index']);
    Route::post('/', [CommandeController::class, 'store']);
    Route::get('/{commande}', [CommandeController::class, 'show']);
    Route::post('/{commande}/cancel', [CommandeController::class, 'cancel']);
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
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{userId}', [MessageController::class, 'show']);
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

//mot de passe oublié
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset',  [AuthController::class, 'resetPassword']);

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
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [MeController::class, '__invoke']);
    Route::put('/me', [MeController::class, 'update']);
    Route::post('/me/fcm-token', function (Request $request) {
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['status' => 200]);
    });
});

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

//routes notification 
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    // Liste des notifications (avec filtres ?lu=false&type=commande)
    Route::get('/', [NotificationController::class, 'index']);

    // Badge : nombre de notifs non lues
    Route::get('/compteur', [NotificationController::class, 'compteur']);

    // Marquer une notif comme lue
    Route::patch('/{id}/lire', [NotificationController::class, 'marquerLue']);

    // Marquer toutes les notifs comme lues
    Route::patch('/lire-tout', [NotificationController::class, 'marquerToutesLues']);

    // Supprimer une notif
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

//Routes Litiges
// Routes acheteur / vendeur
Route::middleware('auth:sanctum')->prefix('litiges')->group(function () {
    Route::get('/', [LitigeController::class, 'index']);
    Route::get('/{litige}', [LitigeController::class, 'show']);
    Route::post('/', [LitigeController::class, 'store']);
});

// Routes admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/litiges')->group(function () {
    Route::get('/', [LitigeController::class, 'adminIndex']);
    Route::patch('/{litige}/prendre-en-charge', [LitigeController::class, 'prendreEnCharge']);
    Route::post('/{litige}/resoudre', [LitigeController::class, 'resoudre']);
});


//route de notif
// ─── Retry des notifications non envoyées ─────────────
// Toutes les 10 minutes, retenté les notifs avec sent_at NULL depuis > 5 min
Schedule::command('notifications:retry-failed')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// ─── Nettoyage des failed_jobs > 30 jours ─────────────
Schedule::command('queue:flush')
    ->monthly();

// ─── Nettoyage des notifications lues > 90 jours ──────
Schedule::call(function () {
    \App\Models\Notification::where('lu', true)
        ->where('created_at', '<', now()->subDays(90))
        ->delete();
})->weekly()->name('clean-old-notifications')->withoutOverlapping();


// Annonces vendeur : INTERDIT si KYC non validé
/*Route::middleware(['auth:sanctum', 'role:vendeur', 'kyc'])->group(function () {
    Route::post('/annonces', [AnnonceController::class, 'store']);
});*/
// Exemple routes protégées rôle (quand tu voudras)
// Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/dashboard', fn() => 'Admin OK');
