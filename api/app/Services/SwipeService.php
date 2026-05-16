<?php

namespace App\Services;

use App\Events\LikeSent;
use App\Events\MatchCreated;
use App\Models\MutualLike;
use App\Models\Swipe;
use App\Models\User;

class SwipeService
{
    public function swipe(int $swiper_id, int $swiped_id, bool $isLiked): void
    {
        $swipe = Swipe::create([
            'swiper_id' => $swiper_id,
            'swiped_id' => $swiped_id,
            'is_liked' => $isLiked
        ]);

        if (!$isLiked) {
            return;
        }

        if ($this->isMatch($swiper_id, $swiped_id)) {
            $this->handleMatch($swiper_id, $swiped_id);
            return;
        }

        LikeSent::dispatch($swipe);
    }

    private function isMatch(int $swiper_id, int $swiped_id): bool
    {
        return Swipe::where('swiper_id', $swiped_id)
            ->where('swiped_id', $swiper_id)
            ->where('is_liked', true)
            ->exists();
    }

    private function handleMatch(int $swiper_id, int $swiped_id): void
    {
        $match = MutualLike::create([
            'first_user_id' => $swiper_id,
            'second_user_id' => $swiped_id,
        ]);

        MatchCreated::dispatch($match);
    }
}
