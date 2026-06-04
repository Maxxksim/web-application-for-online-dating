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
            'min_age' => ['sometimes', 'nullable', 'integer', 'min:18', 'lt:max_age'],
            'max_age' => ['sometimes', 'nullable', 'integer', 'max:120', 'gt:min_age'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:man,woman,both'],
            'distance' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'interests' => ['sometimes', 'nullable', 'array', 'max:10'],
            'interests.*' => ['nullable', 'string', Rule::enum(Interest::class)],

            'dating_purpose' => ['sometimes', 'nullable', Rule::enum(DatingPurpose::class)],
            'body_type' => ['sometimes', 'nullable', Rule::enum(BodyType::class)],
            'eye_color' => ['sometimes', 'nullable', Rule::enum(EyeColor::class)],
            'hair_color' => ['sometimes', 'nullable', Rule::enum(HairColor::class)],
            'smoking' => ['sometimes', 'nullable', Rule::enum(Habit::class)],
            'drinking' => ['sometimes', 'nullable', Rule::enum(Habit::class)],
            'children' => ['sometimes', 'nullable', Rule::enum(ChildrenStatus::class)],
            'zodiac_sign' => ['sometimes', 'nullable', Rule::enum(ZodiacSign::class)],
            'exercise' => ['sometimes', 'nullable', Rule::enum(Habit::class)],

            'min_height' => ['sometimes', 'nullable', 'numeric', 'min:70', 'max:250'],
            'max_height' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:70',
                'max:250',
                function ($value, $fail) {
                    if ($value && request('min_height') && $value < request('min_height')) {
                        $fail('Max height must be greater than min height.');
                    }
                }
            ],

            'min_weight' => ['sometimes', 'nullable', 'numeric', 'min:20', 'max:200'],
            'max_weight' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:20',
                'max:200',
                function ($attribute, $value, $fail) {
                    if ($value && request('min_weight') && $value < request('min_weight')) {
                        $fail('Max weight must be greater than min weight.');
                    }
                }
            ],

            'use_advanced_filters' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}
