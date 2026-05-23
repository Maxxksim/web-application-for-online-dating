<?php

namespace App\Listeners;

use App\Events\LikeSent;
use App\Notifications\LikeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;

#[Queue('likes')]
class SendLikeNotification implements ShouldQueue
{
    public function handle(LikeSent $event): void
    {
        $event->swipe->swiped->notify(new LikeNotification($event->swipe->swiper));
    }
}
