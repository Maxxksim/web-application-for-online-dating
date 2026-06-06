<?php

namespace App\Services;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageService
{

    public function __construct(private readonly ChatService $chatService)
    {

    }

    public function markAsRead(Chat $chat, int $userId): void
    {
        $unreadMessages = $chat->messages()
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->get(['id', 'sender_id']);

        if ($unreadMessages->isEmpty()) {
            return;
        }

        $readAt = now();

        $chat->messages()
            ->whereIn('id', $unreadMessages->pluck('id'))
            ->update(['read_at' => $readAt]);

        User::find($userId)->unreadNotifications()
            ->where('type', 'App\Notifications\MessageNotification')
            ->whereRaw("(data::jsonb->>'chat_id')::integer = ?", [$chat->id])
            ->update(['read_at' => $readAt]);

        $unreadMessages->pluck('sender_id')
            ->unique()
            ->each(function (int $senderId) use ($chat, $userId, $readAt) {
                MessageRead::dispatch($chat->id, $userId, $senderId, $readAt->toIso8601String());
            });
    }

    public function sendMessage(User $sender, User $recipient, $text): Model
    {
        $chat = $this->chatService->firstOrCreate($sender, $recipient);

        $message = $chat->messages()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'text' => $text,
        ]);

        $chat->update(['last_message_at' => now()]);

        MessageSent::dispatch($message);

        return $message;
    }

    public function getMessages(Chat $chat, int $perPage = 30): LengthAwarePaginator
    {
        return $chat->messages()
            ->with('sender.profile')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

}
