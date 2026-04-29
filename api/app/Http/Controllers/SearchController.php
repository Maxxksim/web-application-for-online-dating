<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSearchFilters;
use App\Http\Resources\SearchFiltersResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService)
    {

    }

    public function getSearchFilters(Request $request): JsonResponse
    {
        return response()->json(new SearchFiltersResource($request->user()->profile->searchFilters), Response::HTTP_OK);
    }

    public function updateSearchFilters(UpdateSearchFilters $request): JsonResponse
    {
        $request->user()->profile->searchFilters->update($request->validated());

        return response()->json(['message' => 'Search filters updated successfully.'], Response::HTTP_OK);
    }

    public function getProfilesByFilters(Request $request): JsonResponse
    {
        $profiles = $this->searchService->searchByFilters($request->user()->searchFilter, $request->user()->id);

        if (empty($profiles)) {
            return response()->json(['message' => 'No profiles found matching your filters'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['profiles' => $profiles], Response::HTTP_OK);
    }
}
