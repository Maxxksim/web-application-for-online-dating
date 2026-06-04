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
            'name' => ['sometimes', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'gender' => ['sometimes', 'in:man,woman'],
            'description' => ['sometimes', 'string', 'max:1200'],
            'dating_purpose' => ['sometimes', 'string', Rule::enum(DatingPurpose::class)],
            'height' => ['sometimes', 'numeric', 'min:70', 'max:250'],
            'weight' => ['sometimes', 'numeric', 'min:20', 'max:200'],
            'body_type' => ['sometimes', Rule::enum(BodyType::class)],
            'eye_color' => ['sometimes', Rule::enum(EyeColor::class)],
            'hair_color' => ['sometimes', Rule::enum(HairColor::class)],
            'smoking' => ['sometimes', Rule::enum(Habit::class)],
            'drinking' => ['sometimes', Rule::enum(Habit::class)],
            'children' => ['sometimes', Rule::enum(ChildrenStatus::class)],
            'zodiac_sign' => ['sometimes', Rule::enum(ZodiacSign::class)],
            'exercise' => ['sometimes', Rule::enum(Habit::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old.',
        ];
    }
}
