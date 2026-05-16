<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Message $message)
    {

    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'sender_id' => $this->message->user_id,
        ];
    }
}
