<?php

namespace App\Services;

use App\Events\MatchCreated;
use App\Models\MutualLike;
use App\Models\Swipe;
use App\Models\User;

class SwipeService
{
    public function swipe(int $swiper_id, int $swiped_id, bool $isLiked): void
    {
        Swipe::create([
            'swiper_id' => $swiper_id,
            'swiped_id' => $swiped_id,
            'is_liked' => $isLiked
        ]);

        if ($this->isMatch($swiper_id, $swiped_id)) {

            $match = MutualLike::create([
                'first_user_id' => $swiper_id,
                'second_user_id' => $swiped_id,
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

    public function getWhoLiked(User $user): array
    {
        return Swipe::join('profiles', 'profiles.user_id', '=', 'swiper_id')
            ->where('swiped_id', $user->id)
            ->where('is_liked', true)
            ->select('profiles.*');
    }

    public function getMutualLikes(User $user): array
    {
        return MutualLike::where('first_user_id', $user->id)->orWhere('second_user_id', $user->id);
    }
}
