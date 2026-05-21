<?php

namespace App\Services;

use App\Models\MutualLike;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MatchService
{
    public function __construct()
    {

    }

    public function getMatchedProfiles(User $user): Collection
    {
        $first = MutualLike::where('first_user_id', $user->id)->select('second_user_id as matched_id');
        $second = MutualLike::where('second_user_id', $user->id)->select('first_user_id as matched_id');

        return Profile::whereIn('user_id', $first->unionAll($second))
            ->get();
    }

    public function haveMatch(int $firstUserId, int $secondUserId): bool
    {
        return MutualLike::whereIn('first_user_id', [$firstUserId, $secondUserId])
            ->whereIn('second_user_id', [$firstUserId, $secondUserId])
            ->whereColumn('first_user_id', '!=', 'second_user_id')
            ->exists();
    }
}
