<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeolocationRequest;
use App\Services\LocationService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    public function __construct(private readonly LocationService $geolocationService)
    {

    }
    public function updateLocation(UpdateGeolocationRequest $request): JsonResponse
    {
        $this->geolocationService->updateLocation($request->user(), $request->validated());

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
