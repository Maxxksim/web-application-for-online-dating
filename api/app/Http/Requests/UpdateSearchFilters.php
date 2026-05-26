<?php

namespace App\Http\Requests;

use App\Models\Enums\BodyType;
use App\Models\Enums\ChildrenStatus;
use App\Models\Enums\DatingPurpose;
use App\Models\Enums\EyeColor;
use App\Models\Enums\Habit;
use App\Models\Enums\HairColor;
use App\Models\Enums\ZodiacSign;
use App\Models\Interest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSearchFilters extends FormRequest
{
    public function rules(): array
    {
        $this->subscriptionService->isActive($this->user(), 'premium');

        return [
            'min_age' => ['sometimes', 'integer', 'min:16', 'lt:max_age'],
            'max_age' => ['sometimes', 'integer', 'max:120', 'gt:min_age'],
            'gender' => ['sometimes', 'string', 'in:man,woman,both'],
            'distance' => ['sometimes', 'integer', 'min:1'],
            'interests' => ['sometimes', 'array', 'max:10'],
            'interests.*' => ['string', Rule::enum(Interest::class)],
            'dating_purpose' => ['sometimes', Rule::enum(DatingPurpose::class)],
            'body_type' => ['sometimes', Rule::enum(BodyType::class)],
            'eye_color' => ['sometimes', Rule::enum(EyeColor::class)],
            'hair_color' => ['sometimes', Rule::enum(HairColor::class)],
            'smoking' => ['sometimes', Rule::enum(Habit::class)],
            'drinking' => ['sometimes', Rule::enum(Habit::class)],
            'children' => ['sometimes', Rule::enum(ChildrenStatus::class)],
            'zodiac_sign' => ['sometimes', Rule::enum(ZodiacSign::class)],
            'exercise' => ['sometimes', Rule::enum(Habit::class)],
            'min_height' => ['sometimes', 'numeric', 'lt:max_height'],
            'max_height' => ['sometimes', 'numeric', 'gt:min_height'],
            'min_weight' => ['sometimes', 'numeric', 'lt:max_weight'],
            'max_weight' => ['sometimes', 'numeric', 'gt:min_weight'],
            'use_advanced_filters' => ['sometimes', 'boolean'],
        ];
    }
}
