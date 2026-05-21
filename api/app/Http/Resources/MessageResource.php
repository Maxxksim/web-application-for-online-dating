<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MessageResource extends ResourceCollection
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chat_id' => $this->chat_id,
            'sender_id' => $this->sender_id,
            'recipient_id' => $this->recipient_id,
            'text' => $this->text,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'sender' => [
                'user_id' => $this->sender->id,
                'profile' => new ProfileResource($this->sender->profile)
            ]
        ];
    }
}
