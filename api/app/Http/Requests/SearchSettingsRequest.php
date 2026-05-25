<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'additional_filters' => ['required', 'boolean'],
        ];
    }
}
