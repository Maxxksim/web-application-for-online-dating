<?php

namespace App\Notifications;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MatchNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $matchedWithUser,
    )
    {
    }


    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(User $notifiable): array
    {
        return [
            'matched_user_profile_id' => $this->matchedWithUser->profile->id,
        ];
    }
}
