<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SwipeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_liked' => 'required|boolean',
        ];
    }
}
