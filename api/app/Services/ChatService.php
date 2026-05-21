<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use App\Services\traits\SortsUserIds;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    use SortsUserIds;

    public function __construct()
    {

    }

    public function getUserChats(User $user): Collection
    {
        return $user->chats()
            ->with(['users' => fn($q) => $q->where('users.id', '!=', $user->id)->with('profile')])
            ->withCount([
                'messages as unread_count' => fn($q) => $q
                    ->whereNull('read_at')
                    ->where('messages.user_id', '!=', $user->id),
            ])
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function firstOrCreate(User $sender, User $recipient): Chat
    {
        if ($chat = Chat::whereAttachedTo($sender)->whereAttachedTo($recipient)->first()) {
            return $chat;
        }

        ($chat = Chat::create())->users()->attach([$sender->id, $recipient->id]);

        return $chat;
    }

}
