<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSearchFilters extends FormRequest
{
    public function rules(): array
    {
        return [
            'min_age' => ['sometimes', 'integer', 'min:16', 'lt:max_age'],
            'max_age' => ['sometimes', 'integer', 'max:120', 'gt:min_age'],
            'gender' => ['sometimes', 'string', 'in:man,woman,both'],
            'distance' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
