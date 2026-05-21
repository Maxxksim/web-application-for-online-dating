<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message)
    {

    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->message->chat_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'user_id' => $this->message->user_id,
            'message' => $this->message->text,
            'created_at' => $this->message->created_at,
        ];
    }
}
