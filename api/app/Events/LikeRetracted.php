<?php

namespace App\Events;

use App\Models\Swipe;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LikeRetracted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Swipe $swipe,
    )
    {

    }

    public function broadcastAs(): string
    {
        return 'like.retracted';
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('likes.' . $this->swipe->swiped_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'profile_id' => $this->swipe->swiper_id
        ];
    }
}
