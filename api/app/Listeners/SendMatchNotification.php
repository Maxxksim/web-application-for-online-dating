<?php

namespace App\Listeners;

use App\Events\MatchCreated;
use App\Notifications\MatchNotification;

class SendMatchNotification
{
    public function handle(MatchCreated $event): void
    {
        $match = $event->match;

        $match->firstUser->notify(new MatchNotification($match->secondUser));
        $match->secondUser->notify(new MatchNotification($match->firstUser));
    }
}
