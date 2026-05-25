<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'profile_id' => $this->id,
            'name' => $this->name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'description' => $this->description,
            'photos' => ProfilePhotoResource::collection($this->photos),
            'country' => $this->country,
            'city' => $this->city,
            'age' => $this->age,
            'dating_purpose' => $this->dating_purpose,
            'height' => $this->height,
            'weight' => $this->weight,
            'body_type' => $this->body_type,
            'eye_color' => $this->eye_color,
            'hair_color' => $this->hair_color,
            'smoking' => $this->smoking,
            'drinking' => $this->drinking,
            'children' => $this->children,
            'zodiac_sign' => $this->zodiac_sign,
            'exercise' => $this->exercise,
            'interests' => $this->interests,
            'completion_percentage' => $this->when($request->user()->id === $this->user_id, $this->completion_percentage),
            'is_enabled' => $this->is_enabled,
        ];
    }
}
