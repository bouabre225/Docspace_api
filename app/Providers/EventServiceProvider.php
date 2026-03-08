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

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
