<?php
namespace App\Providers;

use App\Events\CommandeStatusChanged;
use App\Events\LitigeOuvert;
use App\Events\MessageReceived;
use App\Listeners\EnvoyerNotificationCommandeListener;
use App\Listeners\EnvoyerNotificationLitigeListener;
use App\Listeners\EnvoyerNotificationMessageListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CommandeStatusChanged::class => [
            EnvoyerNotificationCommandeListener::class,
        ],
        MessageReceived::class => [
            EnvoyerNotificationMessageListener::class,
        ],
        LitigeOuvert::class => [
            EnvoyerNotificationLitigeListener::class,
        ],
    ];

    // ← Ajoute cette méthode pour désactiver l'auto-découverte
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function discoverEvents(): array
    {
        return [];
    }

    public function boot(): void
    {
        //
    }
}