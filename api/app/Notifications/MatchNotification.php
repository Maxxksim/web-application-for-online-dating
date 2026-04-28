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
        private readonly Profile $matchedWith,
    )
    {
    }


    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }
    /* implement later
        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)
                ->line('The introduction to the notification.')
                ->action('Notification Action', url('/'))
                ->line('Thank you for using our application!');
        }
    */
    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return [
            'matched_with_id' => $this->matchedWith->profile_id,
        ];
    }
}
