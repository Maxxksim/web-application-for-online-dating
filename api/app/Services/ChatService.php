<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\User;
use App\Services\traits\SortsUserIds;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    use SortsUserIds;

    public function __construct()
    {

    }

    public function getUserChats(User $user): Collection
    {
        return $user->chats()
            ->select('chats.*', DB::raw("
            CASE WHEN first_user_id = {$user->id}
            THEN second_user_id
            ELSE first_user_id END as interlocutor_id
        "))
            ->with('interlocutor.profile')
            ->withCount([
                'messages as unread_count' => fn($q) => $q->whereNull('read_at')
                    ->where('user_id', '!=', $user->id),
            ])
            ->orderByDesc('chats.last_message_at')
            ->get();
    }

    public function firstOrCreate(User $sender, User $recipient): array
    {
        [$firstId, $secondId] = $this->sortUserIds($sender->id, $recipient->id);

        $chat = Chat::firstOrCreate([
            'first_user_id' => $firstId,
            'second_user_id' => $secondId,
        ]);

        return ['chat' => $chat, 'isExisted' => !$chat->wasRecentlyCreated];
    }

}
