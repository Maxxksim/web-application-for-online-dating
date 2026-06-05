<?php

namespace App\Http\Requests;

use App\Models\Enums\BodyType;
use App\Models\Enums\ChildrenStatus;
use App\Models\Enums\DatingPurpose;
use App\Models\Enums\EyeColor;
use App\Models\Enums\Habit;
use App\Models\Enums\HairColor;
use App\Models\Enums\ZodiacSign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:' . now()->subYears(18)->toDateString()
            ],
            'gender' => ['sometimes', 'nullable', 'in:man,woman'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1200'],

            'dating_purpose' => ['sometimes', 'nullable', 'string', Rule::enum(DatingPurpose::class)],

            'height' => ['sometimes', 'nullable', 'numeric', 'min:70', 'max:250'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:20', 'max:200'],

            'body_type' => ['sometimes', 'nullable', Rule::enum(BodyType::class)],
            'eye_color' => ['sometimes', 'nullable', Rule::enum(EyeColor::class)],
            'hair_color' => ['sometimes', 'nullable', Rule::enum(HairColor::class)],
            'smoking' => ['sometimes', 'nullable', Rule::enum(Habit::class)],
            'drinking' => ['sometimes', 'nullable', Rule::enum(Habit::class)],
            'children' => ['sometimes', 'nullable', Rule::enum(ChildrenStatus::class)],
            'zodiac_sign' => ['sometimes', 'nullable', Rule::enum(ZodiacSign::class)],
            'exercise' => ['sometimes', 'nullable', Rule::enum(Habit::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old.',
        ];
    }
}
