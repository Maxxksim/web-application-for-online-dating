<?php

namespace App\Http\Requests;

use App\Models\Enums\Plans;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SubscriptionRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', Rule::enum(Plans::class)],
        ];
    }
}
