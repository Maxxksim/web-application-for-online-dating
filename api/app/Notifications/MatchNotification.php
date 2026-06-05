<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $matchedWithUser,
    )
    {
    }


    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'profile_id' => $this->matchedWithUser->profile->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'profile_id' => $this->matchedWithUser->profile->id,
            'name' => $this->matchedWithUser->profile->name,
        ]);
    }
}
