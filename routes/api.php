<?php

use Illuminate\Support\Facades\{Route, Broadcast, Schedule};
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AnnonceController, AnnonceImageController, NotificationController,
    MessageController, CommandeController, KycController,
    PaiementWebhookController, AuthController, GoogleAuthController,
    MeController, TwoFactorController, LitigeController, ContactController,
    AdminKycController
};
use App\Http\Controllers\admin\{adminController, UserAdminController};

/*
|--------------------------------------------------------------------------
| 1. ROUTES PUBLIQUES (Ouvertes à tous)
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => response()->json(['status' => 200, 'message' => 'API Docspace is running']));
Route::post('/contact', [ContactController::class, 'store']);

// Annonces (Consultation)
Route::prefix('annonces')->group(function () {
    Route::get('/', [AnnonceController::class, 'index']);
    Route::get('/search', [AnnonceController::class, 'search']);
    Route::get('/counts-categorie', [AnnonceController::class, 'countsParCategorie']);
    Route::get('/{annonce}', [AnnonceController::class, 'show']);
});

// Authentification & Inscription
Route::middleware('throttle:login')->group(function () {
    Route::post('/register/acheteur', [AuthController::class, 'registerBuyer']);
    Route::post('/register/vendeur', [AuthController::class, 'registerSeller']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
    Route::post('/login/2fa', [AuthController::class, 'login2fa']);
});

// Récupération de compte
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset',  [AuthController::class, 'resetPassword']);

// Google OAuth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// Webhooks (Paiements)
Route::post('/webhooks/fedapay', [PaiementWebhookController::class, 'handleWebhook'])->name('fedapay.webhook');


/*
|--------------------------------------------------------------------------
| 2. ROUTES PROTÉGÉES (Utilisateurs connectés)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // --- MON PROFIL & SÉCURITÉ ---
    Route::prefix('me')->group(function () {
        Route::get('/', [MeController::class, '__invoke']);
        Route::put('/', [MeController::class, 'update']);
        Route::post('/fcm-token', fn(Request $r) => $r->user()->update(['fcm_token' => $r->fcm_token]));
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('2fa')->group(function () {
        Route::post('/enable', [TwoFactorController::class, 'enable']);
        Route::post('/verify', [TwoFactorController::class, 'verify']);
        Route::post('/disable', [TwoFactorController::class, 'disable']);
    });

    // --- MESSAGERIE & NOTIFICATIONS ---
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/compteur', [NotificationController::class, 'compteur']);
        Route::patch('/{id}/lire', [NotificationController::class, 'marquerLue']);
        Route::patch('/lire-tout', [NotificationController::class, 'marquerToutesLues']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::get('/{userId}', [MessageController::class, 'show']);
    });

    // --- COMMANDES & LITIGES (Commun Acheteur/Vendeur) ---
    Route::prefix('commandes')->group(function () {
        Route::get('/', [CommandeController::class, 'index']);
        Route::post('/', [CommandeController::class, 'store']);
        Route::get('/recues', [CommandeController::class, 'recues']);
        Route::get('/{commande}', [CommandeController::class, 'show']);
        Route::patch('/{commande}/livrer', [CommandeController::class, 'marquerLivree']);
        Route::post('/{commande}/cancel', [CommandeController::class, 'cancel']);
        Route::post('/{commande}/pay', [PaiementWebhookController::class, 'pay']);
        Route::post('/{commande}/verify', [PaiementWebhookController::class, 'verify']);
    });

    Route::prefix('litiges')->group(function () {
        Route::get('/', [LitigeController::class, 'index']);
        Route::get('/{litige}', [LitigeController::class, 'show']);
        Route::post('/', [LitigeController::class, 'store']);
    });

    /* --- ESPACE VENDEUR --- */
    Route::middleware('role:vendeur')->group(function () {
        Route::prefix('annonces')->group(function () {
            Route::post('/', [AnnonceController::class, 'store']);
            Route::put('/{annonce}', [AnnonceController::class, 'update']);
            Route::delete('/{annonce}', [AnnonceController::class, 'destroy']);
            Route::post('/{annonce}/images', [AnnonceImageController::class, 'store']);
        });
        Route::delete('/annonces/images/{image}', [AnnonceImageController::class, 'destroy']);

        Route::prefix('kyc')->group(function () {
            Route::post('/submit', [KycController::class, 'submit']);
            Route::get('/status', [KycController::class, 'status']);
        });
    });

    /* --- ESPACE ADMINISTRATEUR --- */
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        
        // Utilisateurs
        Route::prefix('users')->group(function () {
            Route::get('/', [UserAdminController::class, 'index']);
            Route::get('/list-simple', [MeController::class, 'adminUsers']);
            Route::patch('/{id}/suspend', [UserAdminController::class, 'suspend']);
            Route::patch('/{id}/reactivate', [UserAdminController::class, 'reactivate']);
            Route::delete('/{id}', [UserAdminController::class, 'destroy']);
        });

        // KYC
        Route::prefix('kyc')->group(function () {
            Route::get('/pending', [AdminKycController::class, 'pending']);
            Route::post('/{id}/decide', [AdminKycController::class, 'decide']);
            Route::post('/documents/{document}/validate', [adminController::class, 'validateKyc']);
            Route::get('/document/{id}', function ($id) {
                $doc = \App\Models\KycDocument::findOrFail($id);
                return response()->file(storage_path('app/private/'.$doc->fichier));
            });
        });

        // Commandes, Litiges & Annonces
        Route::get('/commandes', [CommandeController::class, 'adminIndex']);
        Route::delete('/annonces/{annonce}', [AnnonceController::class, 'adminDestroy']);
        
        Route::prefix('litiges')->group(function () {
            Route::get('/', [LitigeController::class, 'adminIndex']);
            Route::patch('/{litige}/prendre-en-charge', [LitigeController::class, 'prendreEnCharge']);
            Route::post('/{litige}/resoudre', [LitigeController::class, 'resoudre']);
        });
    });

    Route::post('/broadcasting/auth', fn(Request $r) => Broadcast::auth($r));
});

/*
|--------------------------------------------------------------------------
| 3. TÂCHES PLANIFIÉES (CRON JOBS)
|--------------------------------------------------------------------------
*/
// Retry des notifications échouées
Schedule::command('notifications:retry-failed')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Nettoyage des jobs en échec (> 30 jours)
Schedule::command('queue:flush')->monthly();

// Nettoyage des notifications lues (> 90 jours)
Schedule::call(function () {
    \App\Models\Notification::where('lu', true)
        ->where('created_at', '<', now()->subDays(90))
        ->delete();
})->weekly()->name('clean-old-notifications')->withoutOverlapping();