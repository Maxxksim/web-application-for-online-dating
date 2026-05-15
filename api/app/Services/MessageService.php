<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessageService
{

    public function __construct()
    {

    }

    public function markAsRead(Message $message): void
    {
        if (!$message->isRead()) {
            $message->update(['read_at' => now()]);
        }
    }

    public function sendMessage(Chat $chat, int $userId, string $text): Message
    {
        $message = DB::transaction(function () use ($chat, $userId, $text) {
            $message = $chat->messages()->create([
                'user_id' => $userId,
                'text' => $text,
            ]);

            $chat->update(['last_message_at' => now()]);

            return $message;
        });

        $message->load('user');

        MessageSent::dispatch($message);

        return $message;
    }

    public function getMessages(Chat $chat, int $perPage = 30): LengthAwarePaginator
    {
        return $chat->messages()
            ->with('user.profile')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

}
