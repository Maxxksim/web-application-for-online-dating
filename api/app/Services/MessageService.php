<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageService
{

    public function __construct(private readonly ChatService $chatService)
    {

    }

    public function markAsRead(Chat $chat, int $userId): void
    {
        $chat->messages()
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function sendMessage(User $sender, User $recipient, $text): void
    {
        $chat = $this->chatService->firstOrCreate($sender, $recipient);

        $message = $chat->messages()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'text' => $text,
        ]);

        $chat->update(['last_message_at' => now()]);

        MessageSent::dispatch($message);
    }

    public function getMessages(Chat $chat, int $perPage = 30): LengthAwarePaginator
    {
        return $chat->messages()
            ->with('sender.profile')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

}
