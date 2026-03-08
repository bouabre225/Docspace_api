<?php

namespace App\Listeners;

use App\Events\CommandeStatusChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnvoyerNotificationCommandeListener implements ShouldQueue
{
    public string $queue   = 'notifications';
    public int    $tries   = 3;
    public array  $backoff = [5, 15, 30];

    public function __construct(protected NotificationService $notifService) {}

    public function handle(CommandeStatusChanged $event): void
    {
        $commande = $event->commande;
        $this->notifService->notifierStatutCommande($commande->acheteur, $commande);

        if ($event->ancienStatut === null) {
            $this->notifService->notifierNouvelleCommande($commande->vendeur, $commande);
        }
    }
}