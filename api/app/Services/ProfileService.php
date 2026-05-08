<?php

namespace App\Services;

use App\Models\Profile;

class ProfileService
{
    private const array REQUIRED_FIELDS = ['name', 'date_of_birth', 'gender'];
    private const array OPTIONAL_FIELDS = ['description'];

    public function updateCompletionPercentage(Profile $profile): void
    {
        $countPhotos = $profile->photos()->count();
        $countFilled = collect($profile->only(array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS)))->filter(fn($value) => $value !== null)->count();

        $profile->update([
            'completion_percentage' => (int)(($countPhotos + $countFilled) / 7 * 100)
        ]);
    }

    public function getMissingRequiredFields(Profile $profile): array
    {
        return collect($profile->only(self::REQUIRED_FIELDS))
            ->filter(fn($value) => $value === null)
            ->keys()
            ->all();
    }

    public function isProfileReadyForSearching(Profile $profile): bool
    {
        return empty($this->getMissingRequiredFields($profile)) && $profile->photos()->exists();
    }

}
