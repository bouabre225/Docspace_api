<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $broadcastImmediately = true;

    public function __construct(public readonly Message $message)
    {
        Log::info('[BROADCAST] Message event created', [
            'message_id' => $this->message->id,
            'expediteur_id' => $this->message->expediteur_id,
            'recepteur_id' => $this->message->recepteur_id,
            'channel' => 'conversation.' . $this->message->recepteur_id,
        ]);
    }

    public function broadcastOn(): array
    {
        $channel = new PrivateChannel('conversation.' . (string) $this->message->recepteur_id);
        Log::info('[BROADCAST] Broadcasting on channel', ['channel' => $channel]);
        return [$channel];
    }

    public function broadcastWith(): array
    {
        $data = [
            'id'            => (string) $this->message->id,
            'contenu'       => $this->message->contenu,
            'expediteur_id' => (string) $this->message->expediteur_id,
            'recepteur_id'  => (string) $this->message->recepteur_id,
            'created_at'    => $this->message->created_at,
            'lu'            => (bool) $this->message->lu,
            'annonce_id'    => $this->message->annonce_id,
        ];
        Log::info('[BROADCAST] Data sent', $data);
        return $data;
    }

    public function broadcastAs(): string
    {
        return 'nouveau.message';
    }
}