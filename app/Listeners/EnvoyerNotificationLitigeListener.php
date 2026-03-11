<?php

namespace App\Listeners;

use App\Events\LitigeOuvert;
use App\Services\NotificationService;
//use Illuminate\Contracts\Queue\ShouldQueue;

class EnvoyerNotificationLitigeListener 
{
    //public string $queue   = 'notifications';
    //public int    $tries   = 3;
    //public array  $backoff = [5, 15, 30];

    public function __construct(protected NotificationService $notifService) {}

    public function handle($event): void
    {
        if (!$event instanceof LitigeOuvert) return;

        $litige = $event->litige;
        $this->notifService->notifierLitige(
            $litige->commande->vendeur,
            $litige->acheteur,
            $litige
        );
    }
}