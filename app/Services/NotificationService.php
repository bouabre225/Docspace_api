<?php

namespace App\Services;

use App\Jobs\EnvoyerEmailNotificationJob;
use App\Jobs\EnvoyerPushNotificationJob;
use App\Jobs\EnvoyerSmsNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Point d'entrée principal.
     * 1. Persiste la notification en BDD (synchrone, immédiat)
     * 2. Dispatch un job async par canal → n'attend pas FCM/SMTP/SMS
     */
    public function envoyer(User $user, string $type, string $contenu, array $options = []): void
    {
        $canaux        = $options['canaux'] ?? ['push'];
        $referenceType = $options['reference_type'] ?? null;
        $referenceId   = $options['reference_id'] ?? null;
        $metadata      = $options['metadata'] ?? [];

        foreach ($canaux as $canal) {
            try {
                // 1. Persistance immédiate → visible dans GET /notifications dès maintenant
                $notification = Notification::create([
                    'user_id'        => $user->id,
                    'type'           => $type,
                    'canal'          => $canal,
                    'reference_type' => $referenceType,
                    'reference_id'   => $referenceId,
                    'contenu'        => $contenu,
                    'metadata'       => $metadata,
                    'lu'             => false,
                    'sent_at'        => null, // mis à jour par le job après envoi réel
                ]);

                // 2. Dispatch async → le worker gère l'envoi en arrière-plan
                match ($canal) {
                    'push'  => EnvoyerPushNotificationJob::dispatch($user, $notification)
                                    ->onQueue('notifications'),
                    'email' => EnvoyerEmailNotificationJob::dispatch($user, $notification)
                                    ->onQueue('notifications'),
                    'sms'   => EnvoyerSmsNotificationJob::dispatch($user, $notification)
                                    ->onQueue('notifications'),
                    default => Log::warning("[NotificationService] Canal inconnu: {$canal}"),
                };

            } catch (\Throwable $e) {
                Log::error("[NotificationService] Erreur canal [{$canal}] user [{$user->id}]: " . $e->getMessage());
                //dd($e->getMessage()); // ← ajoute ça temporairement
            }
        }
    }

    // ─── Raccourcis métier ────────────────────────────────────

    public function notifierStatutCommande(User $acheteur, \App\Models\Commande $commande): void
    {
        $messages = [
            'en_attente' => "Votre commande #{$commande->id} a été créée et est en attente de paiement.",
            'expediee'   => "Votre commande #{$commande->id} a été expédiée par le vendeur.",
            'livree'     => "Votre commande #{$commande->id} a été marquée comme livrée.",
            'cloturee'   => "Votre commande #{$commande->id} est clôturée. Merci pour votre achat !",
            'annulee'    => "Votre commande #{$commande->id} a été annulée.",
            'litige'     => "Un litige a été ouvert sur votre commande #{$commande->id}.",
        ];

        $contenu = $messages[$commande->statut] ?? "Votre commande #{$commande->id} a été mise à jour.";

        $this->envoyer($acheteur, 'commande', $contenu, [
            'canaux'         => ['push', 'email'],
            'reference_type' => 'commande',
            'reference_id'   => $commande->id,
            'metadata'       => ['statut' => $commande->statut, 'montant' => $commande->montant],
        ]);
    }

    public function notifierNouvelleCommande(User $vendeur, \App\Models\Commande $commande): void
    {
        $this->envoyer($vendeur, 'commande', "Nouvelle commande #{$commande->id} reçue sur votre annonce.", [
            'canaux'         => ['push', 'email'],
            'reference_type' => 'commande',
            'reference_id'   => $commande->id,
            'metadata'       => ['montant' => $commande->montant],
        ]);
    }

    public function notifierMessage(User $destinataire, \App\Models\Message $message): void
    {
        $this->envoyer($destinataire, 'message', "Vous avez reçu un nouveau message.", [
            'canaux'         => ['push'],
            'reference_type' => 'message',
            'reference_id'   => $message->id,
            'metadata'       => ['expediteur_id' => $message->expediteur_id],
        ]);
    }

    public function notifierLitige(User $vendeur, User $acheteur, \App\Models\Litige $litige): void
    {
        $this->envoyer($acheteur, 'litige', "Votre litige #{$litige->id} a été enregistré. Un admin va traiter votre demande.", [
            'canaux'         => ['push', 'email'],
            'reference_type' => 'litige',
            'reference_id'   => $litige->id,
        ]);

        $this->envoyer($vendeur, 'litige', "Un litige a été ouvert sur votre commande #{$litige->commande_id}.", [
            'canaux'         => ['push', 'email'],
            'reference_type' => 'litige',
            'reference_id'   => $litige->id,
            'metadata'       => ['motif' => $litige->motif],
        ]);
    }

    public function notifierResultatKyc(User $vendeur, string $statut, ?string $commentaire = null): void
    {
        $contenu = $statut === 'valide'
            ? "Votre vérification d'identité a été approuvée. Vous pouvez maintenant publier des annonces."
            : "Votre vérification d'identité a été refusée." . ($commentaire ? " Raison : {$commentaire}" : '');

        $this->envoyer($vendeur, 'systeme', $contenu, [
            'canaux'         => ['push', 'email'],
            'reference_type' => 'kyc',
            'metadata'       => ['statut' => $statut],
        ]);
    }

    public function notifierResolutionLitige(User $user, \App\Models\Litige $litige, string $decision): void
    {
        $contenu = $decision === 'rembourse'
            ? "Le litige #{$litige->id} a été résolu en votre faveur. Un remboursement va être initié."
            : "Le litige #{$litige->id} a été clôturé. Décision : {$decision}.";

        $this->envoyer($user, 'litige', $contenu, [
            'canaux'         => ['push', 'email', 'sms'],
            'reference_type' => 'litige',
            'reference_id'   => $litige->id,
            'metadata'       => ['decision' => $decision],
        ]);
    }
}