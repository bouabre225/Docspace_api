<?php

namespace App\Listeners;

use App\Events\CommandeStatusChanged;
use App\Services\NotificationService;

class EnvoyerNotificationCommandeListener 
{
    public function __construct(protected NotificationService $notifService) {}

    // ← Pas de type hint sur $event pour éviter l'auto-découverte
    public function handle($event): void
    {
        if (!$event instanceof CommandeStatusChanged) return;

        $commande = $event->commande;
        $this->notifService->notifierStatutCommande($commande->acheteur, $commande);

        if ($event->ancienStatut === null) {
            $this->notifService->notifierNouvelleCommande($commande->vendeur, $commande);
        }
    }
}