<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchFiltersResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'min_age'        => $this->min_age,
            'max_age'        => $this->max_age,
            'gender'         => $this->gender,
            'distance'       => $this->distance,
            'interests'      => $this->interests,
            'dating_purpose' => $this->dating_purpose,
            'body_type'      => $this->body_type,
            'eye_color'      => $this->eye_color,
            'hair_color'     => $this->hair_color,
            'smoking'        => $this->smoking,
            'drinking'       => $this->drinking,
            'children'       => $this->children,
            'zodiac_sign'    => $this->zodiac_sign,
            'exercise'       => $this->exercise,
            'min_height'     => $this->min_height,
            'max_height'     => $this->max_height,
            'min_weight'     => $this->min_weight,
            'max_weight'     => $this->max_weight,
            'use_advanced_filters' => $this->use_advanced_filters
        ];
    }
}
