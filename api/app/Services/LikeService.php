<?php

namespace App\Services;

use App\Models\Swipe;
use App\Models\User;

class LikeService
{
    public function getWhoLiked(User $user): array
    {
        return Swipe::join('profiles', 'profiles.user_id', '=', 'swiper_id')
            ->where('swiped_id', $user->id)
            ->where('is_liked', true)
            ->select('profiles.*')
            ->get();
    }
}
