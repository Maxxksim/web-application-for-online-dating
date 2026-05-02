<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSearchFilters extends FormRequest
{
    public function rules(): array
    {
        return [
            'min_age' => ['required', 'integer', 'min:16', 'lt:max_age'],
            'max_age' => ['required', 'integer', 'max:120', 'gt:min_age'],
            'gender' => ['required', 'string', 'in:man,woman'],
            'distance' => ['required', 'integer', 'min:1'],
        ];
    }
}
