<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chat_id' => $this->chat_id,
            'recipient_id' => $this->recipient_id,
            'text' => $this->text,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'sender_id' => $this->sender_id,
            'profile_id' => $this->sender->profile_id,
        ];
    }
}
