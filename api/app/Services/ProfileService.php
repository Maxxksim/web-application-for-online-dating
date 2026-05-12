<?php

namespace App\Services;

use App\Models\Profile;

class ProfileService
{
    private const array REQUIRED_FIELDS = ['name', 'date_of_birth', 'gender', 'city'];
    private const array OPTIONAL_FIELDS = ['description'];

    public function updateCompletionPercentage(Profile $profile): void
    {
        $countPhotos = $profile->photos()->count();
        $countFilled = collect($profile->only(array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS)))->filter(fn($value) => $value !== null)->count();

        $profile->update([
            'completion_percentage' => (int)(($countPhotos + $countFilled) / (count([self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS])) * 100)
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

    public function enableIfReady(Profile $profile): bool
    {
        if ($this->isProfileReadyForSearching($profile)) {
            $profile->update(['is_enabled' => true]);

            return true;
        }

        return false;
    }
}
