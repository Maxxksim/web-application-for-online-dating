<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Swipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LikeService
{
    public function getProfilesWhoLiked(User $user): Collection
    {
        return Profile::whereIn('user_id',
            $user->receivedSwipes()
                ->where('is_liked', true)
                ->pluck('swiper_id')
        )->get();
    }
}
