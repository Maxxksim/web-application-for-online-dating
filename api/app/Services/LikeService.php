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
        return Profile::whereHas('swipes', function ($query) use ($user) {
            $query->where('swiped_id', $user->id)
                ->where('is_liked', true);
        })->get();
    }
}
