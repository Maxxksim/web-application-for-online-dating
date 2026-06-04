<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LikeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $likedByUser,
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
            'type' => 'like_received',
            'user_id' => $this->likedByUser->id,
            'profile_id' => $this->likedByUser->profile->id,
            'name' => $this->likedByUser->profile->name,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'like_received',
            'message' => 'You have received a like from ' . $this->likedByUser->profile->name,
            'user_id' => $this->likedByUser->id,
            'profile_id' => $this->likedByUser->profile->id,
            'name' => $this->likedByUser->profile->name,
        ]);
    }
}
