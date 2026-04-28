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
        $currentGeoPoint = $profile->geolocation->geo_point;

        return Profile::join('geolocations', 'profiles.id', '=', 'geolocations.profile_id')
            ->whereRaw("ST_DWithin(geolocations.geo_point::geography, {$currentGeoPoint}::geography, ?)", [
                $searchFilters['distance'] * 1000
            ])
            ->where('profiles.id', '!=', $profile->id)
            ->whereRaw("DATE_PART('year', AGE(profiles.date_of_birth)) BETWEEN ? AND ?", [
                $searchFilters->min_age,
                $searchFilters->max_age
            ])
            ->where('profiles.gender', $searchFilters['gender'])
            ->select('profiles.*')
            ->selectRaw("ST_Distance(geolocations.geo_point::geography, {$currentGeoPoint}::geography) / 1000 as distance")
            ->orderBy('distance')
            ->paginate(20);
    }
}
