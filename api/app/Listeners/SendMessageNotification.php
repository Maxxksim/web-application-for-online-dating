<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Notifications\MessageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;

#[Queue('notifications')]
class SendMessageNotification implements ShouldQueue
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        $chat = $message->relationLoaded('chat')
            ? $message->chat
            : $message->chat()->with('users:id,name')->first();

        $recipient = $chat->getOtherUser($message->user_id);

        $recipient->notify(new MessageNotification($message));
    }
}
