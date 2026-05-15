<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Message $message)
    {

    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'chat_id' => $this->message->chat_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user->profile->name,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'chat_id' => $this->message->chat_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user->profile->name,
            'created_at' => $this->message->created_at,
        ]);
    }

    public function broadcastType(): string
    {
        return 'new.message';
    }

}
