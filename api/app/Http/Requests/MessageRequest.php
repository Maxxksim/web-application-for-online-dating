<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'text' => ['string', 'max:10000']
        ];
    }
}
