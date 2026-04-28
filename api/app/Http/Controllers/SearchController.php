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

    public function getSearchFilters(Request $request): JsonResponse
    {
        return response()->json(new SearchFiltersResource($request->user()->profile->searchFilters), Response::HTTP_OK);
    }

    public function updateSearchFilters(UpdateSearchFilters $request): JsonResponse
    {
        $request->user()->profile->searchFilters->update($request->validated());

        return response()->json(['message' => 'Search filters updated'], Response::HTTP_OK);
    }

    public function getProfilesByFilters(Request $request, SearchService $searchService): JsonResponse
    {
        try {
            $result = $searchService->searchByFilters($request->user()->profile());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (empty($result)) {
            return response()->json(['message' => 'No profiles found matching your filters'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['result' => $result], Response::HTTP_OK);
    }
}
