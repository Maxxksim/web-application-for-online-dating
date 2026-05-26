<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchSettingsRequest;
use App\Http\Requests\UpdateSearchFilters;
use App\Http\Resources\SearchFiltersResource;
use App\Services\ProfileService;
use App\Services\SearchService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService, private readonly ProfileService $profileService, private readonly SubscriptionService $subscriptionService)
    {

    }

    public function getSearchFilters(Request $request): JsonResponse
    {
        return response()->json(['filters' => new SearchFiltersResource($request->user()->searchFilter)], Response::HTTP_OK);
    }

    public function updateSearchFilters(UpdateSearchFilters $request): JsonResponse
    {
        $data = $request->validated();

        if (!$this->subscriptionService->isActive($request->user(), 'premium')) {
            $data['use_advanced_filters'] = false;
        }

        $request->user()->searchFilter->update($data);

        return response()->json(['message' => 'Search filters updated successfully.'], Response::HTTP_OK);
    }

    public function getProfilesByFilters(Request $request): JsonResponse
    {
        if ($this->profileService->isProfileReadyForSearching($request->user()->profile)) {

            if (!$request->user()->profile->is_enabled) {
                return response()->json(['message' => 'You must enable your profile.'], Response::HTTP_FORBIDDEN);
            }
            $this->profileService->updateRelevanceScore($request->user()->profile, $this->subscriptionService->isActive($request->user(), 'premium'));
            $profiles = $this->searchService->searchByFilters($request->user());

            if (empty($profiles)) {
                return response()->json(['message' => 'No profiles found matching your filters'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['profiles' => $profiles], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Please fill in all missing required fields and add at least one photo.',
            'missing_fields' => $this->profileService->getMissingRequiredFields($request->user()->profile)
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
