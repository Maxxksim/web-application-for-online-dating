<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfilePhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png'],
        ];
    }
}
