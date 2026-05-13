<?php

namespace App\Events;

use App\Models\MutualLike;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MutualLike $match,
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('matches.' . $this->match->first_user_id),
            new PrivateChannel('matches.' . $this->match->second_user_id)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'User.' . $this->match->first_user_id => [
                'message' => 'You have matched with ' . $this->match->firstUser->profile->name . '!',
            ],
            'User.' . $this->match->second_user_id => [
                'message' => 'You have matched with ' . $this->match->secondUser->profile->name . '!',
            ],
        ];
    }
}
