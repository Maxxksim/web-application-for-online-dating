<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\SearchFilters;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function searchByFilters(SearchFilters $searchFilters, int $user_id): LengthAwarePaginator
    {
        $userGeoPoint = "(SELECT geo_point FROM geolocations WHERE user_id = ?)::geography";

        return Profile::join('geolocations', 'profiles.user_id', '=', 'geolocations.user_id')
            ->whereRaw("ST_DWithin(geolocations.geo_point::geography, {$userGeoPoint}, ?)", [
                $user_id,
                $searchFilters->distance * 1000
            ])
            ->where('profiles.user_id', '!=', $user_id)
            ->whereNotIn('profiles.user_id', function ($query) use ($user_id) {
                $query->select('swiped_id')->from('swipes')->where('swiper_id', $user_id);
            })
            ->whereRaw("DATE_PART('year', AGE(profiles.date_of_birth)) BETWEEN ? AND ?", [
                $searchFilters->min_age,
                $searchFilters->max_age
            ])
            ->where('profiles.gender', $searchFilters->gender)
            ->select('profiles.*')
            ->selectRaw("ST_Distance(geolocations.geo_point::geography, {$userGeoPoint}) / 1000 as distance", [
                $user_id
            ])
            ->orderBy('distance')
            ->paginate(20);
    }
}
