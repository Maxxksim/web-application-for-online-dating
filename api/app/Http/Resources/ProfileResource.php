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
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'description' => $this->description,
            'photos' => ProfilePhotoResource::collection($this->photos),
            'country' => $this->country,
            'city' => $this->city,
            'age' => $this->age,
            'completion_percentage' => $this->when($request->user()->id === $this->user_id, $this->completion_percentage),
            'is_enabled' => $this->is_enabled,
        ];
    }
}
