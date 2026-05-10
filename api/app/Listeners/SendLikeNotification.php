<?php

namespace App\Listeners;

use App\Events\LikeProcessed;
use App\Notifications\LikeNotification;
use App\Notifications\MatchNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLikeNotification
{
    public function handle(LikeProcessed $event): void
    {
        $swipe = $event->swipe;

        $swipe->swiped->notify(new LikeNotification($swipe->swiper));
    }
}
