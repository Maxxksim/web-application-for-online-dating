<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\SearchFilters;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchService
{
    public function searchByFilters(User $user, bool $additionalFilters = false): LengthAwarePaginator
    {
        $userGeoPoint = "(SELECT geo_point FROM geolocations WHERE user_id = ?)::geography";

        $query = Profile::join('geolocations', 'profiles.user_id', '=', 'geolocations.user_id')
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
            ]);

        if ($user->subscribed('premium') && $additionalFilters) {
            $this->applyAdditionalFilters($query, $user->searchFilter);
        }

        return $query->orderBy('relevance_score', 'DESC')
            ->orderBy('distance')
            ->paginate(20);

    }

    private function applyAdditionalFilters(Builder $query, array $filters): void
    {
        $interests = $filters['interests'] ?? null;
        $rangeFields = [
            'min_height' => ['profiles.height', '>='],
            'max_height' => ['profiles.height', '<='],
            'min_weight' => ['profiles.weight', '>='],
            'max_weight' => ['profiles.weight', '<='],
        ];

        foreach ($filters as $field => $value) {
            if (isset($rangeFields[$field])) {
                [$column, $operator] = $rangeFields[$field];
                $query->where($column, $operator, $value);
            } elseif ($field !== 'interests') {
                $query->where("profiles.{$field}", $value);
            }
        }

        $query->when($interests, fn($q) => $q->whereExists(fn($q) => $q->select(DB::raw(1))
            ->from('interests')
            ->whereColumn('interests.profile_id', 'profiles.id')
            ->whereIn('interests.interest', (array)$interests)
        ));
    }
}
