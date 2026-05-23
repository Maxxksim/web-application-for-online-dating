<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\SearchFilters;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class SearchService
{
    public function searchByFilters(User $user): LengthAwarePaginator
    {
        $userGeoPoint = "(SELECT geo_point FROM geolocations WHERE user_id = ?)::geography";

        return Profile::join('geolocations', 'profiles.user_id', '=', 'geolocations.user_id')
            ->join('search_filters', 'profiles.user_id', '=', 'search_filters.user_id')
            ->whereRaw("ST_DWithin(geolocations.geo_point::geography, {$userGeoPoint}, ?)", [
                $user->id,
                $user->searchFilter->distance * 1000
            ])
            ->where('profiles.user_id', '!=', $user->id)
            ->whereNotIn('profiles.user_id', function ($query) use ($user) {
                $query->select('swiped_id')->from('swipes')->where('swiper_id', $user->id);
            })
            ->whereRaw("DATE_PART('year', AGE(profiles.date_of_birth)) BETWEEN ? AND ?", [
                $user->searchFilter->min_age,
                $user->searchFilter->max_age
            ])
            ->when($user->searchFilter->gender !== 'both', function ($query) use ($user) {
                $query->where('profiles.gender', $user->searchFilter->gender);
            })
            ->whereIn('search_filters.gender', ['both', $user->profile->gender])
            ->where('is_enabled', true)
            ->select('profiles.*')
            ->selectRaw("ST_Distance(geolocations.geo_point::geography, {$userGeoPoint}) / 1000 as distance", [
                $user->id
            ])->orderBy('relevance_score', 'DESC')
            ->orderBy('distance')
            ->paginate(20);
    }
}
