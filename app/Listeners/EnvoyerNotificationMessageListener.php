<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnvoyerNotificationMessageListener implements ShouldQueue
{
    public string $queue   = 'notifications';
    public int    $tries   = 3;
    public array  $backoff = [5, 15, 30];

    public function __construct(protected NotificationService $notifService) {}

    public function handle(MessageReceived $event): void
    {
        $this->notifService->notifierMessage(
            $event->message->recepteur,
            $event->message
        );
    }
}