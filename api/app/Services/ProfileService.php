<?php

namespace App\Services;

use App\Models\Profile;

class ProfileService
{
    private const array REQUIRED_FIELDS = ['name', 'date_of_birth', 'gender', 'country'];
    private const array OPTIONAL_FIELDS = ['description'];
    private const int MAX_PHOTOS = 3;
    private const int DAILY_SEARCH_RELEVANCE_BONUS = 3;
    private const int RELEVANCE_BONUS_MULTIPLIER = 2;

    public function updateCompletionPercentage(Profile $profile): void
    {
        $countPhotos = $profile->photos()->count();
        $countFilled = collect($profile->only(array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS)))->filter(fn($value) => $value !== null)->count();

        $total = count(array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS)) + self::MAX_PHOTOS;

        $profile->update([
            'completion_percentage' => (int)(($countPhotos + $countFilled) / $total * 100)
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

    public function updateRelevanceScore(Profile $profile, bool $isSubscription): void
    {
        $daysSinceUpdate = $profile->relevance_score_updated_on->diffInDays(now());

        if ($daysSinceUpdate >= 1) {
            $profile->decrement('relevance_score', $daysSinceUpdate);
        }

        if (!$profile->relevance_score_updated_on->isToday()) {
            if ($isSubscription) {
                $profile->increment('relevance_score', self::DAILY_SEARCH_RELEVANCE_BONUS * self::RELEVANCE_BONUS_MULTIPLIER);
            } else {
                $profile->increment('relevance_score', self::DAILY_SEARCH_RELEVANCE_BONUS);
            }
        }
        $profile->update(['relevance_score_updated_on' => now()]);
    }
}
