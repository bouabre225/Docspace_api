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

        // Notifie toujours l'acheteur du statut
        $this->notifService->notifierStatutCommande($commande->acheteur, $commande);

        // Nouvelle commande créée → notifie le vendeur
        if ($event->ancienStatut === null) {
            $this->notifService->notifierNouvelleCommande($commande->vendeur, $commande);
        }

        // ✅ Commande payée → notifie aussi le vendeur
        if ($commande->statut === 'payee') {
            $this->notifService->envoyer(
                $commande->vendeur,
                'commande',
                "La commande #{$commande->id} a été payée. Préparez l'expédition !",
                [
                    'canaux'         => ['push', 'email'],
                    'reference_type' => 'commande',
                    'reference_id'   => $commande->id,
                    'metadata'       => ['statut' => 'payee', 'montant' => $commande->montant],
                ]
            );
        }

        if ($commande->statut === 'livree') {
            $this->notifService->envoyer($commande->vendeur, 'commande',
                "La commande #{$commande->id} a été confirmée livrée par l'admin.",
                ['canaux' => ['push', 'email'], 'reference_type' => 'commande', 'reference_id' => $commande->id]
            );
        }
    }
}