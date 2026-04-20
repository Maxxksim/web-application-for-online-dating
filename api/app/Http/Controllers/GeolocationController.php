<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeolocationRequest;
use App\Services\GeolocationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GeolocationController extends Controller
{

    public function __construct(private GeolocationService $geolocationService)
    {

    }

    public function updateGeolocation(GeolocationRequest $request): JsonResponse
    {
        $this->geolocationService->updateGeolocation($request->user(), $request->validated());

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
