<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct()
    {

    }

    public function getUserChats(User $user): Collection
    {
        return $user->chats()->with([
            'users' => function ($query) use ($user) {
                $query->where('users.id', '!=', $user->id)
                    ->select('users.id');
            },
        ])->withCount([
            'messages as unread_count' => function ($query) use ($user) {
                $query->whereNull('read_at')
                    ->where('user_id', '!=', $user->id);
            },
        ])->orderByDesc('chats.last_message_at')->get();
    }

    public function firstOrCreate(User $sender, User $recipient): array
    {
        return DB::transaction(function () use ($sender, $recipient) {
            $chat = Chat::whereAttachedTo($sender)
                ->whereAttachedTo($recipient)
                ->lockForUpdate()
                ->first();

            if ($chat) {
                return ['chat' => $chat, 'isExisted' => true];
            }

            $chat = Chat::create();
            $chat->users()->attach([$sender->id, $recipient->id]);

            return ['chat' => $chat, 'isExisted' => false];
        });
    }

}
