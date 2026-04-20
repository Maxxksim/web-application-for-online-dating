<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'date_of_birthday' => $this->date_of_birthday,
            'gender' => $this->gender,
            'description' => $this->description,
            'photos' => ProfilePhotoResource::collection($this->photos),
            'completion_percentage' => $this->when($request->user()->id === $this->user_id, $this->completion_percentage),
        ];
    }
}
