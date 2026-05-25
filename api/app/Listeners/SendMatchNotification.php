<?php

namespace App\Listeners;

use App\Events\MatchCreated;
use App\Notifications\MatchNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;

#[Queue('matches')]
class SendMatchNotification implements ShouldQueue
{
    public function handle(MatchCreated $event): void
    {
        $match = $event->match;

        $match->firstUser->notify(new MatchNotification($match->secondUser));
        $match->secondUser->notify(new MatchNotification($match->firstUser));
    }
}
