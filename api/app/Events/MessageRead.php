<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $chatId,
        public readonly int $recipientId,
        public readonly int $senderId,
        public readonly string $readAt,
    ) {
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chats.{$this->senderId}"),
            new PrivateChannel("chats.{$this->recipientId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'recipient_id' => $this->recipientId,
            'sender_id' => $this->senderId,
            'read_at' => $this->readAt,
        ];
    }
}
