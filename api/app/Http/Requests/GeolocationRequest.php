<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GeolocationRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'float', 'between:-90,90'],
            'longitude' => ['required', 'float', 'between:-180,180'],
        ];
    }
}
