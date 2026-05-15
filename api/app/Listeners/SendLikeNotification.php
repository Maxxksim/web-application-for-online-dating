<?php

namespace App\Listeners;

use App\Events\LikeProcessed;
use App\Notifications\LikeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;

#[Queue('notifications')]
class SendLikeNotification implements ShouldQueue
{
    public function handle(LikeProcessed $event): void
    {
        $swipe = $event->swipe;

        $swipe->swiped->notify(new LikeNotification($swipe->swiper));
    }
}
