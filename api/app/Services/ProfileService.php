<?php

namespace App\Services;

use App\Models\Profile;

class ProfileService
{
    public function updateCompletionPercentage(Profile $profile): void
    {
        $countPhotos = $profile->photos()->count();
        $countFilled = collect($profile->only(['name', 'date_of_birth', 'gender', 'description']))->filter(fn($value) => $value !== null)->count();

        $profile->update([
            'completion_percentage' => (int)(($countPhotos + $countFilled) / 7 * 100)
        ]);
    }

    public function updateLocation(Profile $profile, $location): void
    {
        $profile->update([
            'city' => $location['city'],
            'country' => $location['country'],
        ]);
    }
}
