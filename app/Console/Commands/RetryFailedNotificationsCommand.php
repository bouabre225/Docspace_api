<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class RetryFailedNotificationsCommand extends Command
{
   protected $signature   = 'notifications:retry-failed {--dry-run : Afficher sans relancer}';
    protected $description = 'Retenté les notifications non envoyées (sent_at NULL depuis > 5 min)';

    public function handle(): int
    {
        $seuil = now()->subMinutes(5);

        $notifs = Notification::whereNull('sent_at')
            ->where('created_at', '<', $seuil)
            ->get();

        if ($notifs->isEmpty()) {
            $this->info('Aucune notification en échec.');
            return self::SUCCESS;
        }

        $this->warn("Notifications en échec trouvées : {$notifs->count()}");

        if ($this->option('dry-run')) {
            $this->table(['ID', 'User', 'Canal', 'Type', 'Créée le'], $notifs->map(fn($n) => [
                $n->id, $n->user_id, $n->canal, $n->type, $n->created_at,
            ]));
            return self::SUCCESS;
        }

        foreach ($notifs as $notif) {
            try {
                $user = $notif->user;

                $job = match ($notif->canal) {
                    'push'  => new \App\Jobs\EnvoyerPushNotificationJob($user, $notif),
                    'email' => new \App\Jobs\EnvoyerEmailNotificationJob($user, $notif),
                    'sms'   => new \App\Jobs\EnvoyerSmsNotificationJob($user, $notif),
                    default => null,
                };

                if ($job) {
                    dispatch($job)->onQueue('notifications');
                    $this->line("  → Re-dispatché notif #{$notif->id} [{$notif->canal}] pour user {$user->id}");
                }

            } catch (\Throwable $e) {
                Log::error("[RetryFailedNotifications] Erreur notif #{$notif->id}: " . $e->getMessage());
                $this->error("  ✗ Erreur notif #{$notif->id}: " . $e->getMessage());
            }
        }

        $this->info('Terminé.');
        return self::SUCCESS;
    }
}
