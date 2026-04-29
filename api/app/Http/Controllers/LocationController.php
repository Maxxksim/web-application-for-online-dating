<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeolocationRequest;
use App\Services\LocationService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{

    public function __construct(private readonly LocationService $geolocationService, private readonly ProfileService $profileService)
    {

    }

    public function updateLocation(GeolocationRequest $request): JsonResponse
    {
        $this->geolocationService->updateLocation($request->user(), $request->validated(), $this->profileService);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
