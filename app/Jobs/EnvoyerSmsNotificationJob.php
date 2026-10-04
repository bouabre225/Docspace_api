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

class EnvoyerSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 2;       // SMS = cher, on limite les retries
    public array $backoff = [60, 300];
    public int   $timeout = 30;

    public function __construct(
        public readonly User         $user,
        public readonly Notification $notification,
    ) {}

    public function handle(): void
    {
        if (!$this->user->telephone) {
            Log::info("[SMS] Ignoré — pas de téléphone pour user [{$this->user->id}]");
            return;
        }

        $response = Http::withToken(config('services.fedapay.secret'))
            ->post('https://api.fedapay.com/v1/sms', [
                'to'      => $this->user->telephone,
                'message' => substr($this->notification->contenu, 0, 160),
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("[SMS] FedaPay error [{$response->status()}]: " . $response->body());
        }

        $this->notification->update(['sent_at' => now()]);

        Log::info("[SMS] Envoyé à [{$this->user->telephone}]");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[SMS] Échec définitif pour user [{$this->user->id}]: " . $e->getMessage());
    }
}
