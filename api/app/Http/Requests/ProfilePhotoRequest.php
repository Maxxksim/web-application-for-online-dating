<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfilePhotoRequest extends FormRequest
{
    public function rules(): array
    {
        $existing = $this->user()->photos()->count();

        return [
            'photos' => ['required', 'array', 'min:1', 'max:' . max(0, 3 - $existing)],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'photos.max' => 'You can upload a maximum of 3 photos.',
        ];
    }
}
