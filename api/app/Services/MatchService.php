<?php

namespace App\Services;

use App\Models\MutualLike;
use App\Models\User;

class MatchService
{
    public function __construct()
    {

    }

    public function getMatches(User $user): array
    {
        return MutualLike::where('first_user_id', $user->id)->orWhere('second_user_id', $user->id)->get();
    }

    public function haveMatch(int $firstUserId, int $secondUserId): bool
    {
        return MutualLike::whereIn('first_user_id', [$firstUserId, $secondUserId])
            ->whereIn('second_user_id', [$firstUserId, $secondUserId])
            ->where('first_user_id', '!=', 'second_user_id')
            ->exists();
    }
}
