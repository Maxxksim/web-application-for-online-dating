<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfilePhotoValidated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int   $profileId,
        public array $result
    )
    {
    }

    public function broadcastAs(): string
    {
        return 'photo.validated';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("profiles.{$this->profileId}");
    }

    public function broadcastWith(): array
    {
        return [
            'result' => $this->result,
        ];
    }
}
