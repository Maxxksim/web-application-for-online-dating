<?php

namespace App\Http\Requests;

use App\Models\Enums\Interest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileInterestRequest extends FormRequest
{
    public function rules(): array
    {
        $profile = $this->user()->profile;

        return [
            'interest' => [
                'required',
                'string',
                'min:1',
                function ($attribute, $value, $fail) use ($profile) {
                    if ($profile->interests()->count() + count($value) > 10) {
                        $fail('You can have a maximum of 10 interests.');
                    }
                },
                Rule::enum(Interest::class),
                Rule::unique('interests', 'interest')->where('profile_id', $profile->id)
            ]
        ];
    }
}
