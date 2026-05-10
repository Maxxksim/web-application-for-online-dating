<?php

namespace App\Listeners;

use App\Events\LikeProcessed;
use App\Notifications\LikeNotification;

class SendLikeNotification
{
    public function handle(LikeProcessed $event): void
    {
        $swipe = $event->swipe;

        $swipe->swiped->notify(new LikeNotification($swipe->swiper));
    }
}
