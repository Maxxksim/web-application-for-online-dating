<?php

namespace App\Listeners;

use App\Events\MatchCreated;
use App\Notifications\MatchNotification;

class SendMatchNotification
{
    public function handle(MatchCreated $event): void
    {
        $match = $event->match;

        $firstUser = $match->firstProfile->user;
        $secondUser = $match->secondProfile->user;

        $firstUser->notify(new MatchNotification($match->secondProfile));
        $secondUser->notify(new MatchNotification($match->firstProfile));

    }
}
