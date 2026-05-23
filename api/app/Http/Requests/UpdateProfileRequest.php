<?php

namespace App\Http\Requests;

use App\Models\Enums\DatingPurpose;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:man,woman'],
            'description' => ['nullable', 'sometimes', 'string', 'max:500'],
            'dating_purpose' => ['nullable', 'sometimes', 'string', new Enum(DatingPurpose::class)],
        ];
    }
}
