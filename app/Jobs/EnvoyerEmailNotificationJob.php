<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnvoyerEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [30, 120, 300]; // Plus long pour email (limits SMTP)
    public int   $timeout = 60;

    public function __construct(
        public readonly User         $user,
        public readonly Notification $notification,
        //public readonly ?string      $commentaire = null
    ) {}

    public function handle(): void
    {
        Mail::send(
            'emails.notification',
            [
                'user'         => $this->user,
                'contenu'      => $this->notification->contenu,
                'type'         => $this->notification->type,
                'reference'    => $this->notification->reference_type,
                'reference_id' => $this->notification->reference_id,
                'metadata'     => $this->notification->metadata,
                'commentaire'  => $this->notification->metadata['commentaire'] ?? null,
            ],
            function ($message) {
                $message->to($this->user->email, $this->user->nom ?? $this->user->email)
                        ->subject($this->sujetDepuisType($this->notification->type));
                // Anti-spam : désinscription + expéditeur explicite
                $message->getHeaders()->addTextHeader(
                    'List-Unsubscribe',
                    '<https://docspace.bj/notifications>'
                );
            }
        );

        $this->notification->update(['sent_at' => now()]);

        Log::info("[Email] Envoyé à [{$this->user->email}]");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[Email] Échec définitif pour [{$this->user->email}]: " . $e->getMessage());
    }

    private function sujetDepuisType(string $type): string
    {
        return match ($type) {
            'message'  => '[DocSpace] Vous avez un nouveau message',
            'commande' => '[DocSpace] Mise à jour de votre commande',
            'litige'   => '[DocSpace] Litige en cours',
            'systeme'  => '[DocSpace] Information importante',
            default    => '[DocSpace] Notification',
        };
    }
}
