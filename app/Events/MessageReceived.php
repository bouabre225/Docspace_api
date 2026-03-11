<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message) {}

    public function broadcastOn(): array
    {
        // Canal privé par conversation — chaque user écoute son propre canal
        return [
            new PrivateChannel('conversation.' . $this->message->recepteur_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->message->id,
            'contenu'       => $this->message->contenu,
            'expediteur_id' => $this->message->expediteur_id,
            'recepteur_id'  => $this->message->recepteur_id,
            'created_at'    => $this->message->created_at,
        ];
    }

    public function broadcastAs(): string
    {
        return 'nouveau.message';
    }
}