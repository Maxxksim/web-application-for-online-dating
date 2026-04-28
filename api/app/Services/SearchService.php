<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{

    public function __construct()
    {

    }

    public function searchByFilters(Profile $profile): LengthAwarePaginator
    {

        $searchFilters = $profile->searchFilters;

        return Profile::join('geolocations', 'profiles.id', '=', 'geolocations.profile_id')
            ->whereRaw("ST_DWithin(geolocations.geo_point::geography, (SELECT geo_point FROM geolocations WHERE profile_id = ?)::geography, ?)", [
                $profile->id,
                $searchFilters->distance * 1000
            ])
            ->where('profiles.id', '!=', $profile->id)
            ->whereRaw("DATE_PART('year', AGE(profiles.date_of_birth)) BETWEEN ? AND ?", [
                $searchFilters->min_age,
                $searchFilters->max_age
            ])
            ->where('profiles.gender', $searchFilters['gender'])
            ->select('profiles.*')
            ->selectRaw("ST_Distance(geolocations.geo_point::geography, (SELECT geo_point FROM geolocations WHERE profile_id = ?)::geography) / 1000 as distance", [
                $profile->id
            ])
            ->orderBy('distance')
            ->paginate(20);
    }
}
