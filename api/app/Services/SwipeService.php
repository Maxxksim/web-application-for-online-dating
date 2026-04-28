<?php

namespace App\Services;

use App\Events\MatchCreated;
use App\Models\MutualLike;
use App\Models\Profile;
use App\Models\Swipe;

class SwipeService
{
    public function __construct()
    {

    }

    public function swipe(int $swiper_id, int $swiped_id, bool $isLike): void
    {
        Swipe::create([
            'swiper_id' => $swiper_id,
            'swiped_id' => $swiped_id,
            'is_like' => $isLike
        ]);

        if ($this->isMatch($swiper_id, $swiped_id)) {

            $match = MutualLike::create([
                'first_profile_id' => $swiper_id,
                'second_profile_id' => $swiped_id,
            ]);

            MatchCreated::dispatch($match);
        }
    }

    private function isMatch(int $swiper_id, int $swiped_id): bool
    {
        return Swipe::where('swiper_id', $swiped_id)
            ->where('swiped_id', $swiper_id)
            ->where('is_liked', true)
            ->exists();
    }
}
