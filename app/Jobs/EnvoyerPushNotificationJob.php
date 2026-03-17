<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnvoyerPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ─── Comportement en cas d'échec ─────────────────────────

    /** Nombre de tentatives max avant de mettre en failed_jobs */
    public int $tries = 3;

    /** Délai exponentiel entre les tentatives (en secondes) */
    public array $backoff = [10, 30, 60];

    /** Timeout max d'exécution du job (secondes) */
    public int $timeout = 30;

    public function __construct(
        public readonly User         $user,
        public readonly Notification $notification,
    ) {}

    public function handle(): void
    {
        $fcmToken = $this->user->fcm_token;

        if (!$fcmToken) {
            Log::info("[Push] Ignoré — pas de token FCM pour user [{$this->user->id}]");
            return; // Pas une erreur, on n'échoue pas le job
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . config('services.fcm.server_key'),
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => $fcmToken,
            'notification' => [
                'title' => $this->titreDepuisType($this->notification->type),
                'body'  => $this->notification->contenu,
            ],
            'data' => [
                'notification_id' => $this->notification->id,
                'type'            => $this->notification->type,
                'reference_type'  => $this->notification->reference_type,
                'reference_id'    => $this->notification->reference_id,
            ],
        ]);

        if (!$response->successful()) {
            // Lance une exception → Laravel retentera le job selon $backoff
            throw new \RuntimeException("[Push] FCM error [{$response->status()}]: " . $response->body());
        }

        // Marquer comme envoyé
        $this->notification->update(['sent_at' => now()]);

        Log::info("[Push] Envoyé avec succès pour user [{$this->user->id}]");
    }

    public function failed(\Throwable $e): void
    {
        // Appelé après épuisement des $tries — log l'échec définitif
        Log::error("[Push] Échec définitif pour user [{$this->user->id}]: " . $e->getMessage());
        // Ici tu peux alerter ton monitoring (ex: Sentry, Slack webhook, etc.)
    }

    private function titreDepuisType(string $type): string
    {
        return match ($type) {
            'message'  => 'Nouveau message',
            'commande' => 'Mise à jour commande',
            'litige'   => 'Litige',
            default    => 'DocSpace',
        };
    }
}
