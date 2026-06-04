<?php

namespace App\Http\Requests;

use App\Models\Enums\BodyType;
use App\Models\Enums\ChildrenStatus;
use App\Models\Enums\DatingPurpose;
use App\Models\Enums\EyeColor;
use App\Models\Enums\Habit;
use App\Models\Enums\HairColor;
use App\Models\Enums\ZodiacSign;
use App\Models\Enums\Interest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSearchFilters extends FormRequest
{
    public function rules(): array
    {
        return [
            'min_age' => ['sometimes', 'integer', 'min:18', 'lt:max_age'],
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
            'min_height' => ['sometimes', 'numeric', 'min:70', 'max:250'],
            'max_height' => ['sometimes', 'numeric', 'min:70', 'max:250',
                function ($value, $fail) {
                    if ($value && request('min_height') && $value < request('min_height')) {
                        $fail('Max height must be greater than min height.');
                    }
                }],
            'min_weight' => ['sometimes', 'numeric', 'min:20', 'max:200'],
            'max_weight' => ['sometimes', 'numeric', 'min:20', 'max:200',
                function ($attribute, $value, $fail) {
                    if ($value && request('min_weight') && $value < request('min_weight')) {
                        $fail('Max weight must be greater than min weight.');
                    }
                }],
            'use_advanced_filters' => ['sometimes', 'boolean'],
        ];
    }
}
