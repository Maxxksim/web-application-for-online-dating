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
        $event->message->chat->interlocutor->notify(new MessageNotification($event->message));
    }
}
